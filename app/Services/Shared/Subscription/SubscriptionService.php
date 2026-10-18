<?php

namespace App\Services\shared\Subscription;

use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Billing\PaymentMethod;
use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Exceptions\Subscription\SubscriptionCreationException;
use App\Jobs\Admin\Mail\SendSubscriptionMailJob;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use App\Services\shared\Bills\BillService;
use App\Services\shared\Coupons\CouponService;
use App\Services\shared\Tenant\TenantCreator;
use App\Services\shared\Tenant\TenantInitializer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Facades\Tenancy;

class SubscriptionService
{
    public function __construct(
        private TenantCreator       $tenantCreator,
        private TenantInitializer   $tenantInitializer,
        private SubscriptionManager $subscriptionManager,
        private CouponService       $couponService,
        private BillService         $billService,
    ) {}

    // اشتراك جديد عبر بوابة الدفع الإلكتروني
    public function completeOnlineSubscription(Bill $bill, array $payment): array
    {
        return $this->processNewSubscription($bill, PaymentMethod::ONLINE, $payment);
    }

    public function completeExistingTenantSubscription(Bill $bill, array $payment): void
    {
        try {
            $metadata = $bill->metadata;
            $tenantId = $metadata['tenant_id'] ?? null;
            $planId = $metadata['plan_id'] ?? null;

            if (! $tenantId || ! $planId) {
                throw new SubscriptionCreationException('بيانات الاشتراك غير مكتملة.');
            }

            $tenant = Tenancy::central(fn () => Tenant::findOrFail($tenantId));

            if ($this->subscriptionManager->getTenantSubscription($tenant->id)) {
                throw new SubscriptionCreationException('يوجد اشتراك مسجل لهذه المنشأة بالفعل.');
            }

            $plan = Tenancy::central(fn () => Plan::findOrFail($planId));
            $subscription = $this->subscriptionManager->createSubscription($tenant, $plan, (float) $bill->amount);

            $this->billService->markAsPaid($bill, $tenant->id, $subscription->id, PaymentMethod::ONLINE, $payment);
            $this->applyCoupon($bill);

            SendSubscriptionMailJob::dispatch(
                $metadata['billing_email'] ?? $tenant->primary_contact_email,
                $subscription,
                'new'
            )->onQueue('email');
        } catch (SubscriptionCreationException $e) {
            $this->billService->cancelBill($bill);
            throw $e;
        } catch (\Throwable $e) {
            $this->billService->cancelBill($bill);

            Log::error('فشل تفعيل اشتراك Tenant موجود', [
                'bill_number' => $bill->bill_number,
                'error' => $e->getMessage(),
            ]);

            throw new SubscriptionCreationException('حدث خطأ أثناء تفعيل الاشتراك.');
        }
    }

    // اشتراك جديد عبر تحويل بنكي (بعد موافقة الأدمن)
    public function completeBankTransferSubscription(Bill $bill): array
    {
        return $this->processNewSubscription($bill, PaymentMethod::BANK_TRANSFER, requiresVerification: true);
    }

    public function completeAdminSubscription(Bill $bill, array $data): array
    {
        return $this->processAdminSubscription($bill, $data);
    }

    public function completeRenewal(Bill $bill, array $payment): void
    {
        try {
            $metadata = $bill->metadata;
            $tenantId = $metadata['tenant_id'];

            $tenant         = Tenancy::central(fn() => Tenant::findOrFail($tenantId));
            [$subscription] = $this->subscriptionManager->renewSubscription($tenant, $bill->amount);

            $this->billService->markAsPaid($bill, $tenant->id, $subscription->id, PaymentMethod::ONLINE, $payment);

            $this->applyCoupon($bill);

            SendSubscriptionMailJob::dispatch($metadata['billing_email'], $subscription, 'renew')->onQueue('email');
            //
        } catch (\Throwable $e) {
            //
            $this->billService->cancelBill($bill);

            Log::error('فشل تجديد الاشتراك', [
                'bill_number' => $bill->bill_number,
                'error'       => $e->getMessage(),
            ]);

            throw new SubscriptionCreationException('حدث خطأ أثناء تجديد الاشتراك.');
        }
    }

    public function completeLicensePurchase(Bill $bill, array $payment): void
    {
        try {
            $metadata         = $bill->metadata;
            $newLicensesCount = (int) ($metadata['new_licenses_count'] ?? 0);
            $tenantId         = $metadata['tenant_id'];

            Log::info($metadata);
            $tenant       = Tenancy::central(fn() => Tenant::findOrFail($tenantId));
            $subscription = $this->subscriptionManager->upgradeSubscription($tenant, $newLicensesCount);

            $this->billService->markAsPaid($bill, $tenant->id, $subscription->id, PaymentMethod::ONLINE, $payment);

            $this->applyCoupon($bill);

            SendSubscriptionMailJob::dispatch($metadata['billing_email'], $subscription, 'upgrade')->onQueue('email');
            //
        } catch (\Throwable $e) {
            //
            $this->billService->cancelBill($bill);

            Log::error('فشل شراء التراخيص', [
                'bill_number' => $bill->bill_number,
                'error'       => $e->getMessage(),
            ]);

            throw new SubscriptionCreationException('حدث خطأ أثناء شراء التراخيص.');
        }
    }

