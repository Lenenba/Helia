<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Post;

class ContentController extends Controller
{
    public function index()
    {
        $pages = Page::select('id', 'title')->get()->map(function ($page) {
            return [
                'id' => $page->id,
                'title' => $page->title,
                'type' => 'page',
            ];
        });

        $posts = Post::select('id', 'title')->get()->map(function ($post) {
            return [
                'id' => $post->id,
                'title' => $post->title,
                'type' => 'post',
            ];
        });

        return response()->json($pages->concat($posts));
    }
}
