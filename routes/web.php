<?php

use App\Http\Controllers\PropertyController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PropertyController::class, 'index'])->name('home');

Route::get('properties/create', [PropertyController::class, 'create'])
    ->middleware('auth')
    ->name('properties.create');

Route::post('properties', [PropertyController::class, 'store'])
    ->middleware('auth')
    ->name('properties.store');

Route::get('properties/{property}', [PropertyController::class, 'show'])
    ->name('properties.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
