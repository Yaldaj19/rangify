<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'tenant_id',
        'edit_quota',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'edit_quota' => 'integer',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    public function isClientAdmin(): bool
    {
        return $this->hasRole('client-admin');
    }

    public function isRegularUser(): bool
    {
        return $this->hasRole('user');
    }

    /** سهمیه نامحدود؟ (مدیرها همیشه نامحدود؛ کاربر با edit_quota=null هم نامحدود) */
    public function hasUnlimitedQuota(): bool
    {
        return $this->isSuperAdmin() || $this->isClientAdmin() || $this->edit_quota === null;
    }

    /** تعداد تصویرهای ساخته‌شده (واحد مصرف سهمیه = هر پروژه). */
    public function usedEdits(): int
    {
        return $this->projects()->count();
    }

    /** سهمیه باقیمانده؛ null یعنی نامحدود. */
    public function remainingEdits(): ?int
    {
        if ($this->hasUnlimitedQuota()) {
            return null;
        }

        return max(0, (int) $this->edit_quota - $this->usedEdits());
    }

    /** آیا کاربر اجازه‌ی ساخت تصویر/پروژه‌ی جدید را دارد؟ */
    public function canEdit(): bool
    {
        return $this->hasUnlimitedQuota() || $this->usedEdits() < (int) $this->edit_quota;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
