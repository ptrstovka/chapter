<?php

namespace Database\Factories;

use App\Enums\AccessGroupVisibility;
use App\Models\AccessGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccessGroup>
 */
class AccessGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'visibility' => AccessGroupVisibility::Public,
        ];
    }

    /**
     * Indicate that the group should be private.
     */
    public function private(): static
    {
        return $this->state([
            'visibility' => AccessGroupVisibility::Private,
        ]);
    }
}
