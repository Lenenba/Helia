<?php

namespace App\Observers;

use App\Models\MenuItem;
use Illuminate\Support\Facades\Cache;

class MenuItemObserver
{
    public function saved(MenuItem $item): void
    {
        Cache::forget('menus.shared');
    }
    public function deleted(MenuItem $item): void
    {
        Cache::forget('menus.shared');
    }
}
