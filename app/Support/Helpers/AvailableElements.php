<?php

namespace App\Support\Helpers;

use App\Models\Page;
use App\Models\Post;
use App\Models\Block;
use App\Models\Media;
use App\Models\Section;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Centralizes fetching/mapping of "availableElements" for the page builder.
 *
 * Usage:
 *   AvailableElements::fetch();                              // default: posts, blocks, medias, pages
 *   AvailableElements::fetch(include: ['posts','medias']);   // choose what to include
 *   AvailableElements::fetch(limit: 100, cacheTtl: 300);     // limit + cache 5min
 */
class AvailableElements
{
    /**
     * Fetch and map available elements for the editor/preview.
     *
     * @param array        $include  keys to include: ['posts','blocks','medias','pages']
     * @param int|null     $limit    per-model limit (null = no limit)
     * @param int|null     $cacheTtl seconds for caching (null = no cache)
     * @return array
     */
    public static function fetch(
        array $include = ['posts', 'blocks', 'medias', 'pages', 'sections'],
        ?int $limit = 200,
        ?int $cacheTtl = 300
    ): array {
        $include = array_values(array_unique($include));

        // Config des sources + mappers (inspiré de ton code)
        $config = [
            'posts' => [
                'model'  => Post::class,
                'query'  => fn($q) => $q->latest(), // customise si besoin (->published(), ->with(...))
                'mapper' => function (Post $post): array {
                    return [
                        'id'             => $post->id,
                        'title'          => $post->title,
                        'label'          => $post->title,       // utilisé côté BlockManager
                        'excerpt'        => $post->excerpt,
                        'body'           => $post->content,
                        'cover_url'      => $post->cover_url ?? $post->coverUrl ?? null,
                        'image_position' => $post->image_position ?? 'left',
                        'type'           => 'post',             // singular (important pour PreviewBlock)
                    ];
                },
            ],

            'medias' => [
                'model'  => Media::class,
                'query'  => fn($q) => $q->latest(),
                'mapper' => function (Media $media): array {
                    // Spatie Media Library: file_name, mime_type, getUrl()
                    return [
                        'id'    => $media->id,
                        'url'   => method_exists($media, 'getUrl') ? $media->getUrl() : ($media->url ?? null),
                        'title' => $media->file_name ?? $media->title ?? '',
                        'label' => $media->file_name ?? $media->title ?? '',
                        'mime'  => $media->mime_type ?? $media->mime ?? null,
                        'type'  => 'media',
                    ];
                },
            ],

            'blocks' => [
                'model'  => \App\Models\Block::class,
                'query'  => fn($q) => $q->latest(),
                'mapper' => function (\App\Models\Block $block): array {
                    $s = $block->settings ?? [];
                    return [
                        'id'      => $block->id,
                        'title'   => $s['title']   ?? 'Untitled block',
                        'label'   => $s['title']   ?? 'Untitled block',
                        'excerpt' => $s['excerpt'] ?? null,
                        'image'   => $s['image']   ?? null,
                        'html'    => $s['html']    ?? null,
                        'type'    => 'block',
                    ];
                },
            ],
            'pages' => [
                'model'  => Page::class,
                'query'  => fn($q) => $q->orderBy('title'),
                'mapper' => function (Page $page): array {
                    return [
                        'id'    => $page->id,
                        'title' => $page->title,
                        'label' => $page->title,
                        'type'  => 'page',
                    ];
                },
            ],

            'sections' => [
                'model'  => Section::class,
                'query'  => fn($q) => $q->orderBy('title'),
                'mapper' => function ($section): array {
                    return [
                        'id'    => $section->id,
                        'title' => $section->title,
                        'label' => $section->title,
                    ];
                },
            ],
        ];

        // Filtrer par include
        $config = Arr::only($config, $include);

        $runner = function () use ($config, $limit): array {
            $out = [];
            foreach ($config as $key => $cfg) {
                $model = $cfg['model'];
                $query = $model::query();
                if (isset($cfg['query']) && is_callable($cfg['query'])) {
                    $cfg['query']($query);
                }
                if ($limit) {
                    $query->limit($limit);
                }

                /** @var Collection $items */
                $items = $query->get();

                $mapped = $items->map($cfg['mapper'])->values()->all();
                $out[$key] = $mapped;
            }
            return $out;
        };

        if ($cacheTtl !== null) {
            $cacheKey = self::makeCacheKey($include, $limit);
            return Cache::remember($cacheKey, $cacheTtl, $runner);
        }

        return $runner();
    }

    protected static function makeCacheKey(array $include, ?int $limit): string
    {
        sort($include);
        return 'available_elements:' . md5(json_encode([$include, $limit]));
    }
}
