<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class MultiSearchExport implements WithMultipleSheets
{
    protected array $sheets = [];

    public function __construct(array $keywordQueries, array $columns)
    {
        $allColumns = [
            'id' => 'ID',
            'ref' => 'Reference',
            'designation' => 'Designation',
            'marque' => 'Marque',
            'prix' => 'Prix',
            'date' => 'Date',
            'fournisseur' => 'Fournisseur',
        ];

        $selectedColumns = array_filter($allColumns, fn ($key) => in_array($key, $columns), ARRAY_FILTER_USE_KEY);

        foreach ($keywordQueries as $keyword => $query) {
            $this->sheets[] = new KeywordSheet(
                $keyword,
                $query,
                $selectedColumns
            );
        }
    }

    public function sheets(): array
    {
        return $this->sheets;
    }
}

class KeywordSheet implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    protected string $keyword;

    protected $query;

    protected array $columns;

    protected array $columnKeys;

    public function __construct(string $keyword, $query, array $columns)
    {
        $this->keyword = $keyword;
        $this->query = $query;
        $this->columns = $columns;
        $this->columnKeys = array_keys($columns);
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return array_values($this->columns);
    }

    public function map($product): array
    {
        $row = [];
        foreach ($this->columnKeys as $key) {
            if ($key === 'date') {
                $row[] = $product->date ? date('m/d/Y', strtotime($product->date)) : '';
            } else {
                $row[] = $product->{$key} ?? '';
            }
        }

        return $row;
    }

    public function title(): string
    {
        $title = mb_substr($this->keyword, 0, 31);
        $title = str_replace(['\\', '/', '*', '?', '[', ']', ':'], '', $title);

        return $title ?: 'Results';
    }
}
