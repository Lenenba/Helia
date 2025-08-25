<?php

namespace Database\Factories;

use App\Models\Tag;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Arr;
use App\Helpers\ImageHelper;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $types = ['post', 'page', 'news'];
        $type  = $this->faker->randomElement($types);

        // Use helper methods for image generation
        $coverImage = ImageHelper::fakeProfilePhoto()
            ?? ImageHelper::fakeCompanyLogo($this->faker);

        return [
            'title'            => $this->faker->unique()->sentence(),
            'slug'             => $this->faker->unique()->slug(),
            'excerpt'          => $this->faker->optional()->text(180),
            'content'          => $this->faker->paragraphs(3, true),

            // New cover fields
            'cover_media_id'   => null,

            'image_position'   => $this->faker->randomElement(['left', 'right']),
            'show_title'       => $this->faker->boolean(85),

            'type'             => $type,
            'status'           => $this->faker->randomElement(['draft', 'published', 'unpublished', 'archived']),
            'is_published'     => $this->faker->boolean(70),
            'published_at'     => $this->faker->optional(0.7)->dateTimeBetween('-1 year', 'now'),
            'visibility'       => $this->faker->randomElement(['public', 'private', 'members_only']),
            'views'            => $this->faker->numberBetween(0, 1500),
            'meta'             => ['seo_title' => 'Test', 'seo_desc' => 'Demo'],

            'author_id'        => User::factory(),
            'parent_id'        => null,
        ];
    }

    /** State for published posts */
    public function published(): self
    {
        return $this->state(fn() => [
            'status'       => 'published',
            'is_published' => true,
            'published_at' => now()->subDays(rand(0, 30)),
        ]);
    }

    /** Attach random tags */
    public function withRandomTags(int $count = 2): self
    {
        return $this->afterCreating(function (\App\Models\Post $post) use ($count) {
            // Ensure there are tags in DB
            if (Tag::count() === 0) {
                Tag::factory()->count(max(5, $count))->create();
            }

            // Re-fetch valid IDs, sample safely
            $ids = Tag::query()->pluck('id')->all();
            $ids = Arr::shuffle($ids);
            $attach = array_slice($ids, 0, min($count, count($ids)));

            if (!empty($attach)) {
                $post->tags()->syncWithoutDetaching($attach);
            }
        });
    }
}
