<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Inertia\Inertia;
use App\Services\MenuService;
use App\Http\Requests\MenuRequest;

class MenuController extends Controller
{
    public function index()
    {
        $menus = Menu::withCount('roots')->get();
        return Inertia::render('menu/Index', ['menus' => $menus]);
    }

    public function create()
    {
        return Inertia::render('menu/Create');
    }

    public function store(MenuRequest $request)
    {
        $validated = $request->validated();

        // 1. Initially set the settings to an empty JSON array for a new menu.
        $validated['settings'] = json_encode([]);

        $menu = Menu::create($validated);

        return redirect()->route('menus.edit', $menu)->with('success', 'Menu created');
    }

    public function edit(Menu $menu)
    {
        $menu->load('tree');
        return Inertia::render('menu/Edit', [
            'menu' => $menu,
            'tree' => $menu->tree,
        ]);
    }

    public function update(MenuRequest $request, Menu $menu, MenuService $menuService)
    {
        $validated = $request->validated();

        // 1. Get the menu tree from the validated data.
        $menuTree = $validated['tree'] ?? [];

        // 2. Override the 'settings' field with the JSON of the current tree.
        $validated['settings'] = json_encode($menuTree);

        // 3. Update the menu details, including the new 'settings' value.
        $menu->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'settings' => $validated['settings'],
        ]);

        // 4. Synchronize the menu item tree.
        $existingItemIds = [];
        $menuService->syncTree($menu, $menuTree, null, $existingItemIds);

        // 5. Delete any menu items that are no longer in the new tree.
        $menu->tree()->whereNotIn('id', $existingItemIds)->delete();

        return redirect()->route('menus.index')->with('success', 'Menu updated');
    }

    public function destroy(Menu $menu)
    {
        $menu->delete();
        return redirect()->route('menus.index')->with('success', 'Menu deleted');
    }
}
