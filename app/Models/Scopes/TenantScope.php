<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * محدودسازی خودکار کوئری‌ها به tenant کاربر فعلی.
 * super-admin از این scope عبور داده می‌شود (دسترسی کامل).
 * درخواست‌های بدون کاربر (CLI / مهمان) محدود نمی‌شوند.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! auth()->check()) {
            return;
        }

        $user = auth()->user();

        if ($user->hasRole('super-admin')) {
            return;
        }

        if ($user->tenant_id !== null) {
            $builder->where($model->getTable() . '.tenant_id', $user->tenant_id);
        }
    }
}
