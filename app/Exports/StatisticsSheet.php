<?php

namespace App\Exports;

use App\Models\Ticket;
use App\Services\ReportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class StatisticsSheet implements FromCollection, ShouldAutoSize, WithTitle
{
    public function __construct(private array $filters = []) {}

    public function title(): string
    {
        return 'Statistiques';
    }

    public function collection(): Collection
    {
        $stats = app(ReportService::class)->statistics(Ticket::query()->filter($this->filters));

        $rows = [
            ['Statistiques des incidents', ''],
            ['Période', $this->periodLabel()],
            ['Généré le', now()->format('d/m/Y H:i')],
            [''],
            ['Indicateur', 'Valeur'],
            ['Total incidents', $stats['total']],
            ['Ouverts', $stats['open']],
            ['Résolus ou fermés', $stats['resolved']],
            ['Critiques ouverts', $stats['critical']],
            ['En retard', $stats['overdue']],
            ['Temps moyen de résolution', $stats['avg_resolution_label']],
            ['Temps passé total', $stats['time_spent_label']],
            [''],
            ['Répartition par statut', 'Nombre'],
        ];
        foreach ($stats['by_status'] as $status => $count) {
            $rows[] = [Ticket::STATUS_LABELS[$status], $count];
        }

        $rows[] = [''];
        $rows[] = ['Répartition par priorité', 'Nombre'];
        foreach ($stats['by_priority'] as $priority => $count) {
            $rows[] = [Ticket::PRIORITY_LABELS[$priority], $count];
        }

        $rows[] = [''];
        $rows[] = ['Répartition par catégorie', 'Nombre'];
        foreach ($stats['by_category'] as $name => $count) {
            $rows[] = [$name, $count];
        }

        $rows[] = [''];
        $rows[] = ['Technicien', 'Tickets assignés', 'Résolus ou fermés'];
        foreach ($stats['by_technician'] as $tech) {
            $rows[] = [$tech['name'], $tech['assigned'], $tech['done']];
        }

        return collect($rows);
    }

    private function periodLabel(): string
    {
        $from = $this->filters['date_from'] ?? null;
        $to = $this->filters['date_to'] ?? null;

        return match (true) {
            $from && $to => "Du {$this->date($from)} au {$this->date($to)}",
            (bool) $from => "À partir du {$this->date($from)}",
            (bool) $to => "Jusqu'au {$this->date($to)}",
            default => 'Toutes les périodes',
        };
    }

    private function date(string $value): string
    {
        return \Carbon\Carbon::parse($value)->format('d/m/Y');
    }
}
