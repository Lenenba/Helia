<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Section;
use App\Models\Block;
use App\Models\Post;
use App\Models\Media;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PageSyncService
{
    /** Map UI 'contentType' -> morph class */
    protected array $typeMap = [
        'post'  => Post::class,
        'media' => Media::class,
        'block' => Block::class, // si tu autorises un block qui wrappe un autre block (rare)
    ];

    public function sync(Page $page, array $data): void
    {
        DB::transaction(function () use ($page, $data) {
            $page->fill(Arr::only($data, ['title', 'slug', 'type', 'status', 'parent_id']))->save();

            // --- Upsert sections ---
            $existingSections = $page->sections()->get()->keyBy('id');
            $keptSectionIds = [];

            foreach ($data['sections'] ?? [] as $sIdx => $s) {
                /** @var Section $section */
                $section = $existingSections->get($s['id']) ?? $page->sections()->make(['id' => $s['id']]);
                $section->title   = $s['title'];
                $section->order   = $s['order'] ?? $sIdx;
                $section->db_type = $s['db_type_hint'] ?? $section->db_type;

                // On range `ui_type` + `layout` dans settings
                $settings = $section->settings ?? [];
                $settings['ui_type'] = $s['ui_type'] ?? ($settings['ui_type'] ?? null);
                $settings['layout']  = $s['layout']  ?? ($settings['layout']  ?? null);
                $settings['color']   = $s['color']   ?? ($settings['color']   ?? '#ffffff');
                $section->settings   = $settings;

                $section->save();
                $keptSectionIds[] = $section->id;

                // --- Sync blocks on pivot ---
                $this->syncSectionBlocksFromLayout($section, $s['layout'] ?? null);
            }

            // Soft delete des sections non gardées
            $page->sections()->whereNotIn('id', $keptSectionIds)->delete();
        });
    }

    protected function syncSectionBlocksFromLayout(Section $section, ?array $layout): void
    {
        // détacher tout puis rattacher (simple et robuste).
        // Si tu veux faire du diff minimal, fais un calcul d’écarts.
        $section->blocks()->detach();

        if (!$layout || empty($layout['columns'])) {
            return;
        }

        foreach ($layout['columns'] as $cIdx => $col) {
            foreach (($col['blocks'] ?? []) as $bIdx => $b) {
                // 1) Résoudre morph
                $morphClass = $this->resolveMorphClass($b['contentType'] ?? 'block');
                $blockableId = (int) $b['contentId'];

                // 2) Créer/trouver le wrapper Block
                $wrapper = $this->firstOrCreateBlockWrapper($morphClass, $blockableId);

                // 3) Attacher avec pivot
                $section->blocks()->attach($wrapper->id, [
                    'order'         => $b['order'] ?? $bIdx,
                    'column_index'  => $cIdx,
                ]);
            }
        }
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
        /** @var Block|null $existing */
        $existing = Block::query()
            ->where('blockable_type', $blockableClass)
            ->where('blockable_id', $blockableId)
            ->first();

        if ($existing) return $existing;

        $block = new Block();
        $block->blockable_type = $blockableClass;
        $block->blockable_id   = $blockableId;
        $block->template_hint  = null;
        $block->settings       = [];
        $block->save();

        return $block;
    }
}
