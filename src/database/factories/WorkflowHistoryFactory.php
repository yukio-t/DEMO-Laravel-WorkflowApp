<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkflowHistory>
 */
class WorkflowHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workflow_id' => Workflow::factory(),
            'from_state'  => fake()->optional()->randomElement(['draft','submitted','approved','rejected']),
            'to_state'    => fake()->randomElement(['submitted','approved','rejected']),
            'acted_by'    => User::factory(),
            'comment'     => fake()->optional()->sentence(),
            'meta'        => null,
        ];
    }
}
