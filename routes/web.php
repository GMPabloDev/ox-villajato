<?php

use App\Http\Controllers\PropertyController;
use App\Http\Controllers\PropertyInquiryController;
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

Route::post('properties/{property}/inquiries', [PropertyInquiryController::class, 'store'])
    ->middleware('throttle:property-inquiry')
    ->name('properties.inquiries.store');

Route::middleware('auth')->prefix('panel')->name('panel.')->group(function () {
    Route::get('properties', [PropertyController::class, 'panelIndex'])
        ->name('properties.index');

    Route::get('properties/{property}/edit', [PropertyController::class, 'edit'])
        ->name('properties.edit');

    Route::put('properties/{property}', [PropertyController::class, 'update'])
        ->name('properties.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
