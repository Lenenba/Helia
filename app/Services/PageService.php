<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Post;
use App\Models\Block;
use App\Models\Media;
use App\Models\Section;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\Support\UniqueSlugService;
use App\Support\Transformers\PageTransformer;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * PageService
 *
 * Single orchestrator for creating/updating a Page and syncing its Sections/Blocks.
 */
class PageService
{
    /** Map UI contentType -> morph class for Block wrappers. */
    protected array $typeMap = [
        'post'  => Post::class,
        'media' => Media::class,
        'block' => Block::class,
    ];

    public function __construct(
        protected UniqueSlugService $slugger,
        protected PageTransformer $transformer
    ) {}

    /** Create a new Page and fully sync its sections/blocks. */
    public function create(array $payload, ?Authenticatable $author = null): Page
    {
        return DB::transaction(function () use ($payload, $author) {
            $page = $this->createPage($payload, $author);
            $this->replaceSectionsForPage($page, (array) ($payload['sections'] ?? []));
            return $page->refresh();
        });
    }

    /**
     * Update an existing Page and fully resync its sections/blocks (replace strategy).
     *
     * @param Page|int $page
     */
    public function update(Page|int $page, array $payload, bool $pruneOrphanSections = false): Page
    {
        $page = $page instanceof Page ? $page : Page::query()->findOrFail($page);

        return DB::transaction(function () use ($page, $payload, $pruneOrphanSections) {
            $this->fillAndSavePage($page, $payload);
            $this->replaceSectionsForPage($page, (array) ($payload['sections'] ?? []), $pruneOrphanSections);
            return $page->refresh();
        });
    }

    // ---------------------------------------------------------------------
    // Page helpers
    // ---------------------------------------------------------------------

    /** Create the Page row from payload. */
    protected function createPage(array $payload, ?Authenticatable $author): Page
    {
        $status = (string) ($payload['status'] ?? 'draft');
        $isPublished = $status === 'published';

        // ✅ positional call (no named args) for maximum compatibility
        $slug = $this->slugger->makeUnique(
            'pages',
            'slug',
            $payload['slug'] ?? null,
            (string) $payload['title']
        );

        return Page::create([
            'title'        => (string) $payload['title'],
            'slug'         => $slug,
            'excerpt'      => $payload['excerpt'] ?? null,
            'is_published' => $isPublished,
            'type'         => (string) ($payload['type'] ?? 'page'),
            'status'       => $status,
            'published_at' => $isPublished ? now() : null,
            'settings'     => $payload['settings'] ?? null,
            'author_id'    => $author?->getAuthIdentifier(),
            'parent_id'    => $payload['parent_id'] ?? null,
        ]);
    }

    /** Update mutable fields on an existing Page. */
    protected function fillAndSavePage(Page $page, array $payload): void
    {
        $status = (string) ($payload['status'] ?? $page->status);
        $isPublished = $status === 'published';

        // Keep current slug unless a new one is provided; enforce uniqueness when changed.
        $slug = $payload['slug'] ?? $page->slug;
        if (array_key_exists('slug', $payload) && $payload['slug'] !== $page->slug) {
            // ✅ positional call (no named args)
            $slug = $this->slugger->makeUnique(
                'pages',
                'slug',
                $payload['slug'],
                $page->title
            );
        }

        $page->fill([
            'title'        => (string) ($payload['title'] ?? $page->title),
            'slug'         => $slug,
            'excerpt'      => $payload['excerpt'] ?? $page->excerpt,
            'is_published' => $isPublished,
            'type'         => (string) ($payload['type'] ?? $page->type),
            'status'       => $status,
            'published_at' => $isPublished ? ($page->published_at ?? now()) : null,
            'settings'     => $payload['settings'] ?? $page->settings,
            'parent_id'    => $payload['parent_id'] ?? $page->parent_id,
        ])->save();
    }

    // ---------------------------------------------------------------------
    // Sections orchestration (replace strategy)
    // ---------------------------------------------------------------------

    protected function replaceSectionsForPage(Page $page, array $sectionsPayload, bool $pruneOrphanSections = false): void
    {
        $currentlyAttachedIds = $page->sections()->pluck('sections.id')->all();
        $page->sections()->detach();

        if ($pruneOrphanSections && !empty($currentlyAttachedIds)) {
            Section::query()
                ->whereIn('id', $currentlyAttachedIds)
                ->whereDoesntHave('pages')
                ->delete();
        }

        $ordered = array_values($sectionsPayload);

        foreach ($ordered as $secIndex => $sectionPayload) {
            $section = $this->materializeSection($sectionPayload);
            $page->sections()->attach($section->id, ['order' => $secIndex + 1]);
            $this->syncSectionBlocksFromLayout($section, (array) data_get($sectionPayload, 'layout'));
        }
    }

    protected function materializeSection(array $sectionPayload): Section
    {
        $columnsCount = (int) data_get($sectionPayload, 'layout.columns_count', 1);
        $uiTypeLabel  = (string) data_get($sectionPayload, 'ui_type', '1 column');

        $dbType = $this->mapUiTypeToDbEnum(
            uiType: $uiTypeLabel,
            hint: data_get($sectionPayload, 'db_type_hint'),
            columnsCount: $columnsCount
        );

        $section = new Section();
        $section->title        = data_get($sectionPayload, 'title');
        $section->is_published = (bool) data_get($sectionPayload, 'is_published', false);
        $section->type         = $dbType;
        $section->color        = (string) data_get($sectionPayload, 'color', '#ffffff');
        $section->slug         = data_get($sectionPayload, 'slug');
        $section->settings     = [
            'columns_count'            => $columnsCount,
            'ui_label'                 => $uiTypeLabel,
            'columns_layout_block_ids' => [], // updated after syncing blocks
        ];
        $section->save();

        return $section;
    }

