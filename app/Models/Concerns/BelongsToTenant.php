<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/**
 * هر مدلی که این trait را داشته باشد:
 *  - به‌صورت خودکار با TenantScope محدود به tenant کاربر فعلی می‌شود
 *    (به‌جز super-admin که همه‌چیز را می‌بیند).
 *  - هنگام ساخت، اگر tenant_id خالی باشد با tenant کاربر فعلی پر می‌شود.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (Model $model) {
            if (empty($model->tenant_id) && auth()->check()) {
                $tenantId = auth()->user()->tenant_id;
                if ($tenantId !== null) {
                    $model->tenant_id = $tenantId;
                }
            }
        });
    }
}
