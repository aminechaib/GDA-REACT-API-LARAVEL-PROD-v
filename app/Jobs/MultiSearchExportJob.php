<?php

namespace App\Jobs;

use App\Exports\MultiSearchExport;
use App\Models\Product;
use App\Models\ProductExport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class MultiSearchExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use SerializesModels;

    public int $timeout = 3600;

    public int $tries = 1;

    protected int $exportId;

    protected array $keywords;

    protected array $columns;

    protected ?int $maxResults;

    public function __construct(int $exportId, array $keywords, array $columns, ?int $maxResults = null)
    {
        $this->exportId = $exportId;
        $this->keywords = $keywords;
        $this->columns = $columns;
        $this->maxResults = $maxResults;
    }

    public function handle(): void
    {
        $export = ProductExport::findOrFail($this->exportId);
        $export->update(['status' => 'processing', 'matched_rows' => 0, 'keyword_index' => 0, 'current_keyword' => null]);

        try {
            $filename = 'multi_search_export_'.$export->id.'_'.now()->format('Y-m-d_H-i-s').'.xlsx';
            $rows = [];
            $totalMatched = 0;

            foreach ($this->keywords as $index => $keyword) {
                $export->update([
                    'keyword_index' => $index + 1,
                    'current_keyword' => $keyword,
                ]);

                $query = Product::query();
                $this->applyTolerantSearch($query, $keyword);

                // Stream rows, keeping a live "matched rows" counter and
                // building a single combined export (each row tagged with
                // its search keyword) capped per keyword if requested.
                $keywordMatched = 0;
                foreach ($query->lazyById(500) as $product) {
                    $keywordMatched++;

                    if ($this->maxResults !== null && $keywordMatched > $this->maxResults) {
                        break;
                    }

                    $rows[] = $this->formatRow($product, $keyword);

                    if ($keywordMatched % 500 === 0) {
                        $export->update(['matched_rows' => $totalMatched + $keywordMatched]);
                    }
                }
                $totalMatched += $keywordMatched;
                $export->update(['matched_rows' => $totalMatched]);
            }

            if (empty($rows)) {
                throw new \RuntimeException('No results matched any keyword.');
            }

            Excel::store(new MultiSearchExport($rows, $this->columns), $filename, 'local');

            $export->update([
                'status' => 'completed',
                'file_path' => $filename,
                'filename' => $filename,
                'current_keyword' => null,
            ]);
        } catch (\Throwable $e) {
            $export->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Turn one matched product into a single-sheet Excel row, with the
     * search keyword in the first column followed by the selected columns.
     */
    protected function formatRow($product, string $keyword): array
    {
        $row = [trim($keyword)];

        foreach ($this->columns as $key) {
            if ($key === 'date') {
                $row[] = $product->date ? date('m/d/Y', strtotime($product->date)) : '';
            } else {
                $row[] = $product->{$key} ?? '';
            }
        }

        return $row;
    }

    /**
     * Tolerant token-based search: normalize input, split into tokens,
     * require ALL tokens to match at least one searchable field.
     */
    private function applyTolerantSearch($query, string $searchValue): void
    {
        $normalized = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $searchValue));
        $tokens = array_filter(explode(' ', $normalized));

        if (empty($tokens)) {
            return;
        }

        $searchableFields = ['ref', 'designation', 'marque', 'fournisseur'];

        $query->where(function ($q) use ($tokens, $searchableFields) {
            foreach ($tokens as $token) {
                $q->where(function ($subQ) use ($token, $searchableFields) {
                    foreach ($searchableFields as $field) {
                        $subQ->orWhere($field, 'LIKE', '%'.$token.'%');
                    }
                });
            }
        });
    }
}
