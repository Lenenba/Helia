<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Helpers\ImageHelper;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['post', 'page', 'news'];
        $type  = $this->faker->randomElement($types);

        // Astuce : évite d'appeler une API externe (Unsplash) à chaque seed,
        // tu peux commenter la ligne si besoin de performances
        $coverImage = ImageHelper::fakeProfilePhoto()
            ?? ImageHelper::fakeCompanyLogo($this->faker);

        return [
            'title'         => $this->faker->unique()->sentence(),
            'slug'          => $this->faker->unique()->slug(),
            'excerpt'       => $this->faker->optional()->text(180),
            'content'       => $this->faker->paragraphs(3, true),
            'cover_image'   => $coverImage,
            'type'          => $type,
            'status'        => $this->faker->randomElement(['draft', 'published', 'archived']),
            'is_published'  => $this->faker->boolean(70),
            'published_at'  => $this->faker->optional(0.7)->dateTimeBetween('-1 year', 'now'),
            'visibility'    => $this->faker->randomElement(['public', 'private', 'members_only']),
            'views'         => $this->faker->numberBetween(0, 1500),
            'meta'          => json_encode(['seo_title' => 'Test', 'seo_desc' => 'Demo']),
            'tags'          => json_encode(['news', 'cms', 'test']),
            'author_id'     => User::factory(),
            'parent_id'     => null,
        ];
    }
}
