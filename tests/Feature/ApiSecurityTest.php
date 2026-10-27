<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_requires_authentication(): void
    {
        $this->getJson('/api/tickets')->assertUnauthorized();
    }

    public function test_login_returns_a_token_for_active_users_only(): void
    {
        $active = User::factory()->create();
        $inactive = User::factory()->create(['status' => 'inactive']);

        $this->postJson('/api/login', ['email' => $active->email, 'password' => 'password'])
            ->assertOk()->assertJsonStructure(['token', 'user']);

        $this->postJson('/api/login', ['email' => $inactive->email, 'password' => 'password'])
            ->assertStatus(422);
    }

    public function test_inactive_user_with_a_token_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create(['status' => 'inactive']));

        $this->getJson('/api/tickets')->assertForbidden();
    }

    public function test_index_only_returns_visible_tickets(): void
    {
        $employee = User::factory()->create();
        Ticket::factory()->count(2)->create(['user_id' => $employee->id]);
        Ticket::factory()->count(3)->create();

        Sanctum::actingAs($employee);

        $this->getJson('/api/tickets')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_cannot_show_update_or_delete_someone_elses_ticket(): void
    {
        $ticket = Ticket::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/tickets/{$ticket->id}")->assertForbidden();
        $this->putJson("/api/tickets/{$ticket->id}", ['title' => 'Titre piraté'])->assertForbidden();
        $this->deleteJson("/api/tickets/{$ticket->id}")->assertForbidden();

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);
    }

    public function test_employee_cannot_delete_even_his_own_ticket(): void
    {
        $ticket = Ticket::factory()->create();
        Sanctum::actingAs($ticket->user);

        $this->deleteJson("/api/tickets/{$ticket->id}")->assertForbidden();
    }

    public function test_store_forces_status_new_and_logs_activity(): void
    {
        $employee = User::factory()->create();
        $category = Category::factory()->create();
        Sanctum::actingAs($employee);

        $this->postJson('/api/tickets', [
            'title' => 'Climatisation en panne',
            'description' => 'La climatisation du bureau 12 ne démarre plus depuis ce matin.',
            'category_id' => $category->id,
            'priority' => 'high',
            'status' => 'closed', // doit être ignoré
        ])->assertCreated()->assertJsonPath('status', 'new');

        $this->assertDatabaseHas('activities', ['action' => 'created', 'user_id' => $employee->id]);
    }

    public function test_update_ignores_status_field(): void
    {
        $ticket = Ticket::factory()->create(['status' => 'new']);
        Sanctum::actingAs($ticket->user);

        $this->putJson("/api/tickets/{$ticket->id}", ['title' => 'Nouveau titre valide', 'status' => 'closed'])
            ->assertOk();

        $this->assertSame('new', $ticket->fresh()->status);
    }

    public function test_api_status_change_respects_workflow(): void
    {
        $tech = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create(['status' => 'assigned', 'assigned_to' => $tech->id]);
        Sanctum::actingAs($tech);

        $this->patchJson("/api/tickets/{$ticket->id}/status", ['status' => 'closed'])
            ->assertStatus(422);
        $this->patchJson("/api/tickets/{$ticket->id}/status", ['status' => 'in_progress'])
            ->assertOk()->assertJsonPath('status', 'in_progress');
    }
}
