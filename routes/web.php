<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\PublishController;
use App\Http\Controllers\SetAsProfilePhotoController;
use App\Http\Controllers\DeleteProfilePhotoController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(
    function () {
        // Pages management
        Route::get('pages', [PageController::class, 'index'])->name('pages.list');
        Route::get('pages/create', [PageController::class, 'create'])->name('pages.create');

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
