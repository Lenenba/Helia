<?php

use App\Models\Page;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\PublishController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\SetAsProfilePhotoController;
use App\Http\Controllers\DeleteProfilePhotoController;

// Home by slug "home" (or root "/")
Route::get('/', [PageController::class, 'show'])->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/pages/{page:slug}', [PageController::class, 'show'])->name('pages.show');



Route::middleware('auth')->group(
    function () {
        Route::get('/api/content', [ContentController::class, 'index'])->name('api.content.index');


        Route::resource('menus', MenuController::class)->except(['show']);

        Route::post('menus/{menu}/tree', [MenuItemController::class, 'updateTree'])->name('menus.updateTree');
        Route::post('menus/{menu}/items', [MenuItemController::class, 'addItem'])->name('menus.items.store');
        Route::put('menus/{menu}/items/{item}', [MenuItemController::class, 'updateItem'])->name('menus.items.update');
        Route::delete('menus/{menu}/items/{item}', [MenuItemController::class, 'deleteItem'])->name('menus.items.destroy');

        // Pages management
        Route::get('pages', [PageController::class, 'index'])->name('pages.list');
        Route::get('pages/create', [PageController::class, 'create'])->name('pages.create');
        Route::post('pages/store', [PageController::class, 'store'])->name('pages.store');
        Route::get('/pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
        Route::put('/pages/{page}', [PageController::class, 'update'])->name('pages.update');

        // Posts management
        Route::get('posts', [PostController::class, 'index'])->name('posts.list');
        Route::get('posts/create', [PostController::class, 'create'])->name('posts.create');
        Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
        // NOUVELLES ROUTES POUR L'ÉDITION
        Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
        Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');


        // Media management
        Route::get('media', [MediaController::class, 'index'])->name('media.list');
        Route::post('media', [MediaController::class, 'store'])->name('media.store');
        Route::post('media/setAsProfile', SetAsProfilePhotoController::class)->name('media.setProfile');
        Route::delete('media/{media}', DeleteProfilePhotoController::class)->name('media.delete');

        // Route pour contenu
        Route::patch('/admin/publish/{type}/{id}', [PublishController::class, 'publish'])
            ->name('admin.publish.publish');
        Route::patch('/admin/unpublish/{type}/{id}', [PublishController::class, 'unpublish'])
            ->name('admin.publish.unpublish');
        Route::delete('/admin/archive/{type}/{id}', [ArchiveController::class, 'archive'])
            ->name('admin.archive');
        Route::patch('/admin/restore/{type}/{id}', [ArchiveController::class, 'restore'])
            ->name('admin.restore');
    }
);

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
