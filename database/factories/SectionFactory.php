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
            'order'        => 0, // Valeur temporaire, ajustée après création
            'slug' => $this->faker->slug(),
            'settings' => json_encode(['example_setting' => $this->faker->word()]),
            'page_id' => Page::factory()
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Section $section) {
            // Ne met à jour que si order non renseigné
            if (!$section->order || $section->order === 0) {
                $section->order = SequentialHelper::nextOrderForPage($section->page_id);
                $section->save();
            }
        });
    }
}
