<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\PropertyInquiryController;
use App\Http\Controllers\UbigeoController;
use App\Http\Middleware\EnsurePropertyIsAvailable;
use App\Http\Middleware\EnsurePropertyIsManageable;
use Illuminate\Support\Facades\Route;

Route::get('/', [PropertyController::class, 'index'])->name('home');

Route::middleware(['auth'])->group(function () {
    Route::post('properties', [PropertyController::class, 'store'])
        ->name('properties.store');

    Route::get('ubigeo', [UbigeoController::class, 'index'])
        ->name('ubigeo.index');
});

Route::get('properties/{property}', [PropertyController::class, 'show'])
    ->middleware(EnsurePropertyIsAvailable::class)
    ->name('properties.show');

Route::post('properties/{property}/inquiries', [PropertyInquiryController::class, 'store'])
    ->middleware([EnsurePropertyIsAvailable::class, 'throttle:property-inquiry'])
    ->name('properties.inquiries.store');

Route::middleware('auth')->prefix('panel')->name('panel.')->group(function () {
    Route::middleware('can:manage-agents')->group(function () {
        Route::get('agents/create', [AgentController::class, 'create'])
            ->name('agents.create');

        Route::post('agents', [AgentController::class, 'store'])
            ->name('agents.store');

        Route::delete('properties/{property}', [PropertyController::class, 'destroy'])
            ->middleware(EnsurePropertyIsManageable::class.':archived')
            ->name('properties.destroy');
    });

    Route::get('properties', [PropertyController::class, 'panelIndex'])
        ->name('properties.index');

    Route::get('archived-properties', [PropertyController::class, 'archivedIndex'])
        ->name('properties.archived');

    Route::get('inquiries', [PropertyInquiryController::class, 'index'])
        ->name('inquiries.index');

    Route::patch('properties/{property}/republish', [PropertyController::class, 'republish'])
        ->middleware(EnsurePropertyIsManageable::class.':archived')
        ->name('properties.republish');

    Route::middleware(EnsurePropertyIsManageable::class)->group(function () {
        Route::put('properties/{property}', [PropertyController::class, 'update'])
            ->name('properties.update');

        Route::patch('properties/{property}/close', [PropertyController::class, 'close'])
            ->name('properties.close');

        Route::patch('properties/{property}/archive', [PropertyController::class, 'archive'])
            ->middleware(EnsurePropertyIsAvailable::class)
            ->name('properties.archive');
    });

    Route::patch('properties/{property}/reopen', [PropertyController::class, 'reopen'])
        ->middleware(EnsurePropertyIsManageable::class.':closed')
        ->name('properties.reopen');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
