<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use App\Services\MenuService;
use App\Http\Requests\MenuItemRequest;
use Inertia\Controller;

class MenuItemController extends Controller
{
    /**
     * @var MenuService
     */
    protected $menuService;

    public function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;
    }

    public function updateTree(Request $request, Menu $menu)
    {
        $data = $request->validate([
            'tree' => ['required', 'array'],
        ]);

        $this->menuService->persistTree($menu->id, $data['tree']);

        return back()->with('success', 'Tree saved.');
    }

    public function addItem(MenuItemRequest $request, Menu $menu)
    {
        $payload = $request->validated();
        $this->menuService->createItem($menu, $payload);

        return back()->with('success', 'Item added.');
    }

    public function updateItem(MenuItemRequest $request, Menu $menu, MenuItem $item)
    {
        $item->update($request->validated());

        return back()->with('success', 'Item updated.');
    }

    public function deleteItem(Menu $menu, MenuItem $item)
    {
        $item->delete();

        return back()->with('success', 'Item deleted.');
    }
}
