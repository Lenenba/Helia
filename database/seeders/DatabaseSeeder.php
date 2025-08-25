<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Models\Block;
use App\Models\Media;
use App\Models\Section;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Create some tags
        Tag::factory()->count(10)->create();

        Media::factory()
            ->avatar()
            ->forModel($user)
            ->create();

        // 9 gallery items
        Media::factory()
            ->gallery()
            ->count(9)
            ->forModel($user)
            ->create();

        Page::factory(5)
            ->create([
                'author_id'    => $user->id,
                'is_published' => true,
            ])
            ->each(function ($page) {
                $nbSections = fake()->numberBetween(2, 4);
                for ($i = 1; $i <= $nbSections; $i++) {
                    $section = Section::factory()->create([
                        'page_id'      => $page->id,
                        'is_published' => true,
                        'order'        => $i,
                    ]);

                    $nbBlocks = fake()->numberBetween(2, 5);
                    for ($j = 1; $j <= $nbBlocks; $j++) {
                        $block = Block::factory()->create([
                            'is_published' => true,
                        ]);
                        // Crée la relation dans la table pivot avec l'ordre
                        $section->blocks()->attach($block->id, ['order' => $j]);
                    }
                }
            });

        $tagIds = Tag::pluck('id')->all();

        $mediaIds = Media::query()
            ->where('mediaable_id', $user->id)
            ->where('collection_name', 'gallery')
            ->pluck('id');

        if ($mediaIds->count() > 0) {
            Post::factory(12)
                ->published()
                ->create([
                    'author_id' => $user->id,
                    // on va renseigner cover_media_id après création
                ])
                ->each(function (Post $post) use ($mediaIds, $tagIds) {
                    $post->update([
                        'cover_media_id'   => $mediaIds->random(),
                    ]);

                    if (!empty($tagIds)) {
                        // attacher 2-3 tags au hasard existants
                        $pick = collect($tagIds)->shuffle()->take(rand(2, 3))->all();
                        $post->tags()->syncWithoutDetaching($pick);
                    }
                });
        }
    }
}
