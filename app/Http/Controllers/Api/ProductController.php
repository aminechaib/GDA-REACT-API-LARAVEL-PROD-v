<?php

namespace App\Http\Controllers\Api;

use App\Exports\ProductsExport;
use App\Http\Controllers\Controller; // Make sure Product model is imported
use App\Imports\ProductsImport;
use App\Jobs\MultiSearchExportJob;
use App\Models\Product;
use App\Models\ProductExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class ProductController extends Controller
{
    /**
     * Display a paginated listing of the resource for AG Grid.
     * Handles pagination, sorting, and filtering.
     */
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $rowsPerPage = $request->input('rowsPerPage', 100);
        $sortBy = $request->input('sortBy', 'id');
        $sortType = $request->input('sortType', 'asc');

        $query = Product::query();

        // Handle date_display sort (frontend computed, map to date)
        if ($sortBy === 'date_display') {
            $sortBy = 'date';
        }

        // Quick search fields
        if ($request->filled('ref')) {
            $query->where('ref', 'LIKE', '%'.$request->input('ref').'%');
        }
        if ($request->filled('marque')) {
            $query->where('marque', 'LIKE', '%'.$request->input('marque').'%');
        }
        if ($request->filled('date')) {
            $this->applyDateFilter($query, $request->input('date'));
        }
        if ($request->filled('fournisseur')) {
            $query->where('fournisseur', 'LIKE', '%'.$request->input('fournisseur').'%');
        }
        if ($request->filled('designation')) {
            $query->where('designation', 'LIKE', '%'.$request->input('designation').'%');
        }

        // Global search (searchValue) — tolerant token-based matching
        if ($request->filled('searchValue')) {
            $this->applyTolerantSearch($query, $request->input('searchValue'));
        }

        // Advanced filters
        if ($request->filled('dateFrom')) {
            $query->whereDate('date', '>=', $request->input('dateFrom'));
        }
        if ($request->filled('dateTo')) {
            $query->whereDate('date', '<=', $request->input('dateTo'));
        }
        if ($request->filled('minPrice')) {
            $query->where('prix', '>=', floatval($request->input('minPrice')));
        }
        if ($request->filled('maxPrice')) {
            $query->where('prix', '<=', floatval($request->input('maxPrice')));
        }

        // Get total count AFTER filters
        $total = $query->count();

        $products = $query->orderBy($sortBy, $sortType)
            ->skip(($page - 1) * $rowsPerPage)
            ->take($rowsPerPage)
            ->get();

        return response()->json([
            'rows' => $products,
            'total' => $total,
        ]);
    }

    /**
     * Handles Excel import with column mapping from frontend.
     */
    public function importMapping(Request $request)
    {
        $request->validate([
            'data' => 'required|array|min:1',
            'mapping' => 'required|array',
            'fournisseur_id' => 'nullable|integer',
            'new_fournisseur' => 'nullable|string|max:255',
            'date' => 'nullable|date',
        ]);

        $data = $request->data;
        $mapping = $request->mapping;
        $fournisseurId = $request->fournisseur_id;
        $newFournisseur = $request->new_fournisseur;
        $date = $request->date ?: now()->format('Y-m-d');

        // Get fournisseur name from ID (match controller logic)
        $fournisseurName = $newFournisseur;
        if (! $fournisseurName && $fournisseurId) {
            $allFournisseurs = Product::select('fournisseur as nom')
                ->whereNotNull('fournisseur')
                ->where('fournisseur', '!=', '')
                ->distinct()
                ->orderBy('fournisseur')
                ->get()
                ->values();

            $selected = $allFournisseurs->get($fournisseurId - 1); // Index-based ID
            $fournisseurName = $selected?->nom ?? '';
        }

        // Transform data based on mapping
        $transformedData = [];
        foreach ($data as $row) {
            $transformedRow = [
                'ref' => $row[$mapping['ref']] ?? '',
                'designation' => $row[$mapping['designation']] ?? '',
                'marque' => $row[$mapping['marque']] ?? '',
                'prix' => floatval(str_replace(',', '.', $row[$mapping['prix']] ?? 0)),
                'fournisseur' => $fournisseurName,
                'date' => $date,
            ];

            if (! empty($transformedRow['ref'])) {
                $transformedData[] = $transformedRow;
            }
        }

        try {
            foreach ($transformedData as $row) {
                Product::create($row);
            }

            return response()->json([
                'message' => count($transformedData).' products imported/updated successfully!',
                'imported' => count($transformedData),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Import failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function simpleIndex(Request $request)
    {
        $page = $request->input('page', 1);
        $rowsPerPage = $request->input('rowsPerPage', 100);
        $sortBy = $request->input('sortBy', 'id');
        $sortType = $request->input('sortType', 'asc');

        $query = Product::query();

        // Handle date_display sort
        if ($sortBy === 'date_display') {
            $sortBy = 'date';
        }

        // Global search (searchValue) — tolerant token-based matching
        if ($request->has('searchValue') && ! empty($request->input('searchValue'))) {
            $this->applyTolerantSearch($query, $request->input('searchValue'));
        }

        // Get total count before pagination
        $total = $query->count();

        // Apply sorting and pagination
        $products = $query->orderBy($sortBy, $sortType)
            ->skip(($page - 1) * $rowsPerPage)
            ->take($rowsPerPage)
            ->get();

        return response()->json([
            'rows' => $products,
            'total' => $total,
        ]);
    }

    /**
     * Excel file import using ProductsImport class.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10000',
        ]);

        try {
            Excel::import(new ProductsImport, $request->file('file'));

            return response()->json([
                'message' => 'Excel import completed successfully!',
            ], 200);
        } catch (ValidationException $e) {
            $failures = $e->failures();

            return response()->json([
                'message' => 'Import completed with errors',
                'errors' => $failures,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Import failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export filtered or all products to Excel.
     * Reuses exact index() filter logic for consistency.
     * Uses chunked FromQuery export for large datasets.
     */
    public function export(Request $request)
    {
        $query = Product::query();

        // Quick search fields (exact copy from index)
        if ($request->filled('ref')) {
            $query->where('ref', 'LIKE', '%'.$request->input('ref').'%');
        }
        if ($request->filled('marque')) {
            $query->where('marque', 'LIKE', '%'.$request->input('marque').'%');
        }
        if ($request->filled('date')) {
            $this->applyDateFilter($query, $request->input('date'));
        }
        if ($request->filled('fournisseur')) {
            $query->where('fournisseur', 'LIKE', '%'.$request->input('fournisseur').'%');
        }
        if ($request->filled('designation')) {
            $query->where('designation', 'LIKE', '%'.$request->input('designation').'%');
        }

        // Global search (searchValue) — tolerant token-based matching
        if ($request->filled('searchValue')) {
            $this->applyTolerantSearch($query, $request->input('searchValue'));
        }

        // Advanced filters (exact copy)
        if ($request->filled('dateFrom')) {
            $query->whereDate('date', '>=', $request->input('dateFrom'));
        }
        if ($request->filled('dateTo')) {
            $query->whereDate('date', '<=', $request->input('dateTo'));
        }
        if ($request->filled('minPrice')) {
            $query->where('prix', '>=', floatval($request->input('minPrice')));
        }
        if ($request->filled('maxPrice')) {
            $query->where('prix', '<=', floatval($request->input('maxPrice')));
        }

        // Sort by ID asc for consistency
        $query->orderBy('id', 'asc');

        $filename = 'products_export_'.now()->format('Y-m-d_H-i-s').'.xlsx';

        return Excel::download(new ProductsExport($query), $filename);
    }

    /**
     * Start a queued multi-keyword search export.
     * Creates a ProductExport record and dispatches a background job
     * so huge datasets don't time out the HTTP request.
     */
    public function multiSearchExport(Request $request)
    {
        $request->validate([
            'keywords' => 'required|array|min:1|max:100',
            'keywords.*' => 'required|string|min:1|max:255',
            'columns' => 'required|array|min:1',
            'columns.*' => 'required|in:id,ref,designation,marque,prix,date,fournisseur',
            'max_results' => 'nullable|integer|min:1|max:100000',
        ]);

        $export = ProductExport::create([
            'user_id' => $request->user()?->id,
            'status' => 'pending',
            'keyword_count' => count($request->keywords),
            'max_results' => $request->max_results,
        ]);

        MultiSearchExportJob::dispatch($export->id, $request->keywords, $request->columns, $request->max_results);

        return response()->json([
            'export_id' => $export->id,
            'status' => $export->status,
        ], 202);
    }

    /**
     * Poll the status of a multi-search export.
     */
    public function multiSearchExportStatus($id)
    {
        $export = ProductExport::findOrFail($id);

        return response()->json([
            'export_id' => $export->id,
            'status' => $export->status,
            'keyword_count' => $export->keyword_count,
            'keyword_index' => $export->keyword_index,
            'current_keyword' => $export->current_keyword,
            'matched_rows' => $export->matched_rows,
            'max_results' => $export->max_results,
            'error' => $export->error,
            'filename' => $export->filename,
            'download_url' => $export->status === 'completed'
                ? url('/api/products/multi-search-export/'.$export->id.'/download')
                : null,
        ]);
    }

    /**
     * Download a completed multi-search export file.
     */
    public function multiSearchExportDownload($id)
    {
        $export = ProductExport::findOrFail($id);

        if ($export->status !== 'completed' || ! $export->file_path) {
            return response()->json(['message' => 'Export not ready.'], 404);
        }

        $path = Storage::disk('local')->path($export->file_path);

        if (! Storage::disk('local')->exists($export->file_path)) {
            return response()->json(['message' => 'Export file missing.'], 404);
        }

        return response()->download($path, $export->filename);
    }

    /**
     * Update a product.
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'ref' => 'nullable|string|max:255',
            'designation' => 'nullable|string|max:1000',
            'marque' => 'nullable|string|max:255',
            'prix' => 'nullable|numeric|min:0',
            'fournisseur' => 'nullable|string|max:255',
            'date' => 'nullable|date',
        ]);

        $product->update($validated);

        return response()->json($product);
    }

    /**
     * Delete a single product.
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Product deleted.']);
    }

    /**
     * Bulk delete products by IDs.
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:products,id',
        ]);

        Product::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => count($request->ids).' products deleted.']);
    }

    /**
     * Apply flexible date filter.
     * Supports: "2026" (year only), "2026-03" (year-month), or "2026-03-15" (full date).
     */
    private function applyDateFilter($query, string $dateInput): void
    {
        $dateInput = trim($dateInput);

        if (preg_match('/^\d{4}$/', $dateInput)) {
            $query->whereRaw('YEAR(date) = ?', [$dateInput]);
        } elseif (preg_match('/^\d{4}-\d{2}$/', $dateInput)) {
            $parts = explode('-', $dateInput);
            $query->whereRaw('YEAR(date) = ? AND MONTH(date) = ?', [$parts[0], intval($parts[1])]);
        } else {
            $query->whereDate('date', $dateInput);
        }
    }

    /**
     * Apply tolerant token-based search across multiple fields.
     *
     * Normalizes input (lowercase, strips special chars), splits into tokens,
     * and requires ALL tokens to match at least one searchable field.
     * Order of tokens does not matter.
     *
     * Example: "plaq ar tigu" matches "PLAQUETTE FREIN AR TIGUAN"
     */
    private function applyTolerantSearch($query, string $searchValue): void
    {
        // Normalize: lowercase, keep only letters, numbers, and spaces
        $normalized = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $searchValue));

        // Tokenize by whitespace
        $tokens = array_filter(explode(' ', $normalized));

        if (empty($tokens)) {
            return;
        }

        $searchableFields = ['ref', 'designation', 'marque', 'fournisseur'];

        // Each token must match at least one field (OR within token)
        // All tokens must match (AND across tokens)
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
