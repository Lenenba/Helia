<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\PageService;
use App\Services\PageSyncService;
use App\Http\Requests\PageRequest;
use App\Services\ModelStatsService;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\PageUpdateRequest;
use App\Support\Helpers\AvailableElements;
use App\Support\Transformers\PageTransformer;

class PageController extends Controller
{

    public function __construct(protected PageService $pages) {}
    /**
     * Display the specified resource.
     *
     * @param string|null $slug
     * @return \Inertia\Response
     */
    public function show(?string $slug = null)
    {

        $payload = $slug
            ? $this->pages->getRenderedBySlug($slug)
            : ($this->pages->getRenderedBySlug('home')
                ?? $this->pages->getRenderedHomeFallback());

        if (! $payload) {
            abort(404);
        }
        return Inertia::render('page/Show', $payload);
    }
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

        return Inertia::render('page/Create', [
            'availableElements' => AvailableElements::fetch(
                include: ['posts', 'blocks', 'medias', 'pages'], // ce dont la vue a besoin
                limit: 200,
                cacheTtl: 300
            ),
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
        $pageService->create($validatedData, $request->user());

        return redirect()
            ->route('pages.list')
            ->with('success', 'Page created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Page $page
     * @return Response
     */
    public function edit(Page $page): Response
    {
        $page->load(['sections' => function ($q) {
            $q->orderBy('order')
                ->with('blocks'); // si vous avez une relation blocks liée à section/column
        }]);

        $dto = app(PageTransformer::class)->toDto($page);

        return Inertia::render('page/Edit', [
            'page'              => $dto,
            'availableElements' => AvailableElements::fetch(
                include: ['posts', 'blocks', 'medias', 'pages', 'sections'],
                limit: 200,
                cacheTtl: 300
            ),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\PageUpdateRequest $request
     * @param Page $page
     * @return RedirectResponse
     */
    public function update(PageUpdateRequest $request, Page $page, PageService $service): RedirectResponse
    {
        $validated = $request->validated();
        $pruneOrphans = (bool) $request->boolean('prune_orphans', false);

        $service->update($page, $validated, $pruneOrphans);

        return redirect()
            ->route('pages.list')
            ->with('success', 'Page updated successfully.');
    }
}
