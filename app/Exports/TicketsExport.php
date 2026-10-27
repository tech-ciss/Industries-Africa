<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Export Excel à deux feuilles : "Incidents" (liste filtrée) et "Statistiques" (sur les mêmes tickets).
 */
class TicketsExport implements Export, WithMultipleSheets
{
    public function __construct(private array $filters = []) {}

    public function sheets(): array
    {
        return [
            new IncidentsSheet($this->filters),
            new StatisticsSheet($this->filters),
        ];
    }
}
