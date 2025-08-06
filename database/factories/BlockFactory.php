<?php

namespace Database\Factories;

use App\Models\Block;
use App\Models\Section;
use App\Helpers\ImageHelper;
use App\Helpers\SequentialHelper;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Block>
 */
class BlockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['text', 'image', 'video', 'quote']);

        $content = match ($type) {
            'text'   => $this->faker->paragraphs(2, true),
            'image'  => ImageHelper::fakeProfilePhoto() ?? ImageHelper::fakeCompanyLogo($this->faker),
            'video'  => 'https://www.youtube.com/watch?v=' . $this->faker->regexify('[A-Za-z0-9_-]{11}'),
            'quote'  => $this->faker->sentence(16),
            default  => $this->faker->text(200),
        };

        return [
            'type'         => $type,
            'content'      => $content,
            'is_published' => $this->faker->boolean(),
            'title'        => $this->faker->optional()->sentence(5),
            'status'       => $this->faker->randomElement(['draft', 'published', 'archived']),
            // 'settings'   => null, // Tu peux le décommenter et faker si besoin
            // Pas d'order ni section_id ici !
        ];
    }

    /**
     * Associate the block with a section.
     *
     * @param Section|null $section
     * @param int|null $order
     * @return static
     */
    public function withSection($section = null, $order = null)
    {
        return $this->afterCreating(function (Block $block) use ($section, $order) {
            $section = $section ?: Section::factory()->create();

            // On calcule le prochain ordre si non fourni
            $order = $order ?? ($section->blocks()->count() + 1);

            $section->blocks()->attach($block->id, ['order' => $order]);
        });
    }
}
