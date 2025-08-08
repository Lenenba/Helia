<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Page>
 */
class PageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title'        => $this->faker->unique()->sentence(),
            'slug'         => $this->faker->unique()->slug(),
            'excerpt'      => $this->faker->optional()->text(150),
            'is_published' => $this->faker->boolean(),
            'type'         => $this->faker->randomElement(['page', 'landing', 'contact', 'custom', 'about', 'terms']),
            'status'        => $this->faker->randomElement(['draft', 'published', 'unpublished', 'archived']),
            'published_at' => $this->faker->optional(0.7)->dateTimeBetween('-6 months', 'now'),
            'settings'     => null, // ou ['layout' => ...] selon besoins
            'author_id'    => User::factory(), // ou null, ou user_id spécifique
            'parent_id'    => null, // ou Page::factory() si pages imbriquées
        ];
    }
}
