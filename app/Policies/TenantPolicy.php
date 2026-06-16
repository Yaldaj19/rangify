<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

/**
 * مدیریت کارفرماها (tenantها) فقط در اختیار super-admin است.
 * before() برای super-admin true برمی‌گرداند؛ بقیه‌ی نقش‌ها دسترسی ندارند.
 */
class TenantPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return false;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return false;
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return false;
    }
}
