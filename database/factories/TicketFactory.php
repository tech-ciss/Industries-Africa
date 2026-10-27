<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Par défaut un ticket est "Nouveau" (sans technicien, sans échéance, non résolu).
 * Les états assigned() / inProgress() / resolved() / closed() produisent des tickets
 * cohérents avec le workflow : technicien assigné, échéance, date de résolution.
 *
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(3),
            'priority' => fake()->randomElement(['low', 'normal', 'high', 'critical']),
            'status' => 'new',
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'assigned_to' => null,
            'deadline' => null,
            'resolved_at' => null,
        ];
    }

    public function assigned(?User $technician = null): static
    {
        return $this->state(fn () => [
            'status' => 'assigned',
            'assigned_to' => $technician?->id ?? User::factory()->technician(),
            'deadline' => fake()->dateTimeBetween('+1 day', '+2 weeks'),
        ]);
    }

    public function inProgress(?User $technician = null): static
    {
        return $this->assigned($technician)->state(['status' => 'in_progress']);
    }

    public function resolved(?User $technician = null): static
    {
        return $this->inProgress($technician)->state(fn () => [
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    public function closed(?User $technician = null): static
    {
        return $this->resolved($technician)->state(['status' => 'closed']);
    }
}
