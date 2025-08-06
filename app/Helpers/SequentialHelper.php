<?php

namespace App\Helpers;

use App\Models\Section;

class SequentialHelper
{
    /**
     * Retourne le prochain ordre unique pour une section d'une page donnée.
     *
     * @param int $pageId
     * @return int
     */
    public static function nextOrderForPage(int $pageId): int
    {
        $maxOrder = Section::where('page_id', $pageId)->max('order');
        return is_null($maxOrder) ? 1 : $maxOrder + 1;
    }

    /**
     * Retourne le prochain ordre unique pour une section donnée.
     *
     * @param int $sectionId
     * @return int
     */
    public static function nextOrderForSection(int $sectionId): int
    {
        $maxOrder = Section::find($sectionId)->blocks()->max('order');
        return is_null($maxOrder) ? 1 : $maxOrder + 1;
    }
}
