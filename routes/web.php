<?php

declare(strict_types=1);

use App\Http\Controllers\AiVisionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SmartSelectController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/trial', function () {
    return view('trial');
})->name('trial');

Route::post('/api/ai/segment', [AiVisionController::class, 'segment'])
    ->name('ai.segment');

Route::post('/api/ai/smart-point', [SmartSelectController::class, 'point'])
    ->name('ai.smart-point');

Route::post('/api/ai/precompute', [SmartSelectController::class, 'precompute'])
    ->name('ai.precompute');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
