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
        // Configuration enrichie avec des 'mappers' pour une transformation sur mesure.
        $elementConfig = [
            'posts' => [
                'model' => Post::class,
                // Le mapper transforme chaque modèle Post en tableau avec la structure désirée.
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
                // HYPOTHÈSE: Vous utilisez la librairie Spatie\MediaLibrary
                // qui fournit des méthodes pratiques comme getUrl().
                'mapper' => function (Media $media) {
                    return [
                        'id' => $media->id,
                        'url' => $media->getUrl(), // Obtenir l'URL publique du média
                        'title' => $media->file_name,   // Le nom donné au média
                        'label' => $media->file_name,   // Le nom donné au média
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

            // 1. On récupère les modèles complets, sans `select()`.
            //    Ceci est nécessaire pour pouvoir utiliser les accesseurs
            //    (ex: getUrl()) et avoir toutes les données dans le mapper.
            $items = $modelClass::query()->latest()->get();

            // 2. On applique la transformation sur mesure définie dans le mapper.
            $availableElements[$key] = $items->map($mapper);
        }

        return Inertia::render('page/Create', [
            'availableElements' => $availableElements,
        ]);
    }
}
