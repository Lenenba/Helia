<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Post;
use Inertia\Inertia;
use App\Models\Block;
use App\Models\Media;
use App\Models\Section;
use Illuminate\Support\Str;
use App\Services\ModelStatsService;

class PageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param ModelStatsService $statsService
     * @return \Inertia\Response
     */
    public function index(ModelStatsService $statsService)
    {
        $pages = Page::all();
        $stats = $statsService->compute($pages, 'type');

        return Inertia::render('page/Index', [
            'pages' => $pages,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        // Define which models to fetch and which column to use for the display label.
        $elementConfig = [
            'posts' => ['model' => Post::class, 'label_column' => 'title'],
            'pages' => ['model' => Page::class, 'label_column' => 'title'],
            'blocks' => ['model' => Block::class, 'label_column' => 'name'], // Adjust 'name' if your column is different
            'medias' => ['model' => Media::class, 'label_column' => 'file_name'],
        ];

        $availableElements = [];

        // Loop through the configuration to build standardized lists.
        foreach ($elementConfig as $key => $config) {
            $modelClass = $config['model'];
            $labelColumn = $config['label_column'];
            $type = Str::singular($key); // e.g., 'posts' becomes 'post'

            $availableElements[$key] = $modelClass::query()
                // 1. Select only the ID and the label column for efficiency.
                ->select('id', "{$labelColumn} as label")
                ->get()
                // 2. Map the results to a standard format.
                ->map(fn($item) => [
                    'id' => $item->id,
                    'label' => $item->label,
                    'type' => $type,
                ]);
        }

        return Inertia::render('page/Create', [
            'availableElements' => $availableElements,
        ]);
    }
}
