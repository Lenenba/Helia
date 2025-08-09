<?php

namespace Database\Seeders;

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

        // Create ONE profile picture for the user
        Media::factory()->create([
            'mediaable_id' => $user->id,
            'mediaable_type' => User::class,
            'collection_name' => 'avatar',
            'is_profile_picture' => true,
        ]);

        // Create 9 additional media files for the user (e.g., a gallery)
        Media::factory(9)->create([
            'mediaable_id' => $user->id,
            'mediaable_type' => User::class,
            'collection_name' => 'gallery',
            'is_profile_picture' => false,
        ]);

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

        Post::factory(8)->create([
            'author_id'    => $user->id,
            'is_published' => true,
        ]);
    }
}
