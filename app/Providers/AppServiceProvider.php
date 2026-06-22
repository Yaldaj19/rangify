<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Older MySQL/MariaDB caps index key length; limit default string length
        // so utf8mb4 unique indexes (e.g. users.email) stay within the limit.
        Schema::defaultStringLength(191);

        Vite::prefetch(concurrency: 3);
    }
}
