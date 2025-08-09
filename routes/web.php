<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use App\Http\Controllers\MediaController;
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
        Route::get('pages', [PageController::class, 'index'])->name('pages.list');
        Route::get('pages/create', [PageController::class, 'create'])->name('pages.create');
        // Media management
        Route::get('media', [MediaController::class, 'index'])->name('media.list');
        Route::post('media', [MediaController::class, 'store'])->name('media.store');
        Route::post('media/setAsProfile', SetAsProfilePhotoController::class)->name('media.setProfile');
        Route::delete('media/{media}', DeleteProfilePhotoController::class)->name('media.delete');
    }
);

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
