<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_and_api_route_names_do_not_collide(): void
    {
        $this->assertSame('/tickets/1', route('tickets.show', 1, false));
        $this->assertSame('/tickets/1', route('tickets.update', 1, false));
        $this->assertSame('/api/tickets/1', route('api.tickets.show', 1, false));
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Intrus',
            'email' => 'intrus@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'intrus@example.com']);
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->create(['status' => 'inactive']);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertGuest();
    }

    public function test_user_deactivated_while_logged_in_is_kicked_out(): void
    {
        $user = User::factory()->create(['status' => 'inactive']);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_dashboard_only_counts_tickets_visible_to_the_user(): void
    {
        $employee = User::factory()->create();
        Ticket::factory()->count(2)->create(['user_id' => $employee->id]);
        Ticket::factory()->count(3)->create(); // tickets d'autres employés

        $response = $this->actingAs($employee)->get('/dashboard')->assertOk();

        $this->assertSame(2, $response->viewData('stats')['total']);
        $this->assertCount(2, $response->viewData('recent'));
    }

    public function test_technician_dashboard_is_limited_to_assigned_tickets(): void
    {
        $tech = User::factory()->technician()->create();
        Ticket::factory()->count(2)->create(['assigned_to' => $tech->id]);
        Ticket::factory()->count(4)->create();

        $response = $this->actingAs($tech)->get('/dashboard')->assertOk();

        $this->assertSame(2, $response->viewData('stats')['total']);
    }

    public function test_employee_cannot_view_someone_elses_ticket(): void
    {
        $ticket = Ticket::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($other)->get(route('tickets.show', $ticket))->assertForbidden();
    }

    public function test_employee_cannot_comment_on_someone_elses_ticket(): void
    {
        $ticket = Ticket::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($other)
            ->post(route('tickets.comments.store', $ticket), ['message' => 'Je ne devrais pas pouvoir'])
            ->assertForbidden();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_requester_can_comment_on_own_ticket(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($ticket->user)
            ->post(route('tickets.comments.store', $ticket), ['message' => 'Toujours en panne'])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseCount('comments', 1);
    }

    public function test_only_author_or_admin_can_delete_a_comment(): void
    {
        $comment = Comment::factory()->create();
        $intruder = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($intruder)->delete(route('comments.destroy', $comment))->assertForbidden();
        $this->assertDatabaseCount('comments', 1);

        $this->actingAs($admin)->delete(route('comments.destroy', $comment));
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_employee_cannot_access_admin_areas(): void
    {
        $employee = User::factory()->create();

        $this->actingAs($employee)->get('/users')->assertForbidden();
        $this->actingAs($employee)->get('/categories')->assertForbidden();
    }

    public function test_admin_cannot_delete_or_demote_himself(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->delete(route('users.destroy', $admin))->assertForbidden();

        $this->actingAs($admin)->put(route('users.update', $admin), [
            'name' => $admin->name,
            'first_name' => $admin->first_name,
            'email' => $admin->email,
            'role' => 'employee',
            'status' => 'active',
        ])->assertSessionHasErrors('role');

        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_user_with_tickets_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($admin)->delete(route('users.destroy', $ticket->user))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id' => $ticket->user_id]);
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);
    }
}
