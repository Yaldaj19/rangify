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
        // Older MySQL/MariaDB caps index key length (1000 bytes here). With utf8mb4
        // (4 bytes/char) a two-column unique index (e.g. spatie permissions
        // name+guard_name) needs <=125 per column, so cap default string length.
        Schema::defaultStringLength(125);

        Vite::prefetch(concurrency: 3);
    }
}
