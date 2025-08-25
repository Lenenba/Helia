<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Post;
use Inertia\Inertia;
use App\Models\Media;
use Inertia\Response;
use App\Http\Requests\PostRequest;
use App\Services\ModelStatsService;
use Illuminate\Http\RedirectResponse;
use App\Services\PostService; // Importer le service

class PostController extends Controller
{
    /**
     * Display a list of posts with stats.
     * @param ModelStatsService $statsService
     * @return Response
     * Note: This method uses the ModelStatsService to compute statistics
     */
    public function index(ModelStatsService $statsService): Response
    {
        // Amélioration : Utiliser latest() et with() pour éviter le problème N+1
        $posts = Post::latest()->with('coverImage', 'author')->get();
        $stats = $statsService->compute($posts, 'type');

        return Inertia::render('post/Index', [
            'posts' => $posts,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form to create a new post.
     * @return Response
     *
     * Note: This method loads media and tags to populate the form fields.
     * It uses Inertia to render the Vue component for the post creation form.
     */
    public function create(): Response
    {
        return Inertia::render('post/Create', [
            // Amélioration : Ne sélectionner que les colonnes nécessaires
            'media' => Media::select('id', 'file_path', 'file_name')->get(),
            'tags'  => Tag::select('id', 'name')->get(),
        ]);
    }

    /**
     * Store a new post.
     * @param PostRequest $request
     * @param PostService $postService
     * @return RedirectResponse
     *
     * Note: This method uses the PostService to handle the save logic, ensuring
     * that the post is created with all necessary relationships and validations.
     */
    public function store(PostRequest $request, PostService $postService): RedirectResponse
    {
        $postService->savePost($request);

        return redirect()->route('posts.list')->with('success', 'Post created successfully.');
    }

    /**
     * Show the form for editing the specified post.
     *
     * @param Post $post
     * @return Response
     *
     * Note: This method loads the post with its tags and cover image to ensure
     * that the form can be pre-populated with existing data.
     */
    public function edit(Post $post): Response
    {
        $post->load(['tags', 'coverImage', 'author']);

        return Inertia::render('post/Edit', [
            'post'  => $post,
            'media' => Media::select('id', 'file_path', 'file_name')->get(),
            'tags'  => Tag::select('id', 'name')->get(),
        ]);
    }

    /**
     * Update the specified post in storage.
     *
     * @param PostRequest $request
     * @param Post $post
     * @param PostService $postService
     * @return RedirectResponse
     *
     * Note: This method uses the PostService to handle the save logic, ensuring
     * that the post is either created or updated based on whether $post is null.
     */
    public function update(PostRequest $request, Post $post, PostService $postService): RedirectResponse
    {
        $postService->savePost($request, $post);

        return redirect()->route('posts.list')->with('success', 'Post updated successfully.');
    }
}
