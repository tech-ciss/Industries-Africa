<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiTest extends TestCase
{
    use RefreshDatabase;

    // ---------- Pages publiques ----------

    public function test_guest_pages_render(): void
    {
        $this->get('/')->assertOk()->assertSee('Se connecter');
        $this->get('/login')->assertOk()->assertSee('Connexion')->assertSee('Mot de passe oublié ?');
        $this->get('/forgot-password')->assertOk()->assertSee('Envoyer le lien');
        $this->get('/reset-password/un-jeton')->assertOk()->assertSee('Enregistrer le mot de passe');
    }

    public function test_landing_page_offers_the_dashboard_to_signed_in_users(): void
    {
        $this->actingAs(User::factory()->create())->get('/')->assertOk()->assertSee('Ouvrir le tableau de bord');
    }

    public function test_email_verification_and_password_confirmation_pages_render(): void
    {
        $this->actingAs(User::factory()->unverified()->create())->get('/verify-email')
            ->assertOk()->assertSee('Vérifiez votre adresse email');
        $this->actingAs(User::factory()->create())->get('/confirm-password')
            ->assertOk()->assertSee('Confirmer votre mot de passe');
    }

    // ---------- Pages de l'application ----------

    public function test_every_page_renders_for_an_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::factory()->assigned()->create();
        $category = Category::factory()->create();
        $user = User::factory()->create();

        foreach ([
            route('dashboard'), route('tickets.index'), route('tickets.create'), route('tickets.show', $ticket),
            route('tickets.edit', $ticket), route('users.index'), route('users.create'), route('users.edit', $user),
            route('users.edit', $admin), route('categories.index'), route('categories.create'),
            route('categories.edit', $category), route('reports.index'), route('profile.edit'),
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_every_page_renders_for_an_employee_and_a_technician(): void
    {
        $employee = User::factory()->create();
        $own = Ticket::factory()->create(['user_id' => $employee->id]);
        $tech = User::factory()->technician()->create();
        $assigned = Ticket::factory()->inProgress($tech)->create();

        foreach ([route('dashboard'), route('tickets.index'), route('tickets.create'),
            route('tickets.show', $own), route('tickets.edit', $own), route('profile.edit')] as $url) {
            $this->actingAs($employee)->get($url)->assertOk();
        }
        foreach ([route('dashboard'), route('tickets.index'), route('tickets.show', $assigned), route('profile.edit')] as $url) {
            $this->actingAs($tech)->get($url)->assertOk();
        }
    }

    public function test_navigation_depends_on_the_role(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))
            ->assertSee('Mes demandes')->assertSee('Nouvelle demande')
            ->assertDontSee('Administration')->assertDontSee('Utilisateurs');

        $this->actingAs(User::factory()->technician()->create())->get(route('dashboard'))
            ->assertSee('Mes interventions')->assertDontSee('Administration');

        $this->actingAs(User::factory()->admin()->create())->get(route('dashboard'))
            ->assertSee('Administration')->assertSee('Utilisateurs')->assertSee('Catégories')->assertSee('Rapports');
    }

    // ---------- Fiche ticket ----------

    public function test_ticket_actions_follow_the_permissions(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($ticket->user)->get(route('tickets.show', $ticket))
            ->assertSee('Modifier')
            ->assertDontSee('Affecter un technicien')
            ->assertDontSee('Supprimer');

        $this->actingAs(User::factory()->admin()->create())->get(route('tickets.show', $ticket))
            ->assertSee('Affecter un technicien')
            ->assertSee('Supprimer')
            ->assertSee('doit d\'abord être assigné', false);
    }

    public function test_next_step_button_matches_the_status(): void
    {
        $tech = User::factory()->technician()->create();

        foreach (['assigned' => 'Démarrer l\'intervention', 'in_progress' => 'Marquer comme résolu', 'resolved' => 'Fermer le ticket'] as $status => $label) {
            $ticket = Ticket::factory()->inProgress($tech)->create(['status' => $status]);

            $this->actingAs($tech)->get(route('tickets.show', $ticket))->assertSee($label);
        }
    }

    public function test_workflow_steps_reflect_progress_and_dates(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::factory()->inProgress()->create(['created_at' => '2026-09-01 08:00:00']);
        Activity::unguarded(fn () => Activity::create([
            'ticket_id' => $ticket->id, 'user_id' => $admin->id, 'action' => 'assigned',
            'description' => 'Assigné à Tech.', 'created_at' => '2026-09-01 10:00:00',
        ]));
        Activity::unguarded(fn () => Activity::create([
            'ticket_id' => $ticket->id, 'user_id' => $admin->id, 'action' => 'status_changed',
            'description' => 'Statut changé : Assigné → En cours.', 'created_at' => '2026-09-01 11:30:00',
        ]));

        $steps = $ticket->fresh()->workflowSteps();

        $this->assertSame(
            ['new' => 'done', 'assigned' => 'done', 'in_progress' => 'current', 'resolved' => 'todo', 'closed' => 'todo'],
            collect($steps)->pluck('state', 'status')->all()
        );
        $this->assertSame('2026-09-01 08:00', $steps[0]['at']->format('Y-m-d H:i'));
        $this->assertSame('2026-09-01 10:00', $steps[1]['at']->format('Y-m-d H:i'));
        $this->assertSame('2026-09-01 11:30', $steps[2]['at']->format('Y-m-d H:i'));
        $this->assertNull($steps[3]['at']);
    }

    public function test_a_closed_ticket_has_every_step_done(): void
    {
        $steps = Ticket::factory()->closed()->create()->workflowSteps();

        $this->assertSame(['done'], array_values(array_unique(array_column($steps, 'state'))));
    }

    public function test_overdue_flag(): void
    {
        $late = Ticket::factory()->inProgress()->create(['deadline' => now()->subDay()]);
        $onTime = Ticket::factory()->inProgress()->create(['deadline' => now()->addDay()]);
        $resolved = Ticket::factory()->resolved()->create(['deadline' => now()->subDay()]);

        $this->assertTrue($late->isOverdue());
        $this->assertFalse($onTime->isOverdue());
        $this->assertFalse($resolved->isOverdue());
        $this->assertFalse(Ticket::factory()->create()->isOverdue());

        $this->actingAs(User::factory()->admin()->create())->get(route('tickets.index'))->assertSee('En retard');
    }

    // ---------- Listes ----------

    public function test_lists_show_empty_states(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('tickets.index'))->assertSee('Aucun ticket pour le moment.');
        $this->actingAs($admin)->get(route('tickets.index', ['status' => 'closed']))
            ->assertSee('Aucun ticket ne correspond à ces filtres.');
        $this->actingAs($admin)->get(route('categories.index'))->assertSee('Aucune catégorie.');
    }

    public function test_pagination_uses_bootstrap_markup(): void
    {
        Ticket::factory()->count(16)->create();

        $this->actingAs(User::factory()->admin()->create())->get(route('tickets.index'))
            ->assertSee('class="pagination"', false)
            ->assertSee('16 tickets');
    }

    public function test_users_list_never_offers_to_delete_the_current_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->create();

        $this->actingAs($admin)->get(route('users.index'))
            ->assertSee('action="'.route('users.destroy', $other).'"', false)
            ->assertDontSee('action="'.route('users.destroy', $admin).'"', false);
    }

    public function test_category_in_use_cannot_be_deleted_from_the_list(): void
    {
        $ticket = Ticket::factory()->create();
        $free = Category::factory()->create();

        $this->actingAs(User::factory()->admin()->create())->get(route('categories.index'))
            ->assertSee('Suppression impossible : catégorie utilisée')
            ->assertSee('action="'.route('categories.destroy', $free).'"', false)
            ->assertDontSee('action="'.route('categories.destroy', $ticket->category).'"', false);
    }

    // ---------- Formulaires et messages ----------

    public function test_validation_messages_are_in_french_and_input_is_kept(): void
    {
        app()->setLocale('fr');
        $employee = User::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($employee)
            ->from(route('tickets.create'))
            ->followingRedirects()
            ->post(route('tickets.store'), [
                'title' => 'abc',
                'description' => 'Une description suffisamment longue pour passer.',
                'category_id' => $category->id,
                'priority' => 'high',
            ])
            ->assertSee('Le champ titre doit contenir au moins 5 caractères.')
            ->assertSee('value="abc"', false);

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_required_fields_use_the_french_attribute_names(): void
    {
        app()->setLocale('fr');

        $this->actingAs(User::factory()->create())->post(route('tickets.store'), [])
            ->assertSessionHasErrors([
                'title' => 'Le champ titre est obligatoire.',
                'category_id' => 'Le champ catégorie est obligatoire.',
            ]);
    }

    public function test_creation_form_explains_each_priority(): void
    {
        $this->actingAs(User::factory()->create())->get(route('tickets.create'))
            ->assertSee('Gêne mineure, peut attendre')
            ->assertSee('Bloque une partie du travail')
            ->assertSee('Sécurité ou arrêt d\'activité');
    }

    public function test_admin_cannot_edit_own_role_in_the_form(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('users.edit', $admin))
            ->assertSee('Vous ne pouvez pas modifier votre propre rôle', false);
    }

    public function test_profile_page_has_no_account_deletion(): void
    {
        $this->actingAs(User::factory()->create())->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Informations personnelles')
            ->assertSee('Changer le mot de passe')
            ->assertDontSee('Supprimer le compte');
    }

    // ---------- Modèles ----------

    public function test_user_display_helpers(): void
    {
        $user = User::factory()->technician()->create(['first_name' => 'awa', 'name' => 'Kouassi', 'status' => 'inactive']);

        $this->assertSame('AK', $user->initials);
        $this->assertSame('Technicien', $user->role_label);
        $this->assertSame('Inactif', $user->status_label);
    }
}