    public function completeUnpaidBill(Bill $bill, array $payment): void
    {
        try {
            $tenant       = Tenancy::central(fn() => Tenant::findOrFail($bill->tenant_id));
            $subscription = Tenancy::central(fn() => Subscription::findOrFail($bill->subscription_id));

            // تفعيل الاشتراك
            $this->subscriptionManager->changeSubscriptionToActive($tenant);

            // تحديث الفاتورة
            $this->billService->markAsPaid(
                bill: $bill,
                tenantId: $tenant->id,
                subscriptionId: $subscription->id,
                paymentMethod: PaymentMethod::ONLINE,
                payment: $payment,
            );

            $this->applyCoupon($bill);
        } catch (\Throwable $e) {
            $this->billService->cancelBill($bill);
            Log::error('فشل دفع الفاتورة', ['bill_number' => $bill->bill_number, 'error' => $e->getMessage()]);
            throw new SubscriptionCreationException('حدث خطأ أثناء دفع الفاتورة.');
        }
    }

    // public function completeRenewalByBankTransfer(Bill $bill): void
    // {
    //     try {
    //         $tenant         = Tenancy::central(fn() => Tenant::findOrFail($bill->metadata['tenant_id']));
    //         [$subscription] = $this->subscriptionManager->renewSubscription($tenant, $bill->amount);

    //         $this->billService->markAsPaid($bill, $tenant->id, $subscription->id, PaymentMethod::BANK_TRANSFER, null);
    //         $this->billService->markAsVerified($bill);
    //         $this->applyCoupon($bill);

    //         SendSubscriptionMailJob::dispatch($bill->metadata['billing_email'], $subscription, 'renew')
    //             ->onQueue('email');
    //     } catch (\Throwable $e) {
    //         Log::error('فشل تجديد الاشتراك عبر تحويل بنكي', ['bill_number' => $bill->bill_number, 'error' => $e->getMessage()]);
    //         throw new SubscriptionCreationException('حدث خطأ أثناء تجديد الاشتراك.');
    //     }
    // }

    // public function completeLicensePurchaseByBankTransfer(Bill $bill): void
    // {
    //     try {
    //         $metadata         = $bill->metadata;
    //         $tenant           = Tenancy::central(fn() => Tenant::findOrFail($metadata['tenant_id']));
    //         $newLicensesCount = (int) ($metadata['new_licenses_count'] ?? 0);

    //         $subscription = $this->subscriptionManager->upgradeSubscription($tenant, $newLicensesCount);

    //         $this->billService->markAsPaid($bill, $tenant->id, $subscription->id, PaymentMethod::BANK_TRANSFER, null);
    //         $this->billService->markAsVerified($bill);
    //         $this->applyCoupon($bill);

    //         SendSubscriptionMailJob::dispatch($metadata['billing_email'], $subscription, 'upgrade')
    //             ->onQueue('email');
    //     } catch (\Throwable $e) {
    //         Log::error('فشل شراء التراخيص عبر تحويل بنكي', ['bill_number' => $bill->bill_number, 'error' => $e->getMessage()]);
    //         throw new SubscriptionCreationException('حدث خطأ أثناء شراء التراخيص.');
    //     }
    // }


    /*
    |============================================================================
    |============================================================================
    |                         Private Methods
    |============================================================================
    |============================================================================
    */
    private function applyCoupon(Bill $bill): void
    {
        if (!$bill->coupon_code) return;

        $coupon = $this->couponService->findActiveCoupon($bill->coupon_code);
        //
        if ($coupon) $this->couponService->applyCoupon($coupon);
    }

