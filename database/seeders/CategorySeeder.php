<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Climatisation' => 'Climatiseurs, splits, ventilation et régulation de la température.',
            'Electricité' => 'Prises, éclairage, disjoncteurs et alimentation électrique.',
            'Réseau' => 'Connexion internet, wifi, prises réseau et équipements réseau.',
            'Plomberie' => 'Fuites, sanitaires, robinetterie et évacuations.',
            'Imprimante' => 'Imprimantes, scanners et consommables.',
        ];

        foreach ($categories as $name => $description) {
            Category::updateOrCreate(['name' => $name], ['description' => $description]);
        }
    }
}