    protected function syncSectionBlocksFromLayout(Section $section, ?array $layout): void
    {
        $section->blocks()->detach();

        if (!$layout || empty($layout['columns']) || !is_array($layout['columns'])) {
            $this->updateSectionColumnsLayoutSetting($section, []);
            return;
        }

        $overallOrder = 0;
        $columnsLayoutBlockIds = [];
        $columns = array_values($layout['columns']);

        foreach ($columns as $col) {
            $colIndex = (int) data_get($col, 'index', 0);
            $columnsLayoutBlockIds[$colIndex] = [];

            $blocks = array_values((array) data_get($col, 'blocks', []));
            foreach ($blocks as $b) {
                $morphClass  = $this->resolveMorphClass((string) ($b['contentType'] ?? 'block'));
                $blockableId = (int) ($b['contentId'] ?? 0);
                $wrapper     = $this->firstOrCreateBlockWrapper($morphClass, $blockableId);

                $section->blocks()->attach($wrapper->id, [
                    'order'        => ++$overallOrder,
                    'column_index' => $colIndex,
                ]);

                $columnsLayoutBlockIds[$colIndex][] = $wrapper->id;
            }
        }

        $this->updateSectionColumnsLayoutSetting($section, $columnsLayoutBlockIds);
    }

    protected function updateSectionColumnsLayoutSetting(Section $section, array $columnsLayoutBlockIds): void
    {
        $settings = (array) ($section->settings ?? []);
        $settings['columns_layout_block_ids'] = $columnsLayoutBlockIds;
        $section->settings = $settings;
        $section->save();
    }

    // ---------------------------------------------------------------------
    // Utilities
    // ---------------------------------------------------------------------

    protected function mapUiTypeToDbEnum(string $uiType, ?string $hint, int $columnsCount): string
    {
        $normalizedHint = $hint ? strtolower(trim($hint)) : null;
        $normalizedHint = match ($normalizedHint) {
            'tree_columns' => 'three_columns',
            'for_columns'  => 'four_columns',
            default        => $normalizedHint,
        };

        if (in_array($normalizedHint, ['one_column', 'two_columns', 'three_columns', 'four_columns', 'hero', 'gallery'], true)) {
            return $normalizedHint;
        }

        $norm = strtolower(trim($uiType));

        return match (true) {
            $norm === '1 column'  => 'one_column',
            $norm === '2 columns' => 'two_columns',
            $norm === '3 columns' => 'three_columns',
            $norm === '4 columns' => 'four_columns',
            $columnsCount >= 4    => 'four_columns',
            $columnsCount === 3   => 'three_columns',
            $columnsCount === 2   => 'two_columns',
            default               => 'one_column',
        };
    }

    protected function resolveMorphClass(string $uiType): string
    {
        $key = strtolower($uiType);
        if (!isset($this->typeMap[$key])) {
            throw new ModelNotFoundException("Unknown contentType '{$uiType}'");
        }
        return $this->typeMap[$key];
    }

    protected function firstOrCreateBlockWrapper(string $blockableClass, int $blockableId): Block
    {
        $existing = Block::query()
            ->where('blockable_type', $blockableClass)
            ->where('blockable_id', $blockableId)
            ->first();

        if ($existing) {
            return $existing;
        }

        $block = new Block();
        $block->blockable_type = $blockableClass;
        $block->blockable_id   = $blockableId;
        $block->template_hint  = null;
        $block->settings       = [];
        $block->save();

        return $block;
    }

    /** Fetch page with sections/blocks and transform to Inertia props. */
    public function getRenderedBySlug(string $slug): ?array
    {
        $cacheKey = "page_rendered:$slug";

        $page = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($slug) {
            return Page::query()
                ->where('slug', $slug)
                ->where('is_published', true)
                ->with([
                    // Assuming relations exist:
                    // sections (ordered), blocks (ordered), blockable morphs, media, etc.
                    'sections' => fn($q) => $q->orderBy('weight'),
                    'sections.blocks' => fn($q) => $q->orderBy('weight'),
                    'sections.blocks.blockable', // morphTo
                ])
                ->first();
        });

        if (!$page) {
            return null;
        }

        return $this->transformer->toInertia($page);
    }

    /** Fallback if slug 'home' missing, pick first published page flagged as home. */
    public function getRenderedHomeFallback(): ?array
    {
        $page = Page::query()
            ->where(fn($q) => $q->where('slug', 'home'))
            ->where('is_published', true)
            ->with([
                'sections' => fn($q) => $q->orderBy('weight'),
                'sections.blocks' => fn($q) => $q->orderBy('weight'),
                'sections.blocks.blockable',
            ])
            ->first();

        return $page ? $this->transformer->toInertia($page) : null;
    }

    /** Invalidate cache after write operations. Call in observers. */
    public static function bustCacheFor(Page $page): void
    {
        Cache::forget("page_rendered:{$page->slug}");
    }
}
