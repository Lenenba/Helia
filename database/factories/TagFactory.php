<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        // Generate a unique name and a matching unique slug
        $name = ucfirst($this->faker->unique()->words(rand(1, 2), true)); // e.g. "cms", "laravel news"
        $slugBase = Str::slug($name);

        return [
            'name' => $name,
            'slug' => $slugBase . '-' . Str::random(5), // avoid unique collisions on small datasets
        ];
    }

    /**
     * Optional state to force a given name/slug.
     */
    public function named(string $name): self
    {
        return $this->state(function () use ($name) {
            return [
                'name' => $name,
                'slug' => Str::slug($name) . '-' . Str::random(4),
            ];
        });
    }
}
