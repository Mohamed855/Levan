<?php

declare(strict_types=1);

namespace App\Policies\Tenants\FinancialCollection\Reconciliation;

use App\Models\Tenants\FinancialCollection\Reconciliation\PaymentReconciliation;
use App\Models\Tenants\UserMangment\User\User;

class PaymentReconciliationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reconciliation.view');
    }

    public function view(User $user, PaymentReconciliation $reconciliation): bool
    {
        return $user->can('reconciliation.view') && tenancy()->initialized;
    }

    public function create(User $user): bool
    {
        return $user->can('reconciliation.view');
    }

    public function update(User $user, PaymentReconciliation $reconciliation): bool
    {
        return false;
    }

    public function delete(User $user, PaymentReconciliation $reconciliation): bool
    {
        return false;
    }
}
