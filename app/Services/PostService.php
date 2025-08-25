<?php

namespace App\Services;

use App\Models\Tag;
use App\Models\Post;
use App\Http\Requests\PostRequest;
use Illuminate\Support\Facades\DB;

class PostService
{
    /**
     * Crée ou met à jour un post et gère ses relations.
     * L'utilisation de DB::transaction garantit que si une partie échoue (ex: upload d'image),
     * toute l'opération est annulée pour ne pas avoir de données corrompues.
     *
     * @param PostRequest $request
     * @param Post|null $post Le post à mettre à jour, ou null pour une création.
     * @return Post
     */
    public function savePost(PostRequest $request, ?Post $post = null): Post
    {
        $data = $request->validated();

        return DB::transaction(function () use ($data, $request, $post) {
            // Si le post est null, on le crée. Sinon, on le met à jour.
            if (is_null($post)) {
                $postData = $data;
                $postData['author_id'] = auth()->id();
                $post = Post::create($postData);
            } else {
                $post->update($data);
            }

            // On délègue la gestion de l'image et des tags à des méthodes privées
            $this->handleCoverImage($request, $post, $data);
            $this->handleTags($post, $data);

            return $post;
        });
    }

    /**
     * Gère la logique de l'image de couverture.
     * @param PostRequest $request
     * @param Post $post
     * @param array $data Les données du formulaire, incluant l'image de couverture.
     * @return void
     *
     * Note: Cette méthode supprime l'ancien média physique et en base de données si une nouvelle image est uploadée.
     * Si une image est sélectionnée depuis la bibliothèque, elle met à jour le post avec l'ID du média.
     */
    private function handleCoverImage(PostRequest $request, Post $post, array $data): void
    {
        $coverImageId   = $data['cover_image_id'] ?? null;
        $coverImageFile = $request->file('cover_image_file');

        if ($coverImageFile) {
            optional($post->coverImage)->delete(); // Supprime l'ancien média physique et BDD si existant

            $storedPath = $coverImageFile->store('covers', 'public');
            $media = $post->coverImage()->create([
                'collection_name' => 'cover',
                'file_name'       => $coverImageFile->getClientOriginalName(),
                'file_path'       => $storedPath,
                'disk'            => 'public',
                'mime_type'       => $coverImageFile->getClientMimeType(),
                'size'            => $coverImageFile->getSize(),
            ]);
            $post->forceFill(['cover_media_id' => $media->id])->save();
        } elseif (!empty($coverImageId)) {
            $post->update(['cover_media_id' => $coverImageId]);
        } elseif (is_null($coverImageId) && is_null($coverImageFile)) {
            // Si l'utilisateur a explicitement vidé le champ image
            $post->update(['cover_media_id' => null]);
        }
    }

    /**
     * Gère la synchronisation des tags.
     *
     * @param Post $post
     * @param array $data Les données du formulaire, incluant les tags.
     * @return void
     * Note: Cette méthode crée de nouveaux tags si nécessaire et synchronise les tags existants.
     */
    private function handleTags(Post $post, array $data): void
    {
        $createdTagIds = [];
        foreach ($data['new_tags'] ?? [] as $name) {
            $name = trim($name);
            if ($name === '') continue;
            $tag = Tag::firstOrCreate(['name' => $name]);
            $createdTagIds[] = $tag->id;
        }

        $tagIds = array_unique(array_merge($data['selected_tag_ids'] ?? [], $createdTagIds));
        $post->tags()->sync($tagIds);
    }
}
