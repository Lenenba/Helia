<?php

namespace App\Services\Support;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class UniqueSlugService
{
    /**
     * Generate a unique slug for a given table/column.
     *
     * @param  string       $table
     * @param  string       $column
     * @param  string|null  $provided
     * @param  string|null  $fallbackTitle
     */
    public function makeUnique(string $table, string $column, ?string $provided, ?string $fallbackTitle): string
    {
        $base = Str::slug($provided ?: (string) $fallbackTitle);
        $base = $base !== '' ? $base : 'item';

        $slug = $base;
        $i = 2;

        while (DB::table($table)->where($column, $slug)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }
}
