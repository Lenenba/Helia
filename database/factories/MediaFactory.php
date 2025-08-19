<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Media;
use Illuminate\Support\Str;
use App\Helpers\ImageHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Media>
 */
class MediaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Media::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        // 1) Download or synthesize bytes
        [$bytes, $ext, $mime] = ImageHelper::downloadOrMakeImage();

        // 2) Compute unique path + file name
        $fileName    = 'img_' . Str::uuid() . '.' . $ext;
        $storagePath = 'media/' . $fileName;

        // 3) Store
        Storage::disk('public')->put($storagePath, $bytes);

        // 4) Derive mime/size with safe fallbacks
        $detectedMime = Storage::disk('public')->mimeType($storagePath) ?: $mime ?: 'image/jpeg';
        $sizeBytes    = Storage::disk('public')->size($storagePath) ?: strlen($bytes);

        return [
            // NOTE: we do NOT set mediaable_* here to let the seeder override cleanly.
            'collection_name'    => 'avatar',
            'file_name'          => $fileName,
            'file_path'          => $storagePath,          // your UI uses /storage/{file_path}
            'mime_type'          => $detectedMime,
            'size'               => $sizeBytes,
            'disk'               => 'public',
            'is_profile_picture' => true,
        ];
    }

    /**
     * State: mark as avatar (512x512)
     */
    public function avatar(): self
    {
        return $this->state(fn() => [
            'collection_name'    => 'avatar',
            'is_profile_picture' => true,
        ]);
    }

    /**
     * State: mark as gallery item
     */
    public function gallery(): self
    {
        return $this->state(fn() => [
            'collection_name'    => 'gallery',
            'is_profile_picture' => false,
        ]);
    }

    /**
     * State: attach to a specific morph target (model instance).
     */
    public function forModel(object $model): self
    {
        return $this->state(fn() => [
            'mediaable_id'   => $model->getKey(),
            'mediaable_type' => get_class($model),
        ]);
    }

    /**
     * State: force a specific URL (bypass default URL logic).
     */
    public function fromUrl(string $url): self
    {
        return $this->state(function () use ($url) {
            [$bytes, $ext, $mime] = $this->downloadOrMakeImage($url);

            $fileName    = 'img_' . Str::uuid() . '.' . $ext;
            $storagePath = 'media/' . $fileName;

            Storage::disk('public')->put($storagePath, $bytes);

            $detectedMime = Storage::disk('public')->mimeType($storagePath) ?: $mime ?: 'image/jpeg';
            $sizeBytes    = Storage::disk('public')->size($storagePath) ?: strlen($bytes);

            return [
                'file_name' => $fileName,
                'file_path' => $storagePath,
                'mime_type' => $detectedMime,
                'size'      => $sizeBytes,
                'disk'      => 'public',
            ];
        });
    }
}
