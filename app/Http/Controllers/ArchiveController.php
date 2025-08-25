<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

class ArchiveController extends Controller
{
    /**
     * Mapper de sécurité pour les modèles autorisés à être archivés/restaurés.
     */
    private const ARCHIVABLE_TYPES = [
        'post' => \App\Models\Post::class,
        'page' => \App\Models\Page::class,
        // Ajoutez d'autres modèles ici
    ];

    /**
     * Archive (soft delete) un item.
     */
    public function archive(string $type, int $id)
    {
        $item = $this->findItem($type, $id);

        // Grâce au trait SoftDeletes, la méthode delete() fait un soft delete !
        $item->delete();

        return back()->with('success', ucfirst($type) . " archivé avec succès.");
    }

    /**
     * Restaure un item archivé.
     */
    public function restore(string $type, int $id)
    {
        // Pour trouver un item archivé, on doit utiliser withTrashed()
        $modelClass = $this->getModelClass($type);
        $item = $modelClass::withTrashed()->findOrFail($id);
        $item->is_published = false;
        $item->status = 'draft';
        $item->published_at = null;

        // La méthode restore() est fournie par le trait SoftDeletes
        $item->restore();

        return back()->with('success', ucfirst($type) . " restauré avec succès.");
    }

    /**
     * Méthode privée pour valider le type et retrouver le nom de la classe.
     */
    private function getModelClass(string $type): string
    {
        if (!array_key_exists($type, self::ARCHIVABLE_TYPES)) {
            abort(404, "Type de contenu non reconnu.");
        }
        return self::ARCHIVABLE_TYPES[$type];
    }

    /**
     * Méthode privée pour trouver un item non-archivé.
     */
    private function findItem(string $type, int $id)
    {
        $modelClass = $this->getModelClass($type);
        return $modelClass::findOrFail($id);
    }
}
