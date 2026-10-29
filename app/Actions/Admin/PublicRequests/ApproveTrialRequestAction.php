<?php

declare(strict_types=1);

namespace App\Actions\Admin\PublicRequests;

use App\Enums\Admin\PublicRequests\TrialRequestStatus;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\PublicRequests\TrialRequest;
use App\Models\Admin\Tenants\Tenant;
use App\Rules\SystemEmailAvailable;
use App\Rules\SystemPhoneAvailable;
use App\Services\Shared\Subscription\SubscriptionManager;
use App\Services\Shared\Tenant\TenantCreator;
use App\Services\Shared\Tenant\TenantInitializer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ApproveTrialRequestAction
{
    public function __construct(
        private readonly TenantCreator $creator,
        private readonly TenantInitializer $initializer,
        private readonly SubscriptionManager $subscriptions,
    ) {}

    public function execute(TrialRequest $request, AdminUser $admin): TrialRequest
    {
        $request = DB::connection('mysql')->transaction(function () use ($request): TrialRequest {
            $locked = TrialRequest::lockForUpdate()->findOrFail($request->id);
            abort_unless($locked->status === TrialRequestStatus::PENDING, 409, 'Request already processed.');
            abort_if($locked->provisioning_status === 'processing' && $locked->provisioning_started_at?->gt(now()->subMinutes(15)), 409, 'يجري إنشاء حساب هذا الطلب بالفعل.');
            if (! $locked->password_hash || ! $locked->email_verified_at || ! $locked->company_name || ! $locked->commercial_number || ! $locked->tax_number || ! $locked->city_id || ! $locked->address) {
                throw ValidationException::withMessages(['trial_request' => 'هذا طلب قديم أو غير مكتمل. يجب تقديم البيانات الكاملة وتأكيد البريد قبل إنشاء الحساب.']);
            }
            $locked->update(['provisioning_status' => 'processing', 'provisioning_error' => null, 'provisioning_started_at' => now()]);

            return $locked;
        });

        try {
            $metadata = [
                'billing_name' => $request->customer_name, 'billing_email' => $request->customer_email,
                'billing_phone' => $request->customer_phone, 'billing_password_hash' => $request->password_hash,
                'billing_company_name' => $request->company_name, 'billing_commercial_number' => $request->commercial_number,
                'billing_tax_number' => $request->tax_number, 'billing_city_id' => $request->city_id,
                'billing_address' => $request->address,
            ];
            if ($request->tenant_id) {
                $tenant = Tenant::findOrFail($request->tenant_id);
                $user = $this->initializer->setupTenantEnvironment($tenant, $metadata);
            } else {
                Validator::make($metadata, [
                    'billing_company_name' => 'unique:tenants,company_name',
                    'billing_commercial_number' => 'unique:tenants,commercial_number',
                    'billing_tax_number' => 'unique:tenants,tax_number',
                    'billing_email' => [new SystemEmailAvailable],
                    'billing_phone' => [new SystemPhoneAvailable],
                ])->validate();
                [$tenant, $user] = $this->creator->createTenantEnvironment($metadata, function (Tenant $tenant) use ($request): void {
                    $request->update(['tenant_id' => $tenant->id]);
                });
            }
            $subscription = $this->subscriptions->createTrialSubscription($tenant);
            $this->initializer->createCoreSeat($tenant, $subscription, $user);
            $tenant->update(['primary_user_global_uid' => $user->global_uid]);
            DB::connection('mysql')->transaction(function () use ($request, $subscription, $admin): void {
                $start = now();
                $end = $start->copy()->addDays((int) config('trial.days', 7));
                $subscription->update(['starts_at' => $start, 'ends_at' => $end, 'trial_end_date' => $end->toDateString()]);
                $subscription->currentPeriod()->update(['period_start' => $start, 'period_end' => $end]);
                $request->update([
                    'status' => TrialRequestStatus::APPROVED, 'subscription_id' => $subscription->id,
                    'processed_at' => now(), 'processed_by' => $admin->getKey(),
                    'provisioning_status' => 'ready', 'provisioning_error' => null, 'password_hash' => null,
                ]);
            });
            cache()->forget("tenant:{$tenant->id}:subscription");

            $loginUrl = route('path.dashboard', ['tenant' => $tenant->id], false);
            if (! str_starts_with($loginUrl, 'http')) {
                $loginUrl = url($loginUrl);
            }
            \App\Services\Shared\Mail\SystemMailService::sendTrialRequestApproved(
                $request->customer_email,
                $request->customer_name,
                $loginUrl,
                (int) config('trial.days', 7),
                isset($end) ? $end->toDateString() : null
            );
        } catch (Throwable $exception) {
            $request->update(['provisioning_status' => 'failed', 'provisioning_error' => 'تعذر إكمال إنشاء الحساب. يرجى إعادة المحاولة أو مراجعة سجل الأخطاء.']);
            throw $exception;
        }
        cache()->forget('admin:pending_trial_requests_count');

        return $request->fresh(['tenant', 'subscription']);
    }
}
