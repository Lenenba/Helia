<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Post;
use App\Models\Block;
use App\Models\Media;
use App\Models\Section;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PageDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Nettoyage optionnel
            $page = Page::firstOrCreate(
                ['slug' => 'about-us'],
                ['title' => 'About us', 'type' => 'page', 'status' => 'published']
            );

            // Détacher les sections existantes de cette page (si tu veux un état propre)
            $page->sections()->detach();

            /* ---------- Section 1 : Hero (ordre 0) ---------- */
            $section1 = Section::create([
                'title'    => 'Hero',
                'db_type'  => 'one_column',
                'settings' => [
                    'ui_type' => '1 column',
                    'layout'  => ['columns_count' => 1, 'columns' => [['index' => 0, 'blocks' => []]]],
                ],
            ]);

            // ⚠️ Attacher avec le pivot 'order'
            $page->sections()->attach($section1->id, ['order' => 0]);

            // Post de démo
            $post = Post::factory()->create([
                'title'          => 'Welcome to our company',
                'excerpt'        => 'We are leaders in innovation',
                'image_position' => 'left', // évite l’ENUM error si 'background' n’est pas encore dans le schéma
            ]);

            // Block wrapper (morph Post)
            $blockHero = Block::create([
                'blockable_type' => Post::class,
                'blockable_id'   => $post->id,
                'template_hint'  => 'hero',
                'settings'       => [],
            ]);

            // Place le block en colonne 0
            $section1->blocks()->attach($blockHero->id, ['order' => 0, 'column_index' => 0]);

            /* ---------- Section 2 : Features (ordre 1) ---------- */
            $section2 = Section::create([
                'title'    => 'Features',
                'db_type'  => 'two_columns',
                'settings' => [
                    'ui_type' => '2 columns',
                    'layout'  => [
                        'columns_count' => 2,
                        'columns' => [
                            ['index' => 0, 'blocks' => []],
                            ['index' => 1, 'blocks' => []],
                        ],
                    ],
                ],
            ]);

            // ⚠️ Attacher avec un order différent
            $page->sections()->attach($section2->id, ['order' => 1]);

            // Media de démo
            $media = Media::find(1);

            $blockMedia = Block::create([
                'blockable_type' => Media::class,
                'blockable_id'   => $media->id,
                'settings'       => [],
            ]);

            // Colonne gauche (0)
            $section2->blocks()->attach($blockMedia->id, ['order' => 0, 'column_index' => 0]);

            // Bloc générique (ex: HTML simple)
            $generic = Block::create([
                'blockable_type' => Block::class, // si tu utilises un wrapper pour du HTML brut
                'blockable_id'   => 0,
                'settings'       => ['html' => '<p>This is a generic block of text</p>'],
            ]);

            // Colonne droite (1)
            $section2->blocks()->attach($generic->id, ['order' => 0, 'column_index' => 1]);
        });
    }
}
