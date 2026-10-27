<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /** Ticket "neutre" : pas d'aléatoire sur la priorité, l'échéance ou la résolution. */
    private function ticket(array $attributes = []): Ticket
    {
        return Ticket::factory()->create($attributes + [
            'status' => 'new',
            'priority' => 'normal',
            'deadline' => null,
            'resolved_at' => null,
            'assigned_to' => null,
        ]);
    }

    private function stats(User $user): array
    {
        return $this->actingAs($user)->get('/dashboard')->assertOk()->viewData('stats');
    }

    public function test_admin_sees_global_counts(): void
    {
        $tech = User::factory()->technician()->create();
        $this->ticket(['status' => 'new']);
        $this->ticket(['status' => 'new']);
        $this->ticket(['status' => 'assigned', 'assigned_to' => $tech->id]);
        $this->ticket(['status' => 'in_progress', 'assigned_to' => $tech->id, 'priority' => 'critical']);
        $this->ticket(['status' => 'resolved', 'priority' => 'critical', 'resolved_at' => now()]);
        $this->ticket(['status' => 'closed', 'resolved_at' => now()]);

        $stats = $this->stats(User::factory()->admin()->create());

        $this->assertSame(6, $stats['total']);
        $this->assertSame(4, $stats['open']);
        $this->assertSame(2, $stats['resolved']);
        $this->assertSame(1, $stats['critical']); // seul le critique encore ouvert compte
        $this->assertSame(2, $stats['unassigned']);
        $this->assertSame(2, $stats['by_status']['new']);
        $this->assertSame(1, $stats['by_status']['closed']);
    }

    public function test_overdue_counts_only_open_tickets_past_their_deadline(): void
    {
        $this->ticket(['status' => 'in_progress', 'deadline' => now()->subDay()]);
        $this->ticket(['status' => 'assigned', 'deadline' => now()->addDays(2)]);
        $this->ticket(['status' => 'resolved', 'deadline' => now()->subDay(), 'resolved_at' => now()]);

        $stats = $this->stats(User::factory()->admin()->create());

        $this->assertSame(1, $stats['overdue']);
    }

    public function test_average_resolution_time_is_computed_in_hours(): void
    {
        $this->ticket(['status' => 'resolved', 'created_at' => '2026-01-01 08:00:00', 'resolved_at' => '2026-01-01 18:00:00']); // 10 h
        $this->ticket(['status' => 'closed', 'created_at' => '2026-01-01 08:00:00', 'resolved_at' => '2026-01-01 10:00:00']);   // 2 h
        $this->ticket(['status' => 'in_progress']); // ignoré : pas résolu

        $stats = $this->stats(User::factory()->admin()->create());

        $this->assertEqualsWithDelta(6.0, $stats['avg_resolution_hours'], 0.05);
        $this->assertSame('6 h', $stats['avg_resolution_label']);
    }

    public function test_average_resolution_is_null_without_resolved_tickets(): void
    {
        $this->ticket(['status' => 'in_progress']);

        $stats = $this->stats(User::factory()->admin()->create());

        $this->assertNull($stats['avg_resolution_hours']);
        $this->assertSame('N/A', $stats['avg_resolution_label']);
    }

    public function test_average_resolution_is_scoped_to_the_user(): void
    {
        $employee = User::factory()->create();
        $this->ticket(['user_id' => $employee->id, 'status' => 'resolved',
            'created_at' => '2026-01-01 08:00:00', 'resolved_at' => '2026-01-01 12:00:00']); // 4 h
        $this->ticket(['status' => 'resolved', // ticket d'un autre employé : 20 h
            'created_at' => '2026-01-01 08:00:00', 'resolved_at' => '2026-01-02 04:00:00']);

        $stats = $this->stats($employee);

        $this->assertEqualsWithDelta(4.0, $stats['avg_resolution_hours'], 0.05);
    }

    public function test_format_hours_is_human_readable(): void
    {
        $this->assertSame('N/A', DashboardService::formatHours(null));
        $this->assertSame('30 min', DashboardService::formatHours(0.5));
        $this->assertSame('6 h 30', DashboardService::formatHours(6.5));
        $this->assertSame('2 j 6 h', DashboardService::formatHours(54.0));
        $this->assertSame('3 j', DashboardService::formatHours(72.0));
    }

    public function test_technician_workload_is_admin_only_and_counts_open_tickets(): void
    {
        $techA = User::factory()->technician()->create();
        $inactive = User::factory()->technician()->create(['status' => 'inactive']);
        $this->ticket(['status' => 'assigned', 'assigned_to' => $techA->id]);
        $this->ticket(['status' => 'in_progress', 'assigned_to' => $techA->id]);
        $this->ticket(['status' => 'resolved', 'assigned_to' => $techA->id, 'resolved_at' => now()]);

        $adminStats = $this->stats(User::factory()->admin()->create());
        $row = $adminStats['workload']->firstWhere('id', $techA->id);

        $this->assertSame(2, $row->open_tickets_count);
        $this->assertNull($adminStats['workload']->firstWhere('id', $inactive->id));
        $this->assertNull($this->stats(User::factory()->create())['workload']);
        $this->assertNull($this->stats($techA)['workload']);
    }

    public function test_technician_gets_due_soon_and_time_this_month(): void
    {
        $tech = User::factory()->technician()->create();
        $this->ticket(['status' => 'in_progress', 'assigned_to' => $tech->id, 'deadline' => now()->addDays(3)]);
        $this->ticket(['status' => 'in_progress', 'assigned_to' => $tech->id, 'deadline' => now()->addDays(10)]);
        $this->ticket(['status' => 'in_progress', 'assigned_to' => $tech->id, 'deadline' => now()->subDay()]);

        TimeEntry::factory()->create(['user_id' => $tech->id, 'minutes' => 90]);
        TimeEntry::factory()->create(['user_id' => $tech->id, 'minutes' => 500, 'created_at' => now()->subMonths(2)]);

        $stats = $this->stats($tech);

        $this->assertSame(3, $stats['total']);
        $this->assertSame(1, $stats['due_soon']);
        $this->assertSame(1, $stats['overdue']);
        $this->assertSame(90, $stats['time_this_month']);
        $this->assertNull($this->stats(User::factory()->create())['due_soon']);
    }

    public function test_categories_only_count_visible_tickets(): void
    {
        $employee = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Plomberie']);
        $this->ticket(['user_id' => $employee->id, 'category_id' => $category->id]);
        $this->ticket(['category_id' => $category->id]); // autre employé

        $stats = $this->stats($employee);

        $this->assertCount(1, $stats['by_category']);
        $this->assertSame(1, $stats['by_category']->first()->tickets_count);
    }

    public function test_page_renders_for_each_role(): void
    {
        $tech = User::factory()->technician()->create();
        $this->ticket(['status' => 'in_progress', 'assigned_to' => $tech->id, 'deadline' => now()->addDay()]);

        $this->actingAs(User::factory()->admin()->create())->get('/dashboard')
            ->assertOk()->assertSee('Charge des techniciens')->assertSee('Non assignés');

        $this->actingAs($tech)->get('/dashboard')
            ->assertOk()->assertSee('Échéance sous 7 jours')->assertDontSee('Charge des techniciens');

        $this->actingAs(User::factory()->create())->get('/dashboard')
            ->assertOk()->assertSee('Répartition par statut')->assertDontSee('Charge des techniciens');
    }

    public function test_dashboard_works_with_no_tickets(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/dashboard')
            ->assertOk()->assertSee('Aucun ticket.');
    }
}
