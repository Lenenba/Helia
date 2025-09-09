<?php

namespace App\Observers;

use App\Models\Page;
use App\Services\PageService;

class PageObserver
{
    public function saved(Page $page): void
    {
        PageService::bustCacheFor($page);
    }

    public function deleted(Page $page): void
    {
        PageService::bustCacheFor($page);
    }
}
