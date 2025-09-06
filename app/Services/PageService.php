<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Section;
use App\Services\Support\UniqueSlugService;
use App\Services\Blocks\BlockFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Auth\Authenticatable;

class PageService
{
    public function __construct(
        protected UniqueSlugService $slugger,
        protected BlockFactory $blockFactory,
    ) {}

    /**
     * Orchestre la création d'une Page et de ses dépendances.
     */
    public function handle(array $payload, ?Authenticatable $author): Page
    {
        return DB::transaction(function () use ($payload, $author) {
            $page = $this->createPage($payload, $author);
            $this->syncSectionsAndBlocks($page, $payload['sections'] ?? []);
            return $page;
        });
    }

    /**
     * Crée l'enregistrement principal de la Page.
     */
    private function createPage(array $payload, ?Authenticatable $author): Page
    {
        $isPublished = ($payload['status'] ?? 'draft') === 'published';
        $slug = $this->slugger->makeUnique('pages', 'slug', $payload['slug'] ?? null, $payload['title']);

        return Page::create([
            'title'        => $payload['title'],
            'slug'         => $slug,
            'excerpt'      => null,
            'is_published' => $isPublished,
            'type'         => $payload['type'] ?? 'page',
            'status'       => $payload['status'] ?? 'draft',
            'published_at' => $isPublished ? now() : null,
            'settings'     => null,
            'author_id'    => $author?->getAuthIdentifier(),
            'parent_id'    => $payload['parent_id'] ?? null,
        ]);
    }

    /**
     * Crée les sections et y attache les blocs.
     */
    private function syncSectionsAndBlocks(Page $page, array $sectionsPayload): void
    {
        foreach (array_values($sectionsPayload) as $secIndex => $sectionPayload) {
            // 1. Traiter les blocs et préparer les données pour la section et la table pivot
            $processedLayout = $this->processSectionLayout($sectionPayload);

            // 2. Préparer tous les attributs de la section AVANT la création
            $sectionAttributes = $this->prepareSectionAttributes(
                $sectionPayload,
                $secIndex,
                $processedLayout['columns_layout_block_ids']
            );

            // 3. Créer la section en UNE SEULE fois
            $section = $page->sections()->create($sectionAttributes);

            // 4. Attacher tous les blocs en UNE SEULE fois
            if (!empty($processedLayout['attach_map'])) {
                $section->blocks()->attach($processedLayout['attach_map']);
            }
        }
    }

    /**
     * Traite les colonnes et les blocs d'une section pour préparer la sauvegarde.
     * @return array{attach_map: array, columns_layout_block_ids: array}
     */
    private function processSectionLayout(array $sectionPayload): array
    {
        $attachMap = [];
        $columnsLayoutBlockIds = [];
        $overallOrder = 0;

        $columns = array_values((array) data_get($sectionPayload, 'layout.columns', []));

        foreach ($columns as $col) {
            $colIndex = (int) data_get($col, 'index', 0);
            $columnsLayoutBlockIds[$colIndex] = [];

            $blocks = array_values((array) data_get($col, 'blocks', []));
            foreach ($blocks as $blockPayload) {
                $blockId = $this->blockFactory->ensureFromPayload($blockPayload);
                $attachMap[$blockId] = ['order' => $overallOrder++];
                $columnsLayoutBlockIds[$colIndex][] = $blockId;
            }
        }

        return [
            'attach_map' => $attachMap,
            'columns_layout_block_ids' => $columnsLayoutBlockIds,
        ];
    }

    /**
     * Prépare le tableau d'attributs pour la création d'une section.
     */
    private function prepareSectionAttributes(array $sectionPayload, int $order, array $columnsLayout): array
    {
        $columnsCount = (int) data_get($sectionPayload, 'layout.columns_count', 1);
        $uiType = (string) data_get($sectionPayload, 'ui_type', '1 column');
        $dbType = $this->mapUiTypeToDbEnum($uiType, data_get($sectionPayload, 'db_type_hint'), $columnsCount);

        return [
            'title'        => data_get($sectionPayload, 'title'),
            'is_published' => false,
            'type'         => $dbType,
            'color'        => data_get($sectionPayload, 'color', '#ffffff'),
            'order'        => $order,
            'settings'     => [
                'columns_count'            => $columnsCount,
                'ui_label'                 => $uiType,
                'columns_layout_block_ids' => $columnsLayout, // Ajouté directement ici
            ],
        ];
    }

    /**
     * Map UI label to DB enum (avec PHP 8 match expression).
     */
    protected function mapUiTypeToDbEnum(string $uiType, ?string $hint, int $columnsCount): string
    {
        if (in_array($hint, ['one_column', 'two_columns', 'hero', 'gallery'], true)) {
            return $hint;
        }

        $norm = strtolower(trim($uiType));

        return match (true) {
            $norm === '1 column' => 'one_column',
            $norm === '2 columns' => 'two_columns',
            in_array($norm, ['3 columns', '4 columns']) => 'gallery',
            $columnsCount >= 3 => 'gallery',
            $columnsCount === 2 => 'two_columns',
            default => 'one_column',
        };
    }
}
