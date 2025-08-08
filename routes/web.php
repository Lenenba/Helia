<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use PHPUnit\Framework\Attributes\Group;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(
    function () {
        Route::get('pages', [PageController::class, 'index'])->name('pages.list');
    }
);

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
