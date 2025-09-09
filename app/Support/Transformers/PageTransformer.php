<?php

namespace App\Support\Transformers;

use App\Models\Page;
use Illuminate\Support\Str;

class PageTransformer
{
    /**
     * Transform a Page Eloquent model into a DTO for the frontend editor.
     *
     * Structure renvoyée (simplifiée) :
     * [
     *   'id' => int,
     *   'title' => string,
     *   'slug' => ?string,
     *   'type' => 'page'|'post'|'custom',
     *   'status' => 'draft'|'review'|'published',
     *   'parent_id' => ?int,
     *   'sections' => [
     *     [
     *       'id' => string,
     *       'title' => string,
     *       'ui_type' => '1 column'|'2 columns'|'3 columns'|'4 columns',
     *       'db_type' => ?string,
     *       'order' => int, // ordre d’affichage (utilisé côté UI)
     *       'settings' => array,
     *       'layout' => [
     *         'columns_count' => int,
     *         'columns' => [
     *           [
     *             'id' => string,
     *             'index' => int,
     *             'blocks' => [
     *               [
     *                 'id' => string,         // id du wrapper Block
     *                 'contentId' => int,     // post/media => blockable_id ; block générique => wrapper id
     *                 'contentType' => 'post'|'media'|'block',
     *                 'title' => string,
     *                 'order' => int
     *               ],
     *               ...
     *             ]
     *           ],
     *           ...
     *         ]
     *       ]
     *     ],
     *     ...
     *   ]
     * ]
     */
    public function toDto(Page $page): array
    {
        // On trie par l'ordre du pivot page_section.order (ordre des sections dans la page)
        $sections = $page->sections()
            ->with(['blocks.blockable'])     // pour résoudre le morph
            ->orderBy('page_section.order')  // important si sections.order n'existe pas
            ->get();

        return [
            'id'        => $page->id,
            'title'     => $page->title,
            'slug'      => $page->slug,
            'type'      => $page->type,
            'status'    => $page->status,
            'parent_id' => $page->parent_id,
            'sections'  => $sections->map(function ($section, $idx) {
                // 1) Columns count : depuis settings.layout si présent, sinon déduction sur le pivot block_section
                $layout   = $section->settings['layout'] ?? null;
                $colsCnt  = $layout['columns_count'] ?? null;

                if (!$colsCnt) {
                    $maxColIndex = $section->blocks->max('pivot.column_index') ?? 0;
                    $colsCnt = max(1, $maxColIndex + 1);
                }

                // 2) Initialiser les colonnes
                $columns = [];
                for ($i = 0; $i < $colsCnt; $i++) {
                    $columns[$i] = [
                        'id'     => (string) ($section->id . ':' . $i),
                        'index'  => $i,
                        'blocks' => [],
                    ];
                }

                // 3) Dispatcher les blocks dans leurs colonnes (via pivot column_index)
                foreach ($section->blocks as $blk) {
                    $contentType = $this->normalizeTypeFromMorph((string) $blk->blockable_type);

                    // IMPORTANT
                    // - post/media  -> contentId = blockable_id (id du Post/Media)
                    // - block (générique) -> contentId = id du wrapper Block (blk->id)
                    if ($contentType === 'block') {
                        $contentId = (int) $blk->id; // wrapper id
                        $title     = $blk->settings['title'] ?? 'Untitled block';
                    } else {
                        $contentId = (int) $blk->blockable_id;
                        $title     = $this->inferTitle($blk->blockable);
                    }

                    $col = (int) ($blk->pivot->column_index ?? 0);
                    $col = ($col >= 0 && $col < $colsCnt) ? $col : 0;

                    $columns[$col]['blocks'][] = [
                        'id'          => (string) $blk->id,
                        'contentId'   => $contentId,
                        'contentType' => $contentType, // 'post'|'media'|'block'
                        'title'       => $title,
                        'order'       => (int) ($blk->pivot->order ?? 0),
                    ];
                }

                // 4) Ordonner les blocks dans chaque colonne
                foreach ($columns as &$c) {
                    usort($c['blocks'], fn($a, $b) => ($a['order'] <=> $b['order']));
                }

                // 5) ui_type : settings['ui_type'] prioritaire, sinon mapping db_type/colsCnt
                $uiType = $section->settings['ui_type'] ?? $this->dbTypeToUi($section->db_type, $colsCnt);

                return [
                    'id'       => (string) $section->id,
                    'title'    => $section->title,
                    'ui_type'  => $uiType,
                    'db_type'  => $section->db_type,
                    // L'ordre pour le front : on lit d'abord le pivot ; sinon on retombe sur l'index courant
                    'order'    => (int) ($section->pivot->order ?? $idx),
                    'settings' => $section->settings ?? [],
                    'layout'   => [
                        'columns_count' => $colsCnt,
                        'columns'       => array_values($columns),
                    ],
                ];
            })->values()->all(),
        ];
    }

