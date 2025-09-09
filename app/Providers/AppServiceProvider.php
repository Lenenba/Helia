<?php

namespace App\Providers;

use App\Models\Menu;
use Inertia\Inertia;
use App\Models\MenuItem;
use App\Observers\MenuObserver;
use App\Observers\MenuItemObserver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Menu::observe(MenuObserver::class);
        MenuItem::observe(MenuItemObserver::class);

        Inertia::share('menus', function () {
            return Cache::remember('menus.shared', 300, function () {
                $mapItem = function ($item) use (&$mapItem) {
                    return [
                        'id' => $item->id,
                        'label' => $item->label,
                        'href' => $item->href,
                        'visible' => $item->is_visible,
                        'meta' => $item->meta,
                        'children' => $item->childrenRecursive->map(fn($c) => $mapItem($c))->toArray(),
                    ];
                };

                return Menu::with('tree')->get()
                    ->map(fn($m) => [
                        'id' => $m->id,
                        'slug' => $m->slug,
                        'name' => $m->name,
                        'items' => $m->tree->map(fn($i) => $mapItem($i)),
                    ])
                    ->keyBy('slug')
                    ->toArray();
            });
        });
    }
}
