<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTenantRequest;
use App\Http\Requests\Admin\UpdateTenantRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * مدیریت کارفرماها (tenant) — فقط super-admin.
 * هر کارفرما یک مدیر (client-admin) به‌عنوان مالک دارد.
 */
class TenantController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Tenant::class);

        return view('admin.tenants.index', [
            'tenants' => Tenant::with('owner')
                ->withCount(['users', 'projects'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Tenant::class);

        return view('admin.tenants.create');
    }

    public function store(StoreTenantRequest $request): RedirectResponse
    {
        $this->authorize('create', Tenant::class);

        $data = $request->validated();
        [$password, $plain] = $this->resolvePassword($data['admin_password'] ?? null);

        $tenant = DB::transaction(function () use ($data, $password) {
            $tenant = Tenant::create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'status' => 'active',
            ]);

            $owner = User::create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'] ?? null,
                'phone' => $data['admin_phone'] ?? null,
                'password' => $password,
                'tenant_id' => $tenant->id,
            ]);
            $owner->assignRole('client-admin');

            $tenant->update(['owner_id' => $owner->id]);

            return $tenant;
        });

        $redirect = redirect()->route('admin.tenants.index')
            ->with('success', __('Tenant :name and its admin created.', ['name' => $tenant->name]));

        if ($plain !== null) {
            $redirect->with('generated_password', $plain)
                ->with('generated_password_for', $data['admin_name']);
        }

        return $redirect;
    }

    public function edit(Tenant $tenant): View
    {
        $this->authorize('update', $tenant);

        return view('admin.tenants.edit', ['tenant' => $tenant]);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $tenant->update($request->validated());

        return redirect()->route('admin.tenants.index')
            ->with('success', __('Tenant :name updated.', ['name' => $tenant->name]));
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        $this->authorize('delete', $tenant);

        $name = $tenant->name;

        DB::transaction(function () use ($tenant) {
            // حذف کاربرهای این کارفرما (پروژه‌هایشان با cascade حذف می‌شوند).
            $tenant->users()->each(fn (User $u) => $u->delete());
            $tenant->delete();
        });

        return redirect()->route('admin.tenants.index')
            ->with('success', __('Tenant :name and all its users deleted.', ['name' => $name]));
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'tenant';
        $slug = $base;
        $i = 1;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }

    private function resolvePassword(?string $given): array
    {
        if (! empty($given)) {
            return [Hash::make($given), null];
        }

        $plain = Str::password(10, symbols: false);

        return [Hash::make($plain), $plain];
    }
}
