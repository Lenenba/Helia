<?php

// database/seeders/MenuSeeder.php
namespace Database\Seeders;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $main = Menu::firstOrCreate(['slug' => 'main'], ['name' => 'Main']);
        $home = MenuItem::firstOrCreate([
            'menu_id' => $main->id,
            'label' => 'Home',
            'parent_id' => null
        ], ['position' => 0, 'url' => '/', 'is_visible' => true]);

        $blog = MenuItem::firstOrCreate([
            'menu_id' => $main->id,
            'label' => 'Blog',
            'parent_id' => null
        ], ['position' => 1, 'url' => '/blog', 'is_visible' => true]);

        MenuItem::firstOrCreate([
            'menu_id' => $main->id,
            'label' => 'Laravel',
            'parent_id' => $blog->id
        ], ['position' => 0, 'url' => '/blog/laravel', 'is_visible' => true]);

        MenuItem::firstOrCreate([
            'menu_id' => $main->id,
            'label' => 'Vue',
            'parent_id' => $blog->id
        ], ['position' => 1, 'url' => '/blog/vue', 'is_visible' => true]);
    }
}
