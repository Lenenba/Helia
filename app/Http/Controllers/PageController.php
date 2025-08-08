<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Inertia\Inertia;
use App\Services\ModelStatsService;

class PageController extends Controller
{
    public function index(ModelStatsService $statsService)
    {
        $pages = Page::all();
        $stats = $statsService->compute($pages, 'type');

        return Inertia::render('page/Index', [
            'pages' => $pages,
            'stats' => $stats,
        ]);
    }
}
