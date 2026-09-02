<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\PolicyDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PolicyDocument>
 */
class PolicyDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'owner_id' => User::factory(),
            'uuid' => (string) Str::uuid(),
            'title' => fake()->sentence(4),
            'status' => 'draft',
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => 'active',
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => 'archived',
        ]);
    }
}
