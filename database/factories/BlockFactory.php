<?php

namespace Database\Factories;

use App\Models\Block;
use App\Models\HtmlContent;
use App\Models\Post;
use App\Models\Media;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Block>
 */
class BlockFactory extends Factory
{
    /**
     * Define the model's default state.
     * Par défaut, un bloc pointe vers un simple contenu HTML.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // On crée un contenu HTML et on lie le bloc à celui-ci.
            'blockable_id'   => HtmlContent::factory(),
            'blockable_type' => HtmlContent::class,

            'template_hint'  => $this->faker->randomElement(['default', 'card', 'highlight']),
            'settings'       => null,
        ];
    }

    /**
     * Crée un bloc qui pointe vers un Post.
     * Utilisation : Block::factory()->forPost()->create()
     */
    public function forPost(Post $post): static
    {
        return $this->state(fn(array $attributes) => [
            'blockable_id'   => $post ?? Post::factory(),
            'blockable_type' => Post::class,
        ]);
    }

    /**
     * Crée un bloc qui pointe vers un Média (image).
     * Utilisation : Block::factory()->forMedia()->create()
     */
    public function forMedia(Media $media): static
    {
        return $this->state(fn(array $attributes) => [
            'blockable_id'   => $media ?? Media::factory(), // Assurez-vous d'avoir une MediaFactory
            'blockable_type' => Media::class,
            'template_hint'  => 'image',
        ]);
    }

    /**
     * Associe le bloc créé à une section dans la table pivot.
     * Cette méthode reste très utile et sa logique est presque identique.
     */
    public function withSection(Section $section, int $order): static
    {
        return $this->afterCreating(function (Block $block) use ($section, $order) {
            $targetSection = $section ?? Section::factory()->create();

            // Calcule le prochain ordre si non fourni
            $finalOrder = $order ?? $targetSection->blocks()->count();

            $targetSection->blocks()->attach($block->id, ['order' => $finalOrder]);
        });
    }
}
