<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Admin\AdminUser;
use App\Models\Admin\Faq;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\PublicRequests\ContactRequest;
use App\Models\Admin\PublicRequests\TrialRequest;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard.index', [
            'statistics' => [
                ['label' => 'إجمالي الباقات', 'value' => Plan::query()->count(), 'icon' => 'ti-box-seam', 'tone' => 'primary'],
                ['label' => 'الباقات النشطة', 'value' => Plan::query()->where('is_active', true)->count(), 'icon' => 'ti-circle-check', 'tone' => 'success'],
                ['label' => 'طلبات التجربة الجديدة', 'value' => TrialRequest::query()->where('status', 'pending')->count(), 'icon' => 'ti-clock-hour-4', 'tone' => 'info'],
                ['label' => 'رسائل التواصل الجديدة', 'value' => ContactRequest::query()->where('status', 'pending')->count(), 'icon' => 'ti-message-2', 'tone' => 'warning'],
                ['label' => 'مديرو النظام', 'value' => AdminUser::query()->where('status', 'active')->count(), 'icon' => 'ti-shield-check', 'tone' => 'danger'],
            ],
            'recentRequests' => TrialRequest::query()->latest()->limit(5)->get(),
            'faqCount' => Faq::query()->count(),
        ]);
    }
}
