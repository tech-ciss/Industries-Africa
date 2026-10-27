<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HistoryAndTimeTest extends TestCase
{
    use RefreshDatabase;

    private function newTicket(array $overrides = []): Ticket
    {
        return Ticket::factory()->create($overrides + [
            'status' => 'new',
            'title' => 'Titre initial',
            'description' => 'Description initiale assez longue pour la validation.',
            'priority' => 'normal',
        ]);
    }

    // ---------- Historique des modifications ----------

    public function test_updating_a_ticket_logs_only_the_changed_fields(): void
    {
        $ticket = $this->newTicket();

        $this->actingAs($ticket->user)->put(route('tickets.update', $ticket), [
            'title' => 'Titre modifié',
            'description' => $ticket->description,
            'category_id' => $ticket->category_id,
            'priority' => 'high',
        ])->assertRedirect(route('tickets.show', $ticket));

        $activity = Activity::where('ticket_id', $ticket->id)->where('action', 'updated')->first();

        $this->assertNotNull($activity);
        $this->assertStringContainsString('titre', $activity->description);
        $this->assertStringContainsString('priorité', $activity->description);
        $this->assertStringNotContainsString('description', $activity->description);
        $this->assertSame('Titre modifié', $ticket->fresh()->title);
    }

    public function test_updating_without_changes_logs_nothing(): void
    {
        $ticket = $this->newTicket();

        $this->actingAs($ticket->user)->put(route('tickets.update', $ticket), [
            'title' => $ticket->title,
            'description' => $ticket->description,
            'category_id' => $ticket->category_id,
            'priority' => $ticket->priority,
        ]);

        $this->assertDatabaseCount('activities', 0);
    }

    public function test_api_update_is_logged_too(): void
    {
        $ticket = $this->newTicket();
        Sanctum::actingAs($ticket->user);

        $this->putJson("/api/tickets/{$ticket->id}", ['priority' => 'critical'])->assertOk();

        $this->assertDatabaseHas('activities', ['ticket_id' => $ticket->id, 'action' => 'updated']);
    }

    public function test_status_change_history_uses_readable_labels(): void
    {
        $tech = User::factory()->technician()->create();
        $ticket = $this->newTicket(['status' => 'assigned', 'assigned_to' => $tech->id]);

        $this->actingAs($tech)->post(route('tickets.status', $ticket), ['status' => 'in_progress']);

        $activity = Activity::where('ticket_id', $ticket->id)->where('action', 'status_changed')->first();
        $this->assertStringContainsString('Assigné', $activity->description);
        $this->assertStringContainsString('En cours', $activity->description);
        $this->assertSame('Changement de statut', $activity->action_label);
    }

    // ---------- Temps passé ----------

    public function test_assigned_technician_can_log_time_on_a_ticket_in_progress(): void
    {
        $tech = User::factory()->technician()->create();
        $ticket = $this->newTicket(['status' => 'in_progress', 'assigned_to' => $tech->id]);

        $this->actingAs($tech)->post(route('tickets.time-entries.store', $ticket), [
            'minutes' => 90,
            'note' => 'Remplacement du compresseur',
        ])->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('time_entries', [
            'ticket_id' => $ticket->id,
            'user_id' => $tech->id,
            'minutes' => 90,
        ]);
        $this->assertDatabaseHas('activities', ['ticket_id' => $ticket->id, 'action' => 'time_logged']);
    }

    public function test_total_time_adds_up_all_entries(): void
    {
        $ticket = $this->newTicket(['status' => 'in_progress']);
        TimeEntry::factory()->create(['ticket_id' => $ticket->id, 'minutes' => 30]);
        TimeEntry::factory()->create(['ticket_id' => $ticket->id, 'minutes' => 45]);

        $this->assertSame(75, $ticket->totalTimeMinutes());
        $this->assertSame('1 h 15', TimeEntry::format(75));
        $this->assertSame('45 min', TimeEntry::format(45));
        $this->assertSame('2 h', TimeEntry::format(120));
    }

    public function test_only_the_assigned_technician_can_log_time(): void
    {
        $assigned = User::factory()->technician()->create();
        $otherTech = User::factory()->technician()->create();
        $admin = User::factory()->admin()->create();
        $ticket = $this->newTicket(['status' => 'in_progress', 'assigned_to' => $assigned->id]);

        foreach ([$otherTech, $admin, $ticket->user] as $intruder) {
            $this->actingAs($intruder)
                ->post(route('tickets.time-entries.store', $ticket), ['minutes' => 30])
                ->assertForbidden();
        }

        $this->assertDatabaseCount('time_entries', 0);
    }

    public function test_cannot_log_time_before_work_starts_or_after_closing(): void
    {
        $tech = User::factory()->technician()->create();

        foreach (['assigned', 'closed'] as $status) {
            $ticket = $this->newTicket(['status' => $status, 'assigned_to' => $tech->id]);

            $this->actingAs($tech)
                ->post(route('tickets.time-entries.store', $ticket), ['minutes' => 30])
                ->assertForbidden();
        }

        $this->assertDatabaseCount('time_entries', 0);
    }

    public function test_time_entry_is_validated(): void
    {
        $tech = User::factory()->technician()->create();
        $ticket = $this->newTicket(['status' => 'in_progress', 'assigned_to' => $tech->id]);

        foreach ([0, -5, 1441, 'abc', null] as $invalid) {
            $this->actingAs($tech)
                ->post(route('tickets.time-entries.store', $ticket), ['minutes' => $invalid])
                ->assertSessionHasErrors('minutes');
        }

        $this->assertDatabaseCount('time_entries', 0);
    }

    public function test_ticket_page_shows_time_and_history_labels(): void
    {
        $tech = User::factory()->technician()->create();
        $ticket = $this->newTicket(['status' => 'in_progress', 'assigned_to' => $tech->id]);
        TimeEntry::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $tech->id, 'minutes' => 90]);
        Activity::create([
            'ticket_id' => $ticket->id, 'user_id' => $tech->id,
            'action' => 'status_changed', 'description' => 'Statut changé : Assigné → En cours.',
        ]);

        $this->actingAs($tech)->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Temps passé')
            ->assertSee('1 h 30')
            ->assertSee('Changement de statut')
            ->assertSee('En cours');
    }

    // ---------- Prénom ----------

    public function test_admin_must_provide_a_first_name_when_creating_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $payload = [
            'name' => 'Kouassi',
            'email' => 'nouveau@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'technician',
            'status' => 'active',
        ];

        $this->actingAs($admin)->post(route('users.store'), $payload)
            ->assertSessionHasErrors('first_name');

        $this->actingAs($admin)->post(route('users.store'), $payload + ['first_name' => 'Awa'])
            ->assertRedirect(route('users.index'));

        $user = User::where('email', 'nouveau@example.com')->first();
        $this->assertSame('Awa', $user->first_name);
        $this->assertSame('Awa Kouassi', $user->full_name);
    }
}
