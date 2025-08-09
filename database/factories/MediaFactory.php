<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        // Generate a remote URL for an image.
        $url = $this->generateFakeProfilePhoto() ?? $this->generateFakeCompanyLogo();

        // If no URL could be generated, return an empty array to skip creation.
        if (!$url) {
            Log::warning('MediaFactory: Could not generate an image URL.');
            return [];
        }

        // Derive a file name from the URL path, or generate a uuid if none.
        $pathInfo = pathinfo(parse_url($url, PHP_URL_PATH) ?? '');
        $fileName = isset($pathInfo['basename']) && Str::contains($pathInfo['basename'], '.')
            ? $pathInfo['basename']
            : (Str::uuid() . '.jpg');

        // Define the storage path. e.g., 'media/random_file_name.jpg'
        $storagePath = 'media/' . $fileName;

        try {
            // Get the image content from the remote URL.
            $imageContents = Http::get($url)->body();

            // Store the file in the public disk.
            Storage::disk('public')->put($storagePath, $imageContents);
        } catch (\Throwable $e) {
            // Log the error and skip creating this media record if download/storage fails.
            Log::error('MediaFactory: Failed to download or store image.', ['url' => $url, 'error' => $e->getMessage()]);
            return [];
        }

        return [
            // Attach to a new user by default
            'mediaable_id'   => User::factory(),
            'mediaable_type' => User::class,
            // Default collection; adjust via state() when needed
            'collection_name' => 'avatar',
            'file_name'      => $fileName,
            // The path within the storage disk where the file is located.
            'file_path'      => $storagePath,
            // Use the actual mime type and size of the stored file.
            'mime_type'      => Storage::disk('public')->mimeType($storagePath),
            'size'           => Storage::disk('public')->size($storagePath),
            'disk'           => 'public', // It's good practice to store the disk name.
            'is_profile_picture' => true,
        ];
    }

    /**
     * Generate a fake profile photo URL from Unsplash.
     *
     * @return string|null
     */
    private function generateFakeProfilePhoto(): ?string
    {
        if (!env('UNSPLASH_ACCESS_KEY')) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Client-ID ' . env('UNSPLASH_ACCESS_KEY'),
            ])->get('https://api.unsplash.com/photos/random', [
                'query'       => 'portrait',
                'orientation' => 'squarish',
            ]);

            if ($response->successful()) {
                return $response->json('urls.regular');
            }
        } catch (\Throwable $e) {
            Log::warning('Unsplash API call failed.', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Generate a fake company logo placeholder URL.
     *
     * @return string
     */
    private function generateFakeCompanyLogo(): string
    {
        $bgColor = ltrim($this->faker->hexColor(), '#');
        $text    = strtoupper(substr($this->faker->company(), 0, 3));
        $width   = 150;
        $height  = 150;

        return "https://via.placeholder.com/{$width}x{$height}/{$bgColor}/FFFFFF.png?text={$text}";
    }
}
