<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Tenant\User;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('tenant.dashboard.index', [
            'statistics' => [
                ['label' => 'إجمالي المستخدمين', 'value' => User::query()->count(), 'icon' => 'ti-users', 'tone' => 'primary'],
                ['label' => 'المستخدمون النشطون', 'value' => User::query()->where('status', 'active')->count(), 'icon' => 'ti-user-check', 'tone' => 'success'],
                ['label' => 'المستخدمون غير النشطين', 'value' => User::query()->where('status', 'inactive')->count(), 'icon' => 'ti-user-off', 'tone' => 'danger'],
                ['label' => 'البريد الموثق', 'value' => User::query()->whereNotNull('email_verified_at')->count(), 'icon' => 'ti-mail-check', 'tone' => 'info'],
                ['label' => 'مستخدمون جدد هذا الشهر', 'value' => User::query()->where('created_at', '>=', now()->startOfMonth())->count(), 'icon' => 'ti-user-plus', 'tone' => 'warning'],
            ],
            'recentUsers' => User::query()->latest()->limit(5)->get(),
        ]);
    }
}
