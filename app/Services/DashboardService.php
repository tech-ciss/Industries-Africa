<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private const OPEN = ['new', 'assigned', 'in_progress'];

    /**
     * Statistiques du tableau de bord, limitées aux tickets visibles par l'utilisateur
     * (admin = tout, technicien = ses affectations, employé = ses demandes).
     */
    public function statsFor(User $user): array
    {
        $visible = fn () => Ticket::visibleTo($user);

        // Une seule requête pour le total, les ouverts, les résolus et la répartition.
        $byStatus = array_fill_keys(array_keys(Ticket::STATUS_LABELS), 0);
        $counts = $visible()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        foreach ($counts as $status => $total) {
            $byStatus[$status] = (int) $total;
        }

        $avgHours = $this->averageResolutionHours($user);

        $stats = [
            'total' => array_sum($byStatus),
            'open' => $byStatus['new'] + $byStatus['assigned'] + $byStatus['in_progress'],
            'resolved' => $byStatus['resolved'] + $byStatus['closed'],
            // Critiques = priorité critique ET encore à traiter.
            'critical' => $visible()->where('priority', 'critical')->whereIn('status', self::OPEN)->count(),
            'overdue' => $visible()->whereNotNull('deadline')->where('deadline', '<', now())
                ->whereIn('status', self::OPEN)->count(),
            'unassigned' => $byStatus['new'],
            'technicians' => User::where('role', 'technician')->where('status', 'active')->count(),
            'by_status' => $byStatus,
            'by_category' => $this->byCategory($user),
            'avg_resolution_hours' => $avgHours,
            'avg_resolution_label' => self::formatHours($avgHours),
            // Réservés à un rôle (null pour les autres).
            'workload' => null,
            'due_soon' => null,
            'time_this_month' => null,
        ];

        if ($user->isAdmin()) {
            $stats['workload'] = $this->technicianWorkload();
        }

        if ($user->isTechnician()) {
            $stats['due_soon'] = $visible()->whereNotNull('deadline')
                ->whereBetween('deadline', [now(), now()->addDays(7)])
                ->whereIn('status', self::OPEN)->count();
            $stats['time_this_month'] = (int) TimeEntry::where('user_id', $user->id)
                ->where('created_at', '>=', now()->startOfMonth())
                ->sum('minutes');
        }

        return $stats;
    }

    public function recentTickets(User $user, int $limit = 10): Collection
    {
        return Ticket::visibleTo($user)->with(['user', 'category', 'assignee'])->latest()->take($limit)->get();
    }

    /** Temps moyen de résolution (en heures) des tickets visibles par l'utilisateur. */
    public function averageResolutionHours(User $user): ?float
    {
        return $this->averageHours(Ticket::visibleTo($user));
    }

    /** Temps moyen de résolution (en heures) d'un ensemble de tickets, calculé par la base de données. */
    public function averageHours(Builder $tickets): ?float
    {
        $hours = match (DB::connection()->getDriverName()) {
            'sqlite' => '(julianday(resolved_at) - julianday(created_at)) * 24',
            'pgsql' => 'EXTRACT(EPOCH FROM (resolved_at - created_at)) / 3600',
            'sqlsrv' => 'DATEDIFF(MINUTE, created_at, resolved_at) / 60.0',
            default => 'TIMESTAMPDIFF(MINUTE, created_at, resolved_at) / 60', // MySQL / MariaDB
        };

        $avg = (clone $tickets)
            ->whereNotNull('resolved_at')
            ->selectRaw("AVG({$hours}) as avg_hours")
            ->value('avg_hours');

        return $avg === null ? null : round((float) $avg, 2);
    }

    /** 6.5 -> "6 h 30", 0.5 -> "30 min", 54 -> "2 j 6 h", null -> "N/A". */
    public static function formatHours(?float $hours): string
    {
        if ($hours === null) {
            return 'N/A';
        }

        $minutes = (int) round($hours * 60);
        if ($minutes < 48 * 60) {
            return TimeEntry::format($minutes);
        }

        $days = intdiv($minutes, 1440);
        $restHours = intdiv($minutes % 1440, 60);

        return $restHours === 0 ? "{$days} j" : "{$days} j {$restHours} h";
    }

    private function byCategory(User $user, int $limit = 5): \Illuminate\Support\Collection
    {
        return Category::withCount(['tickets as tickets_count' => fn ($q) => $q->visibleTo($user)])
            ->orderByDesc('tickets_count')
            ->get()
            ->filter(fn ($c) => $c->tickets_count > 0)
            ->take($limit)
            ->values();
    }

    /** Techniciens actifs avec leur nombre de tickets en cours (assignés + en cours). */
    private function technicianWorkload(): Collection
    {
        return User::where('role', 'technician')
            ->where('status', 'active')
            ->withCount(['assignedTickets as open_tickets_count' => fn ($q) => $q->whereIn('status', ['assigned', 'in_progress'])])
            ->orderByDesc('open_tickets_count')
            ->orderBy('name')
            ->get();
    }
}
