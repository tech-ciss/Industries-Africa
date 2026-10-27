<?php

namespace App\Exports;

use App\Models\Ticket;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class IncidentsSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(private array $filters = []) {}

    public function title(): string
    {
        return 'Incidents';
    }

    public function headings(): array
    {
        return [
            'ID', 'Titre', 'Catégorie', 'Priorité', 'Statut', 'Demandeur', 'Technicien',
            'Créé le', 'Échéance', 'Résolu le', 'Temps passé (min)',
        ];
    }

    public function collection(): Collection
    {
        return Ticket::query()
            ->filter($this->filters)
            ->with(['user', 'category', 'assignee'])
            ->withSum('timeEntries as time_spent', 'minutes')
            ->orderBy('created_at')
            ->get()
            ->map(fn (Ticket $t) => [
                $t->id,
                $t->title,
                $t->category?->name,
                $t->priority_label,
                $t->status_label,
                $t->user?->full_name,
                $t->assignee?->full_name,
                $t->created_at?->format('d/m/Y H:i'),
                $t->deadline?->format('d/m/Y'),
                $t->resolved_at?->format('d/m/Y H:i'),
                (int) $t->time_spent,
            ]);
    }
}
