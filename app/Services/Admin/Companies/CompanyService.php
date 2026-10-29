<?php

declare(strict_types=1);

namespace App\Services\Admin\Companies;

use App\Enums\Admin\Billing\BillType;
use App\Enums\Admin\Billing\PaymentMethod;
use App\Enums\Admin\PublicRequests\TrialRequestStatus;
use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\CompanySetting;
use App\Models\Admin\Tenants\Tenant;
use App\Services\Admin\Subscriptions\AdminSubscriptionService;
use App\Services\Shared\Bills\BillService;
use App\Services\Shared\Subscription\SubscriptionService;
use App\Services\Shared\Tenant\TenantCreator;
use App\Services\Shared\Tenant\TenantInitializer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

class CompanyService
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly AdminSubscriptionService $adminSubscriptionService,
        private readonly BillService $billService,
        private readonly TenantCreator $tenantCreator,
        private readonly TenantInitializer $tenantInitializer
    ) {}

    public function getCompanyStatistics(): array
    {
        $baseQuery = Tenant::query();

        $trialCount = (clone $baseQuery)->where(function ($query) {
            $query->whereHas('subscriptions', function ($subQuery) {
                $subQuery->where('status', SubscriptionStatus::TRIAL)
                    ->where(function ($dateQuery) {
                        $dateQuery->where('ends_at', '>=', now())
                            ->orWhere('trial_end_date', '>=', now()->toDateString());
                    });
            })->orWhereIn('id', function ($subQuery) {
                $subQuery->select('tenant_id')
                    ->from('trial_requests')
                    ->where('status', TrialRequestStatus::APPROVED->value)
                    ->whereNotNull('tenant_id')
                    ->where('processed_at', '>=', now()->subDays(7));
            });
        })->count();

        return [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->whereHas('lastSubscription', function ($query) {
                $query->where('status', SubscriptionStatus::ACTIVE)
                    ->where(function ($periodQuery) {
                        $periodQuery->whereHas('currentPeriod', function ($currentPeriod) {
                            $currentPeriod->where('status', SubscriptionPeriodStatus::ACTIVE)
                                ->where('period_start', '<=', now())
                                ->where('period_end', '>=', now());
                        })->orWhereDoesntHave('periods');
                    });
            })->count(),
            'suspended' => (clone $baseQuery)->whereHas('lastSubscription', function ($query) {
                $query->whereIn('status', [SubscriptionStatus::SUSPENDED, SubscriptionStatus::EXPIRED]);
            })->count(),
            'pending' => (clone $baseQuery)->whereHas('lastSubscription', function ($query) {
                $query->where('status', SubscriptionStatus::PENDING);
            })->count(),
            'withoutSubscription' => (clone $baseQuery)->whereDoesntHave('subscriptions')->count(),
            'trial' => $trialCount,
        ];
    }

    public function createCompany(array $data): Tenant
    {
        try {
            $plan = Plan::findOrFail($data['plan_id']);
            $totalUsers = $plan->included_users;
            $data['total_users'] = $totalUsers;

            $metadata = [
                'plan_id' => $data['plan_id'],
                'billing_name' => $data['name'],
                'billing_email' => $data['email'],
                'billing_phone' => $data['phone'],
                'billing_password' => $data['password'],
                'billing_company_name' => $data['company_name'],
                'billing_tax_number' => $data['tax_number'] ?? null,
                'billing_city_id' => $data['city_id'] ?? null,
                'billing_address' => $data['address'] ?? null,
                'billing_commercial_number' => $data['commercial_number'],
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'total_users' => $totalUsers,
                'activate_immediately' => ! empty($data['activate_immediately']),
                'email_notifications_enabled' => isset($data['email_notifications_enabled']) ? (bool) $data['email_notifications_enabled'] : true,
                'two_factor_enabled' => ! empty($data['two_factor_enabled']),
                'external_integrations_enabled' => ! empty($data['external_integrations_enabled']),
            ];

            $amount = (float) $plan->price_after_discount;

            $bill = $this->billService->createPendingBill(
                amount: $amount,
                paymentMethod: null,
                originalAmount: (float) $plan->price_after_discount,
                type: BillType::NEW_SUBSCRIPTION,
                couponData: [
                    'discount_amount' => 0,
                    'coupon_id' => null,
                    'coupon_code' => null,
                    'coupon_value' => null,
                    'coupon_type' => null,
                ],
                metadata: $metadata
            );

            $result = $this->subscriptionService->completeAdminSubscription($bill, $data);
            $tenant = Tenant::findOrFail($result['tenant_id']);

            CompanySetting::updateOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'email_notifications_enabled' => (bool) ($metadata['email_notifications_enabled'] ?? true),
                    'two_factor_enabled' => (bool) ($metadata['two_factor_enabled'] ?? false),
                    'external_integrations_enabled' => (bool) ($metadata['external_integrations_enabled'] ?? false),
                ]
            );

            $this->clearCompanyCache($tenant->id);

            return $tenant;
        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    public function updateCompany(Tenant $company, array $data): Tenant
    {
        return DB::connection('mysql')->transaction(function () use ($company, $data) {
            $updateData = [
                'company_name' => $data['company_name'],
                'commercial_number' => $data['commercial_number'],
                'tax_number' => $data['tax_number'] ?? null,
                'city_id' => $data['city_id'] ?? null,
                'address' => $data['address'] ?? null,
            ];

            if (array_key_exists('primary_contact_name', $data)) {
                $updateData['primary_contact_name'] = $data['primary_contact_name'];
            }
            if (array_key_exists('primary_contact_email', $data)) {
                $updateData['primary_contact_email'] = $data['primary_contact_email'];
            }
            if (array_key_exists('primary_contact_phone', $data)) {
                $updateData['primary_contact_phone'] = $data['primary_contact_phone'];
            }

            $company->update($updateData);

            // Update or create Company Settings
            CompanySetting::updateOrCreate(
                ['tenant_id' => $company->id],
                [
                    'email_notifications_enabled' => ! empty($data['email_notifications_enabled']),
                    'two_factor_enabled' => ! empty($data['two_factor_enabled']),
                    'external_integrations_enabled' => ! empty($data['external_integrations_enabled']),
                ]
            );

            $this->clearCompanyCache($company->id);

            return $company->fresh(['companySettings', 'city']);
        });
    }

    public function deleteCompany(Tenant $company): bool
    {
        return DB::connection('mysql')->transaction(function () use ($company) {
            $this->clearCompanyCache($company->id);

            return (bool) $company->delete();
        });
    }

    public function restoreCompany(string $id): Tenant
    {
        $company = Tenant::onlyTrashed()->findOrFail($id);
        $company->restore();

        $this->clearCompanyCache($company->id);

        return $company;
    }

    public function forceDeleteCompany(string $id): bool
    {
        $company = Tenant::onlyTrashed()->findOrFail($id);

        return DB::connection('mysql')->transaction(function () use ($company) {
            // Clean up all subscriptions permanently if any left
            $company->subscriptions()->get()->each(function (Subscription $subscription) {
                $this->adminSubscriptionService->deleteSubscription($subscription);
            });

            CompanySetting::where('tenant_id', $company->id)->delete();

            $this->clearCompanyCache($company->id);

            return (bool) $company->forceDelete();
        });
    }

    public function getShowData(Tenant $company): array
    {
        $company->load([
            'city',
            'companySettings',
            'subscriptions' => fn ($q) => $q->with(['plan', 'currentPeriod', 'periods'])->latest('id'),
        ]);

        $activities = Activity::query()
            ->where(function ($query) use ($company) {
                $query->where('properties->tenant_id', $company->id)
                    ->orWhere(function ($q) use ($company) {
                        $q->where('subject_type', Tenant::class)
                            ->where('subject_id', $company->id);
                    });
            })
            ->latest('id')
            ->take(30)
            ->get();

        return [
            'company' => $company,
            'settings' => $company->companySettings ?: new CompanySetting([
                'tenant_id' => $company->id,
                'email_notifications_enabled' => true,
                'two_factor_enabled' => false,
                'external_integrations_enabled' => false,
            ]),
            'activities' => $activities,
        ];
    }

    public function clearCompanyCache(string $tenantId): void
    {
        Cache::forget("tenant:{$tenantId}:subscription");
        Cache::forget("tenant:{$tenantId}:settings");
    }
}
