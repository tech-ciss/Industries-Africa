<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    // ---------- Catégories ----------

    public function test_admin_pages_render(): void
    {
        $admin = $this->admin();
        $category = Category::factory()->create();
        $user = User::factory()->create();

        foreach ([
            route('categories.index'), route('categories.create'), route('categories.edit', $category),
            route('users.index'), route('users.create'), route('users.edit', $user),
            route('reports.index'),
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_admin_can_create_update_and_delete_a_category(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('categories.store'), ['name' => 'Plomberie', 'description' => 'Fuites'])
            ->assertRedirect(route('categories.index'));
        $category = Category::where('name', 'Plomberie')->firstOrFail();

        // Le nom reste modifiable sans conflit avec lui-même.
        $this->actingAs($admin)->put(route('categories.update', $category), ['name' => 'Plomberie', 'description' => 'Fuites et WC'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Fuites et WC', $category->fresh()->description);

        $this->actingAs($admin)->delete(route('categories.destroy', $category))->assertRedirect(route('categories.index'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_category_name_is_validated(): void
    {
        $admin = $this->admin();
        Category::factory()->create(['name' => 'Réseau']);

        $this->actingAs($admin)->post(route('categories.store'), ['name' => 'Réseau'])->assertSessionHasErrors('name');
        $this->actingAs($admin)->post(route('categories.store'), ['name' => 'ab'])->assertSessionHasErrors('name');
        $this->assertDatabaseCount('categories', 1);
    }

    public function test_category_used_by_tickets_cannot_be_deleted(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($this->admin())->delete(route('categories.destroy', $ticket->category))
            ->assertSessionHasErrors('category');

        $this->assertDatabaseHas('categories', ['id' => $ticket->category_id]);
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);
    }

    public function test_non_admins_cannot_manage_categories_users_or_reports(): void
    {
        $employee = User::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($employee)->post(route('categories.store'), ['name' => 'Piratage'])->assertForbidden();
        $this->actingAs($employee)->delete(route('categories.destroy', $category))->assertForbidden();
        $this->actingAs($employee)->post(route('users.store'), [])->assertForbidden();
        $this->actingAs($employee)->get(route('reports.index'))->assertForbidden();

        $this->assertDatabaseCount('categories', 1);
    }

    // ---------- Utilisateurs ----------

    private function userPayload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Kouassi',
            'first_name' => 'Awa',
            'email' => 'awa@example.com',
            'phone' => '0102030405',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'technician',
            'status' => 'active',
        ];
    }

    public function test_admin_creates_a_user_with_a_hashed_password(): void
    {
        $this->actingAs($this->admin())->post(route('users.store'), $this->userPayload())
            ->assertRedirect(route('users.index'));

        $user = User::where('email', 'awa@example.com')->firstOrFail();
        $this->assertSame('technician', $user->role);
        $this->assertNotSame('password123', $user->password);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_user_creation_is_validated(): void
    {
        $admin = $this->admin();
        User::factory()->create(['email' => 'pris@example.com']);

        $cases = [
            'email' => $this->userPayload(['email' => 'pris@example.com']),
            'role' => $this->userPayload(['role' => 'superadmin']),
            'status' => $this->userPayload(['status' => 'suspendu']),
            'password' => $this->userPayload(['password_confirmation' => 'autre-chose']),
            'name' => $this->userPayload(['name' => '']),
        ];

        foreach ($cases as $field => $payload) {
            $this->actingAs($admin)->post(route('users.store'), $payload)->assertSessionHasErrors($field);
        }
    }

    public function test_update_without_password_keeps_the_old_one_and_with_password_changes_it(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $oldHash = $user->password;

        $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => 'Nouveau', 'first_name' => 'Nom', 'email' => $user->email,
            'role' => 'employee', 'status' => 'active', 'password' => '',
        ])->assertRedirect(route('users.index'));

        $this->assertSame('Nouveau', $user->fresh()->name);
        $this->assertSame($oldHash, $user->fresh()->password);

        $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => 'Nouveau', 'first_name' => 'Nom', 'email' => $user->email,
            'role' => 'employee', 'status' => 'active',
            'password' => 'nouveau-secret', 'password_confirmation' => 'nouveau-secret',
        ]);

        $this->assertTrue(Hash::check('nouveau-secret', $user->fresh()->password));
    }

    public function test_admin_can_deactivate_another_user_who_is_then_locked_out(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();

        $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => $user->name, 'first_name' => $user->first_name, 'email' => $user->email,
            'role' => 'employee', 'status' => 'inactive',
        ])->assertRedirect(route('users.index'));

        $this->assertSame('inactive', $user->fresh()->status);

        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->assertGuest();
    }

    public function test_user_without_tickets_can_be_deleted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin())->delete(route('users.destroy', $user))->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
