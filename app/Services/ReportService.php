<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Calcule les statistiques et les données des rapports (Excel et PDF)
 * à partir d'un ensemble de tickets défini par une requête.
 */
class ReportService
{
    private const OPEN = ['new', 'assigned', 'in_progress'];

    public function __construct(private DashboardService $dashboard) {}

    /** Statistiques sur l'ensemble de tickets décrit par $tickets (ex. Ticket::query()->filter(...)). */
    public function statistics(Builder $tickets): array
    {
        $byStatus = array_fill_keys(array_keys(Ticket::STATUS_LABELS), 0);
        foreach ((clone $tickets)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status') as $status => $total) {
            $byStatus[$status] = (int) $total;
        }

        $byPriority = array_fill_keys(array_keys(Ticket::PRIORITY_LABELS), 0);
        foreach ((clone $tickets)->selectRaw('priority, COUNT(*) as total')->groupBy('priority')->pluck('total', 'priority') as $priority => $total) {
            $byPriority[$priority] = (int) $total;
        }

        $categoryNames = Category::pluck('name', 'id');
        $byCategory = [];
        $categoryCounts = (clone $tickets)->selectRaw('category_id, COUNT(*) as total')
            ->groupBy('category_id')->orderByDesc('total')->pluck('total', 'category_id');
        foreach ($categoryCounts as $categoryId => $total) {
            $byCategory[$categoryNames[$categoryId] ?? '—'] = (int) $total;
        }

        $avgHours = $this->dashboard->averageHours($tickets);
        $timeSpent = (int) TimeEntry::whereIn('ticket_id', (clone $tickets)->select('tickets.id'))->sum('minutes');

        return [
            'total' => array_sum($byStatus),
            'open' => $byStatus['new'] + $byStatus['assigned'] + $byStatus['in_progress'],
            'resolved' => $byStatus['resolved'] + $byStatus['closed'],
            'critical' => (clone $tickets)->where('priority', 'critical')->whereIn('status', self::OPEN)->count(),
            'overdue' => (clone $tickets)->whereNotNull('deadline')->where('deadline', '<', now())
                ->whereIn('status', self::OPEN)->count(),
            'avg_resolution_hours' => $avgHours,
            'avg_resolution_label' => DashboardService::formatHours($avgHours),
            'time_spent_minutes' => $timeSpent,
            'time_spent_label' => TimeEntry::format($timeSpent),
            'by_status' => $byStatus,
            'by_priority' => $byPriority,
            'by_category' => $byCategory,
            'by_technician' => $this->byTechnician($tickets),
        ];
    }

    /**
     * Données du rapport mensuel : tickets créés pendant le mois + tickets résolus pendant le mois.
     */
    public function monthly(Carbon $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $created = Ticket::query()->whereBetween('created_at', [$start, $end]);
        $resolvedInMonth = Ticket::query()->whereBetween('resolved_at', [$start, $end]);

        return [
            'month' => $start,
            'label' => ucfirst($start->copy()->locale('fr')->translatedFormat('F Y')),
            'generated_at' => now(),
            'stats' => $this->statistics($created),
            'resolved_in_period' => (clone $resolvedInMonth)->count(),
            'avg_resolution_period_label' => DashboardService::formatHours($this->dashboard->averageHours($resolvedInMonth)),
            'tickets' => (clone $created)->with(['user', 'category', 'assignee'])
                ->withSum('timeEntries as time_spent', 'minutes')
                ->orderBy('created_at')->get(),
        ];
    }

    /** Nombre de tickets assignés et terminés par technicien. */
    private function byTechnician(Builder $tickets): array
    {
        $rows = (clone $tickets)->whereNotNull('assigned_to')
            ->selectRaw("assigned_to, COUNT(*) as assigned, SUM(CASE WHEN status IN ('resolved', 'closed') THEN 1 ELSE 0 END) as done")
            ->groupBy('assigned_to')
            ->orderByDesc('assigned')
            ->get();

        $names = User::whereIn('id', $rows->pluck('assigned_to'))->get()->keyBy('id');

        return $rows->map(fn ($row) => [
            'name' => $names[$row->assigned_to]->full_name ?? '—',
            'assigned' => (int) $row->assigned,
            'done' => (int) $row->done,
        ])->all();
    }
}
