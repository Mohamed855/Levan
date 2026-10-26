<?php

declare(strict_types=1);

namespace App\Services\Tenants\Shared;

use App\Models\Admin\Subscription\Subscription;
use Illuminate\Validation\ValidationException;
use Stancl\Tenancy\Facades\Tenancy;

final class CentralTrialAccessGateway
{
    public function isSingleUserTrial(): bool
    {
        $tenantId = tenant('id');
        if (! $tenantId) {
            return false;
        }

        return Tenancy::central(function () use ($tenantId): bool {
            $subscription = Subscription::where('tenant_id', $tenantId)->latest('id')->first();

            return $subscription && $subscription->is_trial && $subscription->plan_id === null;
        });
    }

    public function assertUserCreationAllowed(): void
    {
        if ($this->isSingleUserTrial()) {
            throw ValidationException::withMessages(['user_limit' => 'التجربة المجانية تشمل المدير الأساسي فقط. لا يمكن إضافة مستخدم آخر أثناء التجربة.']);
        }
    }
}
