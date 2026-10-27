<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketCrudTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(Category $category, array $overrides = []): array
    {
        return $overrides + [
            'title' => 'Climatisation en panne',
            'description' => 'La climatisation du bureau 12 ne démarre plus depuis ce matin.',
            'category_id' => $category->id,
            'priority' => 'high',
        ];
    }

    // ---------- Création ----------

    public function test_employee_can_create_a_ticket_with_an_attachment(): void
    {
        Storage::fake('public');
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($employee)->post(route('tickets.store'), $this->validPayload($category, [
            'attachment' => UploadedFile::fake()->create('photo.pdf', 100),
        ]));

        $ticket = Ticket::firstOrFail();
        $response->assertRedirect(route('tickets.show', $ticket));

        $this->assertSame('new', $ticket->status);
        $this->assertSame($employee->id, $ticket->user_id);
        $this->assertNull($ticket->assigned_to);
        Storage::disk('public')->assertExists($ticket->attachment);
        $this->assertDatabaseHas('activities', ['ticket_id' => $ticket->id, 'action' => 'created']);
        Notification::assertSentTo($admin, TicketCreatedNotification::class);
        Notification::assertNotSentTo($employee, TicketCreatedNotification::class);
    }

    public function test_creation_form_is_displayed(): void
    {
        $this->actingAs(User::factory()->create())->get(route('tickets.create'))->assertOk();
    }

    public function test_ticket_creation_is_validated(): void
    {
        $employee = User::factory()->create();
        $category = Category::factory()->create();

        $cases = [
            'title' => $this->validPayload($category, ['title' => 'abc']),
            'description' => $this->validPayload($category, ['description' => 'trop court']),
            'category_id' => $this->validPayload($category, ['category_id' => 9999]),
            'priority' => $this->validPayload($category, ['priority' => 'urgente']),
        ];

        foreach ($cases as $field => $payload) {
            $this->actingAs($employee)->post(route('tickets.store'), $payload)->assertSessionHasErrors($field);
        }

        $this->actingAs($employee)->post(route('tickets.store'), $this->validPayload($category, [
            'attachment' => UploadedFile::fake()->create('gros.pdf', 6000),
        ]))->assertSessionHasErrors('attachment');

        $this->assertDatabaseCount('tickets', 0);
    }

    // ---------- Consultation ----------

    public function test_requester_sees_the_ticket_page(): void
    {
        $ticket = Ticket::factory()->create(['title' => 'Prise réseau inactive']);

        $this->actingAs($ticket->user)->get(route('tickets.show', $ticket))
            ->assertOk()->assertSee('Prise réseau inactive');
    }

    public function test_employee_list_only_contains_own_tickets(): void
    {
        $employee = User::factory()->create();
        Ticket::factory()->count(2)->create(['user_id' => $employee->id]);
        Ticket::factory()->count(3)->create();

        $response = $this->actingAs($employee)->get(route('tickets.index'))->assertOk();

        $this->assertCount(2, $response->viewData('tickets'));
    }

    // ---------- Recherche et filtres ----------

    private function ticketIds(User $user, array $query): array
    {
        return $this->actingAs($user)->get(route('tickets.index', $query))->assertOk()
            ->viewData('tickets')->getCollection()->pluck('id')->sort()->values()->all();
    }

    public function test_filters_narrow_the_ticket_list(): void
    {
        $admin = User::factory()->admin()->create();
        $techA = User::factory()->technician()->create();
        $techB = User::factory()->technician()->create();
        $catA = Category::factory()->create();
        $catB = Category::factory()->create();

        $t1 = Ticket::factory()->create([
            'status' => 'new', 'priority' => 'low', 'category_id' => $catA->id,
            'title' => 'Climatiseur salle A', 'created_at' => '2026-01-10 10:00:00',
        ]);
        $t2 = Ticket::factory()->create([
            'status' => 'in_progress', 'priority' => 'critical', 'category_id' => $catB->id,
            'assigned_to' => $techA->id, 'title' => 'Imprimante bloquée', 'created_at' => '2026-02-10 10:00:00',
        ]);
        $t3 = Ticket::factory()->create([
            'status' => 'closed', 'priority' => 'high', 'category_id' => $catB->id,
            'assigned_to' => $techB->id, 'title' => 'Réseau coupé', 'created_at' => '2026-03-10 10:00:00',
        ]);

        $this->assertSame([$t1->id, $t2->id, $t3->id], $this->ticketIds($admin, []));
        $this->assertSame([$t2->id], $this->ticketIds($admin, ['status' => 'in_progress']));
        $this->assertSame([$t1->id], $this->ticketIds($admin, ['priority' => 'low']));
        $this->assertSame([$t2->id, $t3->id], $this->ticketIds($admin, ['category_id' => $catB->id]));
        $this->assertSame([$t3->id], $this->ticketIds($admin, ['technician_id' => $techB->id]));
        $this->assertSame([$t2->id, $t3->id], $this->ticketIds($admin, ['date_from' => '2026-02-01']));
        $this->assertSame([$t1->id, $t2->id], $this->ticketIds($admin, ['date_to' => '2026-02-28']));
        $this->assertSame([$t2->id], $this->ticketIds($admin, ['date_from' => '2026-02-01', 'date_to' => '2026-02-28']));
        $this->assertSame([$t2->id], $this->ticketIds($admin, ['search' => 'Imprimante']));
        $this->assertSame([$t3->id], $this->ticketIds($admin, ['status' => 'closed', 'category_id' => $catB->id]));
        $this->assertSame([], $this->ticketIds($admin, ['status' => 'resolved']));
    }

    // ---------- Modification et suppression ----------

    public function test_owner_can_edit_a_new_ticket_but_not_an_assigned_one(): void
    {
        $new = Ticket::factory()->create();
        $assigned = Ticket::factory()->assigned()->create();

        $this->actingAs($new->user)->get(route('tickets.edit', $new))->assertOk();
        $this->actingAs($assigned->user)->get(route('tickets.edit', $assigned))->assertForbidden();
        $this->actingAs($assigned->user)->put(route('tickets.update', $assigned), $this->validPayload($assigned->category))
            ->assertForbidden();
    }

    public function test_replacing_the_attachment_deletes_the_old_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('tickets/ancien.pdf', 'contenu');
        $ticket = Ticket::factory()->create(['attachment' => 'tickets/ancien.pdf']);

        $this->actingAs($ticket->user)->put(route('tickets.update', $ticket), $this->validPayload($ticket->category, [
            'attachment' => UploadedFile::fake()->create('nouveau.pdf', 50),
        ]))->assertRedirect(route('tickets.show', $ticket));

        Storage::disk('public')->assertMissing('tickets/ancien.pdf');
        Storage::disk('public')->assertExists($ticket->fresh()->attachment);
        $this->assertDatabaseHas('activities', ['ticket_id' => $ticket->id, 'action' => 'updated']);
    }

    public function test_only_admin_can_delete_a_ticket(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($ticket->user)->delete(route('tickets.destroy', $ticket))->assertForbidden();
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);

        $this->actingAs(User::factory()->admin()->create())->delete(route('tickets.destroy', $ticket))
            ->assertRedirect(route('tickets.index'));
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }

    // ---------- Commentaires ----------

    public function test_comment_with_attachment_is_stored_and_logged(): void
    {
        Storage::fake('public');
        $ticket = Ticket::factory()->create();

        $this->actingAs($ticket->user)->post(route('tickets.comments.store', $ticket), [
            'message' => 'Voici une photo de la panne.',
            'attachment' => UploadedFile::fake()->create('panne.pdf', 100),
        ])->assertRedirect(route('tickets.show', $ticket));

        $comment = Comment::firstOrFail();
        Storage::disk('public')->assertExists($comment->attachment);
        $this->assertDatabaseHas('activities', ['ticket_id' => $ticket->id, 'action' => 'commented']);
    }

    public function test_comment_is_validated(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($ticket->user)->post(route('tickets.comments.store', $ticket), ['message' => 'a'])
            ->assertSessionHasErrors('message');
        $this->actingAs($ticket->user)->post(route('tickets.comments.store', $ticket), [
            'message' => 'Un message valide',
            'attachment' => UploadedFile::fake()->create('gros.pdf', 6000),
        ])->assertSessionHasErrors('attachment');

        $this->assertDatabaseCount('comments', 0);
        $this->assertSame(0, Activity::count());
    }
}
