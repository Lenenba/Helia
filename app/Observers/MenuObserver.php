<?php
// app/Observers/MenuObserver.php
namespace App\Observers;

use App\Models\Menu;
use Illuminate\Support\Facades\Cache;

class MenuObserver
{
    /** @return void */
    public function saved(Menu $menu): void
    {
        Cache::forget('menus.shared');
    }
    public function deleted(Menu $menu): void
    {
        Cache::forget('menus.shared');
    }
}