    /**
     * Déduit le type de contenu à partir du morph type
     * (ex. App\Models\Post -> 'post', App\Models\Media -> 'media', sinon 'block').
     */
    protected function normalizeTypeFromMorph(string $morph): string
    {
        $m = Str::lower(class_basename($morph));
        if (str_contains($m, 'media')) return 'media';
        if (str_contains($m, 'post'))  return 'post';
        return 'block';
    }

    /**
     * Mappe db_type (one_column/two_columns/gallery) et/ou le nb de colonnes vers le label UI.
     */
    protected function dbTypeToUi(?string $dbType, int $colsCnt): string
    {
        return match ($dbType) {
            'one_column'  => '1 column',
            'two_columns' => '2 columns',
            'gallery'     => $colsCnt === 3 ? '3 columns' : '4 columns',
            default       => match ($colsCnt) {
                1 => '1 column',
                2 => '2 columns',
                3 => '3 columns',
                default => '4 columns',
            },
        };
    }

    /**
     * Titre "humain" de secours pour le blockable.
     */
    protected function inferTitle(?object $blockable): string
    {
        if (!$blockable) return 'Untitled';
        foreach (['title', 'name', 'file_name', 'filename', 'label'] as $k) {
            if (isset($blockable->{$k}) && $blockable->{$k}) {
                return (string) $blockable->{$k};
            }
        }
        return 'Untitled';
    }

    /**
     * Convert a Page (with loaded relations) into a clean Inertia payload.
     * Keep frontend stable & decoupled from DB details.
     */
    public function toInertia(Page $page): array
    {
        return [
            // Page/SEO basics
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'excerpt' => (string) data_get($page, 'excerpt'),
                'seo' => [
                    'title' => $page->seo_title ?? $page->title,
                    'description' => $page->seo_description ?? $page->excerpt,
                    'image' => $page->seo_image_url,
                ],
                'layout' => $page->layout ?? 'default',
            ],

            // Sections & blocks normalized
            'sections' => collect($page->sections ?? [])->map(function ($section) {
                return [
                    'id' => $section->id,
                    'title' => $section->title,
                    'key' => $section->key ?? null,
                    'settings' => $section->settings ?? [],
                    'blocks' => collect($section->blocks ?? [])->map(function ($block) {
                        // Generic block envelope
                        return [
                            'id' => $block->id,
                            'type' => $block->type, // e.g. "rich_text", "image", "post_teaser"
                            'weight' => $block->weight,
                            'settings' => $block->settings ?? [],
                            // Optional resolved data from blockable
                            'data' => $this->mapBlockable($block->blockable, $block->type),
                        ];
                    })->values(),
                ];
            })->values(),
        ];
    }

    /** Map blockable morphs to a small, typed payload for the UI. */
    private function mapBlockable(?object $blockable, ?string $type): array
    {
        if (!$blockable) return [];

        return match ($type) {
            'image' => [
                'url' => $blockable->url ?? $blockable->file_url ?? null,
                'alt' => $blockable->alt ?? $blockable->title ?? '',
                'caption' => $blockable->caption ?? null,
                'ratio' => $blockable->ratio ?? null,
            ],
            'post_teaser' => [
                'title' => $blockable->title ?? '',
                'slug'  => $blockable->slug ?? '',
                'excerpt' => $blockable->excerpt ?? '',
                'cover' => $blockable->cover_url ?? null,
                'href'  => $blockable->slug ? route('posts.show', $blockable->slug) : '#',
            ],
            default => [
                // For "rich_text" or custom components, just return relevant fields.
                // Assume block.settings already contains the HTML or content structure.
            ],
        };
    }
}
