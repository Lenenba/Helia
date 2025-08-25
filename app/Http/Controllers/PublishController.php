<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;

class PublishController extends Controller
{
    /**
     * Mapper de sécurité pour les modèles autorisés.
     */
    private const PUBLISHABLE_TYPES = [
        'post'  => \App\Models\Post::class,
        'page'  => \App\Models\Page::class,
    ];

    /**
     * Publie un contenu.
     *
     * @param string $type
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function publish(string $type, int $id)
    {
        $item = $this->findPublishableItem($type, $id);

        $item->published_at = now();
        $item->is_published = true;
        $item->status = 'published'; // Optionnel, selon votre logique métier
        $item->save();

        return back()->with('success', ucfirst($type) . " publié avec succès.");
    }

    /**
     * Dépublie un contenu.
     *
     * @param string $type
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unpublish(string $type, int $id)
    {
        $item = $this->findPublishableItem($type, $id);

        $item->published_at = null;
        $item->status = 'unpublished';
        $item->is_published = false;
        $item->save();

        return back()->with('success', ucfirst($type) . " dépublié (remis en brouillon).");
    }

    /**
     * Méthode privée pour trouver et valider l'item à modifier.
     *
     * @param string $type
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Model
     */
    private function findPublishableItem(string $type, int $id): Model
    {

        if (!array_key_exists($type, self::PUBLISHABLE_TYPES)) {
            abort(404, "Type de contenu non reconnu.");
        }

        $modelClass = self::PUBLISHABLE_TYPES[$type];

        $item = $modelClass::findOrFail($id);

        // 4. (OPTIONNEL MAIS RECOMMANDÉ) Vérifier les autorisations
        // \Illuminate\Support\Facades\Gate::authorize('update', $item);

        return $item;
    }
}
