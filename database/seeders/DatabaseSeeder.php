<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Jeu de données reproductible : les mêmes noms et tickets à chaque "migrate:fresh --seed".
        fake()->seed(2026);

        $this->call([
            CategorySeeder::class,
            UserSeeder::class,
            TicketSeeder::class,
        ]);
    }
}