    // For the Site
    private function processNewSubscription(Bill $bill, PaymentMethod $paymentMethod, ?array $payment = null, bool $requiresVerification = false): array
    {
        try {
            $metadata = $bill->metadata;

            if (empty($metadata['plan_id']) || empty($metadata['billing_email'])) {
                throw new SubscriptionCreationException('بيانات الاشتراك غير مكتملة.');
            }

            // Get Plan
            $plan = Plan::findOrFail($metadata['plan_id']);

            //  إنشاء الـ tenant + المستخدم الأول
            [$tenant, $user] = $this->tenantCreator->createTenantEnvironment($metadata);

            //  إنشاء الاشتراك
            $subscription = $this->subscriptionManager->createSubscription($tenant, $plan, $bill->amount);

            //  ربط المستخدم بالترخيص
            $this->tenantInitializer->createCoreSeat($tenant, $subscription, $user);

            //  تحديث الفاتورة
            $this->billService->markAsPaid($bill, $tenant->id, $subscription->id, $paymentMethod, $payment);

            //  تسجيل من وافق (للتحويل البنكي والأدمن)
            if ($requiresVerification) {
                $this->billService->markAsVerified($bill);
            }

            //  تطبيق الكوبون
            $this->applyCoupon($bill);

            //  إرسال البريد الإلكتروني
            SendSubscriptionMailJob::dispatch($metadata['billing_email'], $subscription, 'new')->onQueue('email');

            return [
                'tenant_id' => $tenant->id,
                'message'   => 'تم إنشاء الاشتراك بنجاح.',
            ];
        } catch (SubscriptionCreationException $e) {
            //
            if ($bill->status !== BillStatus::PAID) {
                $this->billService->cancelBill($bill);
            }
            throw $e;
        } catch (\Throwable $e) {
            //
            if ($bill->status !== BillStatus::PAID) {
                $this->billService->cancelBill($bill);
            }
            Log::error('فشل إنشاء الاشتراك', [
                'bill_number'    => $bill->bill_number,
                'payment_method' => $paymentMethod->value,
                'error'          => $e->getMessage(),
            ]);
            throw new SubscriptionCreationException('حدث خطأ أثناء إنشاء الاشتراك.');
        }
    }

    // For the Admin
    private function processAdminSubscription(Bill $bill, array $data): array
    {
        if (! empty($data['activate_immediately'])) {
            $subscriptionStatus = SubscriptionStatus::ACTIVE;
            $periodStatus = SubscriptionPeriodStatus::ACTIVE;
        } else {
            $subscriptionStatus = SubscriptionStatus::PENDING;
            $periodStatus = SubscriptionPeriodStatus::PENDING_PAYMENT;
        }

        // Get Plan
        $plan = Plan::findOrFail($bill->metadata['plan_id']);

        [$tenant, $user]  = $this->tenantCreator->createTenantEnvironment($bill->metadata);
        $subscription = $this->subscriptionManager->createSubscription(
            $tenant, $plan, $bill->amount, $subscriptionStatus, $periodStatus, $data['trial_end_date'] ?? null,
            ['start_date' => $data['start_date'] ?? null, 'end_date' => $data['end_date'] ?? null, 'total_users' => $data['total_users'] ?? $plan->included_users]
        );
        $tenant->update(['status' => ! empty($data['activate_immediately']) ? 'active' : 'pending']);

        //  ربط المستخدم بالترخيص
        $this->tenantInitializer->createCoreSeat($tenant, $subscription, $user);

        $this->handleAdminBill($bill, $tenant, $subscription, $data);

        // $this->applyCoupon($bill);

        SendSubscriptionMailJob::dispatch($bill->metadata['billing_email'], $subscription, 'new')->onQueue('email');

        return ['tenant_id' => $tenant->id, 'message' => 'تم إنشاء الاشتراك بنجاح.'];
    }

    private function resolveSubscriptionStatus(string $paymentType): array
    {
        return match ($paymentType) {
            'paid'   => [SubscriptionStatus::ACTIVE,   SubscriptionPeriodStatus::ACTIVE],
            'unpaid' => [SubscriptionStatus::SUSPENDED, SubscriptionPeriodStatus::PENDING_PAYMENT],
            'trial'  => [SubscriptionStatus::TRIAL,     SubscriptionPeriodStatus::TRIAL],
        };
    }

    private function handleAdminBill(Bill $bill, Tenant $tenant, Subscription $subscription, array $data): void
    {
        match ($data['payment_type']) {
            'paid' => (function () use ($bill, $tenant, $subscription, $data) {
                $paymentMethod = match ($data['payment_method']) {
                    'cash'          => PaymentMethod::CASH,
                    'bank_transfer' => PaymentMethod::BANK_TRANSFER,
                    'online'        => PaymentMethod::ONLINE,
                };

                $this->billService->markAsPaid($bill, $tenant->id, $subscription->id, $paymentMethod, null);

                $updateData = ['verified_by' => Auth::id(), 'verified_at' => now()];

                if (($data['has_discount'] ?? 'no') === 'yes') {
                    $updateData['settlement_amount'] = $data['discount_value'];
                }

                if (!empty($data['receipt_path'])) {
                    $updateData['invoice_url'] = $data['receipt_path'];
                }

                $bill->update($updateData);
            })(),

            'unpaid' => $bill->update([
                'tenant_id'       => $tenant->id,
                'subscription_id' => $subscription->id,
                'status'          => BillStatus::UNPAID,
            ]),

            'trial' => $bill->update([
                'tenant_id'       => $tenant->id,
                'subscription_id' => $subscription->id,
                'status'          => BillStatus::TRIAL,
            ]),
        };
    }
}
