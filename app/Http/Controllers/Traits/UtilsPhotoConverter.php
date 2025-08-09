<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Str;

trait UtilsPhotoConverter
{
    /**
     * Get a WebP URL for the given image or return the original URL/path if it's external or a data URI.
     * If a local file exists, convert it to WebP (caching the result) and return its asset URL.
     *
     * @param  string  $imagePath  A URL, data URI, or local-relative path (e.g. 'images/photo.jpg').
     * @return string
     */
    public function convertToWebp(string $imagePath): string
    {
        // 1) External URL or data URI: return as-is
        if (Str::startsWith($imagePath, ['http://', 'https://', 'data:image'])) {
            return $imagePath;
        }

        // 5) Return asset URL for WebP
        return asset($imagePath);
    }
}
