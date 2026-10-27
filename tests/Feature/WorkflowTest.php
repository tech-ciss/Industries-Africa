<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketResolvedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_assigns_technician_and_ticket_becomes_assigned(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $tech = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create(['status' => 'new']);

        $this->actingAs($admin)->post(route('tickets.assign', $ticket), [
            'technician_id' => $tech->id,
            'deadline' => now()->addDays(3)->toDateString(),
        ])->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();
        $this->assertSame('assigned', $ticket->status);
        $this->assertSame($tech->id, $ticket->assigned_to);
        $this->assertDatabaseHas('activities', ['ticket_id' => $ticket->id, 'action' => 'assigned']);
        Notification::assertSentTo($tech, TicketAssignedNotification::class);
    }

    public function test_cannot_assign_a_non_technician_or_inactive_technician(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->create();
        $inactiveTech = User::factory()->technician()->create(['status' => 'inactive']);
        $ticket = Ticket::factory()->create(['status' => 'new']);

        foreach ([$employee, $inactiveTech] as $invalid) {
            $this->actingAs($admin)
                ->post(route('tickets.assign', $ticket), ['technician_id' => $invalid->id])
                ->assertSessionHasErrors('technician_id');
        }

        $this->assertNull($ticket->fresh()->assigned_to);
        $this->assertSame('new', $ticket->fresh()->status);
    }

    public function test_cannot_assign_with_a_past_deadline(): void
    {
        $admin = User::factory()->admin()->create();
        $tech = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create(['status' => 'new']);

        $this->actingAs($admin)->post(route('tickets.assign', $ticket), [
            'technician_id' => $tech->id,
            'deadline' => now()->subDay()->toDateString(),
        ])->assertSessionHasErrors('deadline');
    }

    public function test_only_admin_can_assign(): void
    {
        $tech = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create(['status' => 'new']);

        $this->actingAs($tech)
            ->post(route('tickets.assign', $ticket), ['technician_id' => $tech->id])
            ->assertForbidden();
    }

    public function test_cannot_reassign_a_ticket_already_in_progress(): void
    {
        $admin = User::factory()->admin()->create();
        $tech = User::factory()->technician()->create();
        $other = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create(['status' => 'in_progress', 'assigned_to' => $tech->id]);

        $this->actingAs($admin)
            ->post(route('tickets.assign', $ticket), ['technician_id' => $other->id])
            ->assertSessionHasErrors('technician_id');

        $this->assertSame('in_progress', $ticket->fresh()->status);
        $this->assertSame($tech->id, $ticket->fresh()->assigned_to);
    }

    public function test_status_steps_cannot_be_skipped(): void
    {
        $tech = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create(['status' => 'assigned', 'assigned_to' => $tech->id]);

        // assigned -> resolved saute "in_progress"
        $this->actingAs($tech)
            ->post(route('tickets.status', $ticket), ['status' => 'resolved'])
            ->assertSessionHasErrors('status');

        $this->assertSame('assigned', $ticket->fresh()->status);
    }

    public function test_new_ticket_cannot_leave_new_without_assignment(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::factory()->create(['status' => 'new', 'assigned_to' => null]);

        $this->actingAs($admin)
            ->post(route('tickets.status', $ticket), ['status' => 'assigned'])
            ->assertSessionHasErrors('status');

        $this->assertSame('new', $ticket->fresh()->status);
    }

    public function test_full_workflow_in_order(): void
    {
        Notification::fake();
        $tech = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create(['status' => 'assigned', 'assigned_to' => $tech->id]);

        foreach (['in_progress', 'resolved', 'closed'] as $status) {
            $this->actingAs($tech)
                ->post(route('tickets.status', $ticket), ['status' => $status])
                ->assertSessionHasNoErrors();
            $this->assertSame($status, $ticket->fresh()->status);
        }

        $this->assertNotNull($ticket->fresh()->resolved_at);
        $this->assertDatabaseCount('activities', 3);
        Notification::assertSentTo($ticket->user, TicketResolvedNotification::class);
    }

    public function test_only_assigned_technician_or_admin_can_change_status(): void
    {
        $assigned = User::factory()->technician()->create();
        $otherTech = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create(['status' => 'assigned', 'assigned_to' => $assigned->id]);

        $this->actingAs($ticket->user)
            ->post(route('tickets.status', $ticket), ['status' => 'in_progress'])->assertForbidden();
        $this->actingAs($otherTech)
            ->post(route('tickets.status', $ticket), ['status' => 'in_progress'])->assertForbidden();
    }
}
