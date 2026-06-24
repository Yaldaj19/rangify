<?php

declare(strict_types=1);

use App\Http\Controllers\AiVisionController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SmartSelectController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// تغییر زبان (فارسی / انگلیسی) — عمومی، برای صفحه ورود هم کار می‌کند.
Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, \App\Http\Middleware\SetLocale::SUPPORTED, true)) {
        session(['locale' => $locale]);
    }

    return back();
})->name('locale.switch');

// ادیتور رنگ‌آمیزی (تست رایگان) — عمومی، بدون نیاز به لاگین.
Route::get('/trial', function () {
    return view('trial');
})->name('trial');

// API هوش مصنوعی — عمومی (ادیتور تست رایگان ازش استفاده می‌کند).
// throttle: محافظت سبک در برابر spam. trial.guest: سقف تست رایگان روزانه‌ی مهمان (per-IP).
Route::middleware(['throttle:60,1', 'trial.guest'])->group(function () {
    Route::post('/api/ai/segment', [AiVisionController::class, 'segment'])
        ->name('ai.segment');
    Route::post('/api/ai/smart-point', [SmartSelectController::class, 'point'])
        ->name('ai.smart-point');
    Route::post('/api/ai/segment-surfaces', [SmartSelectController::class, 'semantic'])
        ->name('ai.segment-surfaces');
    Route::post('/api/ai/precompute', [SmartSelectController::class, 'precompute'])
        ->name('ai.precompute');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('admin')->name('admin.')->group(function () {
        // مدیریت کاربر — super-admin و client-admin (ایزوله‌سازی tenant در Policy).
        Route::resource('users', UserController::class)
            ->except(['show'])
            ->middleware('permission:manage users');

        // مدیریت کارفرما — فقط super-admin.
        Route::resource('tenants', TenantController::class)
            ->except(['show'])
            ->middleware('role:super-admin');
    });
});

require __DIR__.'/auth.php';
