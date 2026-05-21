<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * داشبورد نقش‌محور — محتوای متفاوت برای super-admin / client-admin / user.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->hasRole('super-admin')) {
            return view('dashboard', [
                'role' => 'super-admin',
                'stats' => [
                    'tenants' => Tenant::count(),
                    'users' => User::count(),
                    'projects' => Project::count(),
                ],
                'tenants' => Tenant::withCount(['users', 'projects'])->latest()->take(10)->get(),
            ]);
        }

        if ($user->hasRole('client-admin')) {
            $tenant = $user->tenant;

            return view('dashboard', [
                'role' => 'client-admin',
                'tenant' => $tenant,
                'stats' => [
                    'members' => $tenant?->users()->count() ?? 0,
                    'projects' => $tenant?->projects()->count() ?? 0,
                ],
                'members' => $tenant?->users()->latest()->take(10)->get() ?? collect(),
                'projects' => $tenant?->projects()->with('user')->latest()->take(10)->get() ?? collect(),
            ]);
        }

        // کاربر عادی — فقط پروژه‌های خودش.
        return view('dashboard', [
            'role' => 'user',
            'projects' => $user->projects()->latest()->get(),
        ]);
    }
}
