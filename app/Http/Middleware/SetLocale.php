<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * زبان برنامه را از session می‌خواند (پیش‌فرض فارسی).
 * با /locale/{fa|en} قابل تغییر است.
 */
class SetLocale
{
    public const SUPPORTED = ['fa', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale', 'fa'); // پیش‌فرض: فارسی

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = 'fa';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
