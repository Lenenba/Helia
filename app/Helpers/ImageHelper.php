<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Faker\Generator as Faker;

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
}
