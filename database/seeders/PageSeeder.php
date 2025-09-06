<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Models\Block;
use App\Models\Media;
use App\Models\Section;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have an author; fall back to a factory user if none exists.
        $author = User::first() ?? User::factory()->create([
            'name'  => 'Seeder Author',
            'email' => 'author@example.com',
        ]);

        // Collect blockable models (NOT just their IDs) so we can associate morphs safely.
        // Using models avoids brittle "is UUID?" detection and works with morph maps.
        $posts  = Post::all();
        $media  = Media::where('collection_name', 'gallery')->get();

        // Merge all potential blockable models into one pool.
        $blockables = $posts->concat($media);

        // Abort early if nothing to attach to blocks.
        if ($blockables->isEmpty()) {
            echo "Warning: No Post or gallery Media available for blocks. Page seeding skipped.\n";
            return;
        }

        // Create 5 pages authored by $author.
        Page::factory(5)
            ->create([
                'author_id' => $author->id,
            ])
            ->each(function (Page $page) use ($blockables) {
                $numSections = fake()->numberBetween(2, 4);

                for ($sectionIndex = 0; $sectionIndex < $numSections; $sectionIndex++) {

                    // Create the Section WITHOUT a page_id column (sections are reusable).
                    $section = Section::factory()->create();

                    // Link section to page via the pivot with ordered position.
                    $page->sections()->attach($section->id, ['order' => $sectionIndex]);

                    // Create blocks for this section and keep an ordered pivot entry.
                    $numBlocks = fake()->numberBetween(2, 5);

                    for ($blockIndex = 0; $blockIndex < $numBlocks; $blockIndex++) {
                        // Pick a random blockable model (Post or Media instance).
                        $blockableModel = $blockables->random();

                        // Build the Block and associate morphically to the chosen model.
                        // Using associate() guarantees correct blockable_type (class or morph alias).
                        $block = Block::factory()->make();
                        $block->blockable()->associate($blockableModel);
                        $block->save();

                        // Attach the block to the section with an 'order' on the pivot.
                        $section->blocks()->attach($block->id, ['order' => $blockIndex]);
                    }
                }
            });
    }
}
