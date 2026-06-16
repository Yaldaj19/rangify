<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $admin = auth()->user();

        $query = User::query()->with('tenant')->withCount('projects')->latest();

        if (! $admin->isSuperAdmin()) {
            // client-admin: فقط کاربرهای عادیِ tenant خودش
            $query->where('tenant_id', $admin->tenant_id)->role('user');
        }

        return view('admin.users.index', [
            'users' => $query->paginate(20),
            'isSuperAdmin' => $admin->isSuperAdmin(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', [
            'isSuperAdmin' => auth()->user()->isSuperAdmin(),
            'tenants' => auth()->user()->isSuperAdmin()
                ? Tenant::orderBy('name')->get()
                : collect(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $tenantId = $this->resolveTenantId($request->user(), $data['tenant_id'] ?? null);
        if ($tenantId === null) {
            return back()->withInput()->with('error', __('A tenant must be selected to create a user.'));
        }

        [$password, $plain] = $this->resolvePassword($data['password'] ?? null);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'password' => $password,
            'tenant_id' => $tenantId,
            'edit_quota' => $request->boolean('unlimited') ? null : (int) $data['edit_quota'],
        ]);

        $user->assignRole('user');

        $redirect = redirect()->route('admin.users.index')
            ->with('success', __('User :name created.', ['name' => $user->name]));

        if ($plain !== null) {
            $redirect->with('generated_password', $plain)
                ->with('generated_password_for', $user->name);
        }

        return $redirect;
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.edit', ['user' => $user]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $user->name = $data['name'];
        $user->email = $data['email'] ?? null;
        $user->phone = $data['phone'] ?? null;
        $user->edit_quota = $request->boolean('unlimited') ? null : (int) $data['edit_quota'];

        // رمز فقط در صورتی تغییر می‌کند که مدیر مقدار جدید وارد کند.
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return redirect()->route('admin.users.index')
            ->with('success', __('User :name updated.', ['name' => $user->name]));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', __('User :name deleted.', ['name' => $name]));
    }

    /** tenant مقصد را تعیین می‌کند: client-admin → tenant خودش؛ super-admin → انتخابی. */
    private function resolveTenantId(User $admin, ?int $requestedTenantId): ?int
    {
        if ($admin->isSuperAdmin()) {
            return $requestedTenantId;
        }

        return $admin->tenant_id;
    }

    /** اگر رمز داده نشده باشد یک رمز تصادفی می‌سازد و نسخه‌ی متنی را هم برمی‌گرداند. */
    private function resolvePassword(?string $given): array
    {
        if (! empty($given)) {
            return [Hash::make($given), null];
        }

        $plain = Str::password(10, symbols: false);

        return [Hash::make($plain), $plain];
    }
}
