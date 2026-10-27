<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Comptes de test (mot de passe : "password" pour tous) :
 *   admin@maintenance.test, technicien1..5@maintenance.test,
 *   employe1..10@maintenance.test, inactif@maintenance.test (compte désactivé).
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create([
            'first_name' => 'Admin',
            'name' => 'Principal',
            'email' => 'admin@maintenance.test',
        ]);

        User::factory()->technician()->count(5)
            ->sequence(fn ($sequence) => ['email' => 'technicien'.($sequence->index + 1).'@maintenance.test'])
            ->create();

        User::factory()->count(10)
            ->sequence(fn ($sequence) => ['email' => 'employe'.($sequence->index + 1).'@maintenance.test'])
            ->create();

        // Pour tester le refus de connexion d'un compte inactif.
        User::factory()->create([
            'first_name' => 'Compte',
            'name' => 'Désactivé',
            'email' => 'inactif@maintenance.test',
            'status' => 'inactive',
        ]);
    }
}
