<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Post;
use Inertia\Inertia;
use App\Models\Media;
use App\Http\Requests\PostRequest;
use App\Services\ModelStatsService;

class PostController extends Controller
{
    /**
     * Display a list of posts with stats.
     * @param ModelStatsService $statsService
     * @return \Inertia\Response
     */
    public function index(ModelStatsService $statsService)
    {
        $posts = Post::all();

        $stats = $statsService->compute($posts, 'type');

        return Inertia::render('post/Index', [
            'posts' => $posts,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form to create a new post.
     * @return \Inertia\Response
     */
    public function create()
    {
        $media = Media::all();
        $tags = Tag::all();
        return Inertia::render(
            'post/Create',
            [
                'media' => $media,
                'tags' => $tags,
            ]
        );
    }

    /**
     * Store a new post.
     * @param PostRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(PostRequest $request)
    {
        $data = $request->validated();

        // Gérer l’image
        $cover_media_id = null;
        $cover_image_path = null;

        if (!empty($data['cover_image_id'])) {
            $cover_media_id = $data['cover_image_id'];
        } elseif ($request->hasFile('cover_image_file')) {
            $path = $request->file('cover_image_file')->store('covers', 'public');
            $cover_image_path = $path;
        }

        $post = Post::create([
            'title'          => $data['title'],
            'content'        => $data['content'],
            'cover_media_id' => $cover_media_id,
            'cover_image_path' => $cover_image_path,
            'image_position' => $data['image_position'],
            'show_title'     => true,
            'type'           => $data['type'],
            'status'         => $data['status'],
            'author_id'      => auth()->id(),
        ]);

        // Créer les tags “new_tags”
        $createdTagIds = [];
        foreach ($data['new_tags'] ?? [] as $name) {
            $name = trim($name);
            if ($name === '') continue;
            $tag = Tag::firstOrCreate(['name' => $name]);
            $createdTagIds[] = $tag->id;
        }

        // Attacher tags (existants + créés)
        $tagIds = array_unique(array_merge($data['selected_tag_ids'] ?? [], $createdTagIds));
        if (!empty($tagIds)) {
            $post->tags()->sync($tagIds);
        }

        return redirect()->route('posts.list', $post)->with('success', 'Post created');
    }
}
