<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Inertia\Inertia;
use App\Services\ModelStatsService;

class PostController extends Controller
{
    public function index(ModelStatsService $statsService)
    {
        $posts = Post::all();

        $stats = $statsService->compute($posts, 'type');

        return Inertia::render('post/Index', [
            'posts' => $posts,
            'stats' => $stats,
        ]);
    }
    public function create()
    {
        return Inertia::render('post/Create');
    }
}
