<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Workflow>
 */
class WorkflowFactory extends Factory
{
    protected $model = Workflow::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'public_id'      => (string) Str::ulid(),
            'title'          => fake()->sentence(3),
            'body'           => fake()->optional()->paragraph(),
            'current_state'  => 'draft',
            'created_by'     => User::factory(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['current_state' => 'draft']);
    }

    public function submitted(): static
    {
        return $this->state(fn () => ['current_state' => 'submitted']);
    }

    public function approved(): static
    {
        return $this->state(fn () => ['current_state' => 'approved']);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['current_state' => 'rejected']);
    }
}
