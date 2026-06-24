<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * محدودیت تست رایگان برای مهمان روی APIهای هوش مصنوعی.
 *
 * هر IP مهمان در هر روز حداکثر DAILY_IMAGE_LIMIT تصویر متفاوت می‌تواند
 * پردازش AI کند (روی هر تصویر، هر تعداد درخواست آزاد است). کاربران
 * واردشده هیچ محدودیتی ندارند. شمارش بر اساس هش تصویر است؛ فرانت‌اند
 * هش پایدارِ تصویر اصلی را در image_hash می‌فرستد و اگر نبود، از محتوای
 * image یک هش می‌سازیم.
 */
class LimitGuestTrial
{
    private const DAILY_IMAGE_LIMIT = 2;

    public function handle(Request $request, Closure $next): Response
    {
        // کاربران واردشده معاف‌اند (سهمیه‌ی آن‌ها جداگانه مدیریت می‌شود).
        if ($request->user()) {
            return $next($request);
        }

        $hash = (string) $request->input('image_hash', '');
        if ($hash === '') {
            $image = (string) $request->input('image', '');
            $hash = $image !== '' ? sha1($image) : '';
        }

        // اگر هش قابل‌تشخیص نبود، عبور بده (throttle محافظت سبک را تأمین می‌کند).
        if ($hash === '') {
            return $next($request);
        }

        $key = 'trial_guest:' . $request->ip() . ':' . now()->toDateString();
        $images = Cache::get($key, []);

        // همان تصویرِ امروز → آزاد (هر تعداد درخواست روی یک تصویر مجاز است).
        if (in_array($hash, $images, true)) {
            return $next($request);
        }

        // تصویر جدید ولی سقف روزانه پر شده → مسدود.
        if (count($images) >= self::DAILY_IMAGE_LIMIT) {
            return response()->json([
                'error' => 'به سقف تست رایگان روزانه رسیدید. برای ادامه رایگان ثبت‌نام کنید.',
                'trial_limit_reached' => true,
            ], 403);
        }

        // ثبت تصویر جدید؛ انقضا در پایان همان روز (ریست روزانه).
        $images[] = $hash;
        Cache::put($key, $images, now()->endOfDay());

        return $next($request);
    }
}
