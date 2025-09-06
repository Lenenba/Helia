<?php

namespace App\Helpers;

use Faker\Generator as Faker;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ImageHelper
{
    /**
     * Generate a fake profile photo URL from Unsplash.
     */
    public static function fakeProfilePhoto(): ?string
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
            // silently ignore API errors
        }

        return null;
    }

    /**
     * Generate a fake company logo placeholder URL.
     */
    public static function fakeCompanyLogo(Faker $faker): string
    {
        $bgColor = ltrim($faker->hexColor(), '#');
        $text    = strtoupper(substr($faker->company(), 0, 3));
        $width   = 150;
        $height  = 150;

        return "https://api.oneapipro.com/images/placeholder"
            . "?text={$text}&width={$width}&height={$height}&color={$bgColor}";
    }

    /**
     * Download the image. If it fails, synthesize a tiny valid PNG.
     *
     * @return array{0:string,1:string,2:string|null} [bytes, extension, mime]
     */
    public static function downloadOrMakeImage(): array
    {
        $url = self::fakeProfilePhoto() ?: self::fakeCompanyLogo(app(Faker::class));
        if ($url) {
            try {
                $resp = Http::timeout(7)->get($url);
                if ($resp->successful()) {
                    $bytes = $resp->body();
                    // Guess extension from content-type
                    $mime = $resp->header('Content-Type') ?: null;
                    $ext  = match (true) {
                        str_contains((string) $mime, 'png')  => 'png',
                        str_contains((string) $mime, 'webp') => 'webp',
                        str_contains((string) $mime, 'gif')  => 'gif',
                        default                               => 'jpg',
                    };
                    return [$bytes, $ext, $mime];
                }
            } catch (\Throwable $e) {
                Log::warning('MediaFactory: download failed', ['url' => $url, 'error' => $e->getMessage()]);
            }
        }

        // 1x1 transparent PNG (valid image bytes) as ultimate fallback
        $tinyPngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQImWNgYGBgAAAABQABhZ1cWQAAAABJRU5ErkJggg==';
        return [base64_decode($tinyPngBase64), 'png', 'image/png'];
    }
}
