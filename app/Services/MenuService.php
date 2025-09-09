<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\MenuItem;

class MenuService
{
    /**
     * Synchronizes the menu item tree with the database.
     * This method handles creating, updating, and deleting menu items.
     *
     * @param Menu $menu The menu instance to update.
     * @param array<int, array> $nodes An array of menu item nodes.
     * @param int|null $parentId The parent ID for the current level of nodes.
     * @param array $existingItemIds A reference to an array of existing item IDs to track which ones are kept.
     * @return void
     */
    public function syncTree(Menu $menu, array $nodes, ?int $parentId = null, array &$existingItemIds = []): void
    {
        foreach ($nodes as $index => $node) {
            $menuItem = MenuItem::firstOrNew(['id' => $node['id'], 'menu_id' => $menu->id]);

            $menuItem->fill([
                'menu_id' => $menu->id,
                'label' => $node['label'],
                'url' => $node['url'] ?? null,
                'is_visible' => $node['is_visible'] ?? true,
                'parent_id' => $parentId,
                'position' => $index,
                'linkable_type' => $node['linkable_type'] ?? null,
                'linkable_id' => $node['linkable_id'] ?? null,
            ]);

            $menuItem->save();

            $existingItemIds[] = $menuItem->id;

            if (!empty($node['children']) && is_array($node['children'])) {
                $this->syncTree($menu, $node['children'], $menuItem->id, $existingItemIds);
            }
        }
    }

    /**
     * Recursively flattens the tree to get all item IDs.
     *
     * @param array $nodes
     * @param array $ids
     * @return array
     */
    protected function flattenTreeIds(array $nodes, array &$ids = []): array
    {
        foreach ($nodes as $node) {
            // Check if the node's ID is a number (meaning it already exists in the database)
            if (is_numeric($node['id'])) {
                $ids[] = (int) $node['id'];
            }
            if (!empty($node['children'])) {
                $this->flattenTreeIds($node['children'], $ids);
            }
        }
        return $ids;
    }

    /**
     * Get a menu's tree of items, including their linked content (e.g., pages, posts).
     *
     * @param string $slug The slug of the menu to retrieve.
     * @return array
     */
    public function getPublicTree(string $slug): array
    {
        $menu = Menu::where('slug', $slug)->firstOrFail();

        // Recursively load all children and linked content.
        $menu->load([
            'tree.children.children.children' => function ($query) {
                $query->whereNotIn('linkable_type', ['custom', 'none']);
            },
            'tree' => function ($query) {
                $query->whereNotIn('linkable_type', ['custom', 'none']);
            },
        ]);

        // Use a conditional eager load to avoid trying to load a non-existent class.
        $menu->load([
            'tree.linkable',
            'tree.children.linkable',
            'tree.children.children.linkable',
            'tree.children.children.children.linkable',
        ]);

        // Extract and format the tree data.
        $tree = $this->buildTree($menu->tree->toArray());

        return $tree;
    }

    /**
     * Recursively builds a nested tree from a flat list of menu items.
     *
     * @param array $items
     * @param int|null $parentId
     * @return array
     */
    protected function buildTree(array $items, $parentId = null): array
    {
        $branch = [];

        foreach ($items as $item) {
            if ($item['parent_id'] === $parentId) {
                $item['slug'] = $item['linkable']['slug'] ?? null;
                $children = $this->buildTree($items, $item['id']);

                if ($children) {
                    $item['children'] = $children;
                }

                $branch[] = $item;
            }
        }

        return $branch;
    }
}
