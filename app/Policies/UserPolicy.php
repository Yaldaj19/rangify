<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /** super-admin به همه‌چیز دسترسی دارد. */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('manage users');
    }

    public function view(User $user, User $model): bool
    {
        return $this->managesInSameTenant($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('manage users');
    }

    public function update(User $user, User $model): bool
    {
        return $this->managesInSameTenant($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        // مدیر نمی‌تواند خودش را حذف کند.
        return $user->id !== $model->id && $this->managesInSameTenant($user, $model);
    }

    /**
     * client-admin فقط کاربرهای عادیِ tenant خودش را مدیریت می‌کند —
     * نه مدیرهای دیگر و نه کاربرهای tenant‌های دیگر.
     */
    private function managesInSameTenant(User $user, User $model): bool
    {
        return $user->can('manage users')
            && $user->tenant_id !== null
            && $user->tenant_id === $model->tenant_id
            && $model->isRegularUser();
    }
}
