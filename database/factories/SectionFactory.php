<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\Section;
use App\Helpers\SequentialHelper;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Section>
 */
class SectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'is_published' => $this->faker->boolean(),
            'color' => $this->faker->hexColor(),
            'slug' => $this->faker->slug(),
            'settings' => json_encode(['example_setting' => $this->faker->word()]),
        ];
    }
}
