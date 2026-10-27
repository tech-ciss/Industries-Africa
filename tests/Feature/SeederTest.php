<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_seeder_meets_the_minimum_dataset(): void
    {
        $this->assertGreaterThanOrEqual(10, User::count());
        $this->assertSame(1, User::where('role', 'admin')->count());
        $this->assertSame(5, User::where('role', 'technician')->where('status', 'active')->count());
        $this->assertSame(10, User::where('role', 'employee')->where('status', 'active')->count());
        $this->assertSame(1, User::where('status', 'inactive')->count());
        $this->assertSame(0, User::whereNull('first_name')->count());
        $this->assertSame(5, \App\Models\Category::count());
        $this->assertSame(50, Ticket::count());
    }

    public function test_status_distribution_matches_the_plan(): void
    {
        $counts = Ticket::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->all();

        $this->assertEquals(
            ['new' => 8, 'assigned' => 8, 'in_progress' => 10, 'resolved' => 8, 'closed' => 16],
            $counts
        );
    }

    public function test_tickets_are_consistent_with_the_workflow(): void
    {
        foreach (Ticket::with('assignee')->get() as $t) {
            if ($t->status === 'new') {
                $this->assertNull($t->assigned_to, "Ticket {$t->id} nouveau avec technicien");
                $this->assertNull($t->deadline);
            } else {
                $this->assertNotNull($t->assigned_to, "Ticket {$t->id} sans technicien");
                $this->assertSame('technician', $t->assignee->role);
                $this->assertSame('active', $t->assignee->status);
                $this->assertTrue($t->deadline->gt($t->created_at), "Ticket {$t->id} : échéance avant création");
            }

            if (in_array($t->status, ['resolved', 'closed'], true)) {
                $this->assertNotNull($t->resolved_at);
                $this->assertTrue($t->resolved_at->gt($t->created_at));
            } else {
                $this->assertNull($t->resolved_at);
            }

            $this->assertFalse($t->updated_at->lt($t->created_at));
            $this->assertTrue($t->updated_at->lte(now()->addMinute()), "Ticket {$t->id} daté dans le futur");
        }
    }

    public function test_history_matches_each_ticket_status(): void
    {
        $statusChanges = ['new' => 0, 'assigned' => 0, 'in_progress' => 1, 'resolved' => 2, 'closed' => 3];

        foreach (Ticket::all() as $t) {
            $actions = Activity::where('ticket_id', $t->id)->pluck('action');

            $this->assertSame(1, $actions->filter(fn ($a) => $a === 'created')->count());
            $this->assertSame($t->status === 'new' ? 0 : 1, $actions->filter(fn ($a) => $a === 'assigned')->count());
            $this->assertSame($statusChanges[$t->status], $actions->filter(fn ($a) => $a === 'status_changed')->count());
        }
    }

    public function test_time_entries_only_exist_once_work_has_started(): void
    {
        foreach (Ticket::all() as $t) {
            $entries = TimeEntry::where('ticket_id', $t->id)->get();

            if (in_array($t->status, ['new', 'assigned'], true)) {
                $this->assertCount(0, $entries);
            } else {
                $this->assertGreaterThanOrEqual(1, $entries->count());
                $this->assertTrue($entries->every(fn ($e) => $e->user_id === $t->assigned_to));
            }
        }
        $this->assertSame(TimeEntry::count(), Activity::where('action', 'time_logged')->count());
    }

    public function test_comments_come_from_the_requester_or_the_assigned_technician(): void
    {
        foreach (Comment::with('ticket')->get() as $c) {
            $this->assertContains($c->user_id, [$c->ticket->user_id, $c->ticket->assigned_to]);
            $this->assertTrue($c->created_at->gte($c->ticket->created_at));
        }
        $this->assertSame(Comment::count(), Activity::where('action', 'commented')->count());
    }

    public function test_seeded_data_gives_a_realistic_average_resolution_time(): void
    {
        $admin = User::where('role', 'admin')->first();

        $hours = app(DashboardService::class)->averageResolutionHours($admin);

        $this->assertNotNull($hours);
        $this->assertGreaterThan(2, $hours);
        $this->assertLessThan(120, $hours);
    }

    public function test_seeded_accounts_can_log_in_except_the_inactive_one(): void
    {
        foreach (['admin@maintenance.test', 'technicien1@maintenance.test', 'employe1@maintenance.test'] as $email) {
            $this->post('/login', ['email' => $email, 'password' => 'password']);
            $this->assertAuthenticated();
            $this->post('/logout');
        }

        $this->post('/login', ['email' => 'inactif@maintenance.test', 'password' => 'password']);
        $this->assertGuest();
    }

    public function test_factory_states_are_workflow_consistent(): void
    {
        $assigned = Ticket::factory()->assigned()->create();
        $closed = Ticket::factory()->closed()->create();
        $new = Ticket::factory()->create();

        $this->assertSame('assigned', $assigned->status);
        $this->assertSame('technician', $assigned->assignee->role);
        $this->assertNotNull($assigned->deadline);
        $this->assertNull($assigned->resolved_at);

        $this->assertSame('closed', $closed->status);
        $this->assertNotNull($closed->resolved_at);
        $this->assertNotNull($closed->assigned_to);

        $this->assertSame('new', $new->status);
        $this->assertNull($new->assigned_to);
    }
}
