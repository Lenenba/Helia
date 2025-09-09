<?php

namespace App\Services\Blocks;

use App\Models\Block;
use App\Models\Post;
use App\Models\Media;
use App\Models\HtmlContent;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BlockFactory
{
    /**
     * Ensure a Block exists for the given payload and ALWAYS set the morph target.
     *
     * Expected payload shapes (examples):
     *  - ['id' => 123]                                          // reuse existing block
     *  - ['source' => ['type' => 'post',  'id' => 7], ...]
     *  - ['source' => ['type' => 'media', 'id' => 42], ...]
     *  - ['source' => ['type' => 'html',  'content' => '<p>..']]
     *  - fallback: creates HtmlContent with empty content.
     *
     * Returns the Block ID.
     */
    public function ensureFromPayload(array $payload): int
    {
        // -- 1) Reuse an existing block if provided and valid
        if ($existingId = Arr::get($payload, 'id')) {
            $block = Block::query()->find((int) $existingId);
            if ($block) {
                // Optional: merge/overwrite settings
                $settings = $this->normalizeSettings($payload);
                if (!empty($settings)) {
                    $block->update(['settings' => $settings] + $this->maybeTemplateHint($payload));
                }
                return $block->getKey();
            }
        }

        // -- 2) Resolve source type
        $srcType = strtolower((string) Arr::get($payload, 'source.type', ''));
        $templateHint = (string) Arr::get($payload, 'template_hint', Arr::get($payload, 'template', 'default'));
        $settings = $this->normalizeSettings($payload);

        // -- 3) Create the proper morph target + the Block
        return match ($srcType) {
            'post'  => $this->createForPost((int) Arr::get($payload, 'source.id'), $settings, $templateHint)->getKey(),
            'media' => $this->createForMedia((int) Arr::get($payload, 'source.id'), $settings, $templateHint)->getKey(),
            'html'  => $this->createForHtml((string) Arr::get($payload, 'source.content', ''), $settings, $templateHint)->getKey(),
            default => $this->createFallbackHtml($settings, $templateHint)->getKey(), // safe fallback
        };
    }

    /** Create a Block pointing to a Post. */
    public function createForPost(int $postId, array $settings = [], ?string $templateHint = 'default'): Block
    {
        $post = Post::query()->find($postId);
        if (!$post) {
            throw new ModelNotFoundException("Post #{$postId} not found for block creation.");
        }

        return Block::query()->create([
            'blockable_type' => Post::class,
            'blockable_id'   => $post->getKey(),
            'template_hint'  => $templateHint,
            'settings'       => $settings,
        ]);
    }

    /** Create a Block pointing to a Media. */
    public function createForMedia(int $mediaId, array $settings = [], ?string $templateHint = 'image'): Block
    {
        $media = Media::query()->find($mediaId);
        if (!$media) {
            throw new ModelNotFoundException("Media #{$mediaId} not found for block creation.");
        }

        return Block::query()->create([
            'blockable_type' => Media::class,
            'blockable_id'   => $media->getKey(),
            'template_hint'  => $templateHint ?? 'image',
            'settings'       => $settings,
        ]);
    }

    /** Create a Block pointing to HtmlContent. */
    public function createForHtml(string $html, array $settings = [], ?string $templateHint = 'default'): Block
    {
        $hc = HtmlContent::query()->create([
            'content' => $html,
        ]);

        return Block::query()->create([
            'blockable_type' => HtmlContent::class,
            'blockable_id'   => $hc->getKey(),
            'template_hint'  => $templateHint,
            'settings'       => $settings,
        ]);
    }

    /** Fallback: create an empty HtmlContent as the morph target. */
    public function createFallbackHtml(array $settings = [], ?string $templateHint = 'default'): Block
    {
        $hc = HtmlContent::query()->create(['content' => '']);

        return Block::query()->create([
            'blockable_type' => HtmlContent::class,
            'blockable_id'   => $hc->getKey(),
            'template_hint'  => $templateHint,
            'settings'       => $settings,
        ]);
    }

    /**
     * Normalize settings from UI payload:
     * - Keep only serializable, UI-relevant data
     * - Optionally strip internal keys if needed
     */
    protected function normalizeSettings(array $payload): array
    {
        // You can prune keys that should not be persisted in settings.
        // Example here: keep 'settings' as-is if provided, otherwise accept some top-level UI keys.
        $settings = Arr::get($payload, 'settings');

        if (is_array($settings)) {
            return $settings;
        }

        // Compose a lightweight settings array from known top-level fields (optional)
        $candidate = Arr::only($payload, [
            'excerpt',
            'image_position',
            'cover_url',
            'cta',
            'ui',
        ]);

        // Keep source echoing if it's helpful for rendering (optional)
        $source = Arr::get($payload, 'source');
        if (is_array($source)) {
            $candidate['source'] = $source;
        }

        return $candidate;
    }

    /** Build an array with template_hint if present. */
    protected function maybeTemplateHint(array $payload): array
    {
        $hint = Arr::get($payload, 'template_hint', Arr::get($payload, 'template'));
        return $hint ? ['template_hint' => (string) $hint] : [];
    }
}
