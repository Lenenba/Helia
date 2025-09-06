<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\Post;
use App\Models\User;
use App\Models\Media;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first(); // Assurez-vous que l'utilisateur existe
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
                ])
                ->each(function (Post $post) use ($mediaIds, $tagIds) {
                    $post->update([
                        'cover_media_id' => $mediaIds->random(),
                    ]);

                    if (!empty($tagIds)) {
                        $pick = collect($tagIds)->shuffle()->take(rand(2, 3))->all();
                        $post->tags()->syncWithoutDetaching($pick);
                    }
                });
        }
    }
}
