<?php

namespace App\Services\Blocks;

use App\Models\Block;
use App\Models\Post;
use App\Models\Media;

/**
 * BlockFactory
 * - Centralizes how we create (or reuse) Block rows for different payload types.
 * - Keeps controller/actions clean and enforces a single source of truth.
 */
class BlockFactory
{
    /**
     * Ensure there is a Block row representing the given payload.
     * Returns the Block id.
     *
     * Expected $payload shape (from UI):
     *  - contentId: int
     *  - contentType: 'post' | 'media' | 'block'
     *  - title?: string
     */
    public function ensureFromPayload(array $payload): int
    {
        $contentId   = (int) data_get($payload, 'contentId');
        $contentType = (string) data_get($payload, 'contentType');
        $title       = data_get($payload, 'title');

        return match ($contentType) {
            'media' => $this->fromMedia($contentId, $title),
            'post'  => $this->fromPost($contentId, $title),
            'block' => $this->reuseExisting($contentId),
            default => $this->generic($contentId, $contentType, $title),
        };
    }

    /** Create a "media" block pointing to a Media row. */
    protected function fromMedia(int $mediaId, ?string $title): int
    {
        $media = Media::query()->findOrFail($mediaId);

        $block = Block::create([
            'type'         => 'media',
            'title'        => $title ?? ($media->file_name ?? null),
            'content'      => '', // required by schema; media content is carried in settings
            'settings'     => [
                'source' => ['type' => 'media', 'id' => $media->id],
            ],
            'is_published' => false,
            'status'       => 'draft',
            'media_id'     => $media->id,
        ]);

        return $block->id;
    }

    /** Create a "post" block carrying post content and layout hints. */
    protected function fromPost(int $postId, ?string $title): int
    {
        $post = Post::query()->findOrFail($postId);

        $block = Block::create([
            'type'         => 'post',
            'title'        => $title ?? $post->title,
            'content'      => $post->content ?? ($post->excerpt ?? ''),
            'settings'     => [
                'source'         => ['type' => 'post', 'id' => $post->id],
                'excerpt'        => $post->excerpt ?? null,
                'image_position' => $post->image_position ?? 'top',
                'cover_url'      => $post->coverUrl ?? null,
            ],
            'is_published'  => false,
            'status'        => 'draft',
            'media_id'      => null,
        ]);

        return $block->id;
    }

    /** Reuse an existing "block" (already modeled). */
    protected function reuseExisting(int $blockId): int
    {
        $existing = Block::query()->findOrFail($blockId);
        return $existing->id;
    }

    /** Fallback for unknown types. */
    protected function generic(int $contentId, string $type, ?string $title): int
    {
        $block = Block::create([
            'type'         => 'generic',
            'title'        => $title ?? 'Untitled block',
            'content'      => '',
            'settings'     => [
                'source' => ['type' => $type, 'id' => $contentId],
            ],
            'is_published' => false,
            'status'       => 'draft',
            'media_id'     => null,
        ]);

        return $block->id;
    }
}
