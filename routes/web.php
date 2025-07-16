<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ImportController;

Route::get('/', function () {
    return Inertia::render('Map');
})->middleware(['auth', 'verified'])->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Map');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('map', function () {
    return Inertia::render('Map');
})->middleware(['auth', 'verified'])->name('map');

Route::get('imports', function () {
    return Inertia::render('Imports');
})->middleware(['auth', 'verified'])->name('imports');

Route::get('campanhas', function () {
    return Inertia::render('Campanhas');
})->middleware(['auth', 'verified'])->name('campanhas');

Route::get('/imports/upload', [ImportController::class, 'index'])->name('imports.upload');
Route::post('/imports/process', [ImportController::class, 'import'])->name('imports.process');


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
