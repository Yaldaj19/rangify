<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * جلوگیری از ورود کاربر عادیِ بدون سهمیه به ادیتور / ساخت پروژه‌ی جدید.
 * مدیرها (نامحدود) همیشه عبور می‌کنند.
 */
class EnsureUserHasQuota
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->canEdit()) {
            $message = __('Your image edit quota has run out. Contact your administrator to increase it.');

            if ($request->expectsJson()) {
                return response()->json(['error' => $message], 403);
            }

            return redirect()->route('dashboard')->with('error', $message);
        }

        return $next($request);
    }
}
