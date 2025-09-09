<?php

namespace App\Http\Middleware;

use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;
use Illuminate\Http\Request;
use App\Services\MenuService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Traits\UtilsPhotoConverter;

class HandleInertiaRequests extends Middleware
{
    use UtilsPhotoConverter;
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');
        $user = $request->user();
        $cacheKey = 'menu_tree_main';
        $menuTree = Cache::remember($cacheKey, now()->addMinutes(60), function () {
            // This code runs only if the cache is empty.
            return app(MenuService::class)->getPublicTree('main');
        });

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
                'profilPicture' =>  $this->convertToWebp($user?->media()->isProfilePicture()->first()->file_path ?? ''),
            ],
            'ziggy' => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error')
            ],
            'menu' => [
                'tree' => $menuTree,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
