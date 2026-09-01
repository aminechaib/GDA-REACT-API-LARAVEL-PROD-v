<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class MultiSearchExport implements FromArray, WithHeadings, WithMapping, WithTitle
{
    protected array $rows;

    protected array $columns;

    protected array $columnKeys;

    public function __construct(array $rows, array $columns)
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

        // Rows are already mapped to their final row arrays by the caller;
        // here we just keep them plus the column definitions for headings.
        $this->rows = $rows;
        $this->columns = array_filter($allColumns, fn ($key) => in_array($key, $columns), ARRAY_FILTER_USE_KEY);
        $this->columnKeys = array_keys($this->columns);
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return array_merge(['Search Value'], array_values($this->columns));
    }

    public function map($row): array
    {
        // $row is already a ready-to-write array: [searchValue, col1, col2, ...]
        return $row;
    }

    public function title(): string
    {
        return 'Search Results';
    }
}
