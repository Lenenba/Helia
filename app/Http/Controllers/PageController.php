<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Post;
use Inertia\Inertia;
use App\Models\Media;
use Inertia\Response;
use App\Services\PageService;
use App\Http\Requests\PageRequest;
use App\Services\ModelStatsService;
use Illuminate\Http\RedirectResponse;

class PageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param ModelStatsService $statsService
     * @return Response
     */
    public function index(ModelStatsService $statsService): Response
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
     * @return Response
     */
    public function create(): Response
    {
        $elementConfig = [
            'posts' => [
                'model' => Post::class,
                'mapper' => function (Post $post) {
                    return [
                        'id' => $post->id,
                        'title' => $post->title,
                        'label' => $post->title,
                        'excerpt' => $post->excerpt,
                        'body' => $post->content,
                        'cover_url' => $post->coverUrl,
                        'image_position' => $post->image_position ?? 'left',
                        'type' => 'post',
                    ];
                }
            ],
            'medias' => [
                'model' => Media::class,
                'mapper' => function (Media $media) {
                    return [
                        'id' => $media->id,
                        'url' => $media->getUrl(),
                        'title' => $media->file_name,
                        'label' => $media->file_name,
                        'mime' => $media->mime_type,
                        'type' => 'media',
                    ];
                }
            ],
        ];

        $availableElements = [];

        // La boucle est maintenant plus simple et plus puissante.
        foreach ($elementConfig as $key => $config) {
            $modelClass = $config['model'];
            $mapper = $config['mapper'];

            $items = $modelClass::query()->latest()->get();

            $availableElements[$key] = $items->map($mapper);
        }

        return Inertia::render('page/Create', [
            'availableElements' => $availableElements,
        ]);
    }

    /**
     * Store a new page (thin controller).
     *
     * @param PageRequest $request
     * @param PageService $pageService
     *
     * @return RedirectResponse
     */
    public function store(PageRequest $request, PageService $pageService): RedirectResponse
    {
        $validatedData = $request->validated();
        $pageService->handle($validatedData, $request->user());

        return redirect()
            ->route('pages.list')
            ->with('success', 'Page created successfully.');
    }
}
