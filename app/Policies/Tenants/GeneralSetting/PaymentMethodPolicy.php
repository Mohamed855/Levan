<?php

declare(strict_types=1);

namespace App\Policies\Tenants\GeneralSetting;

final class PaymentMethodPolicy extends LookupDeletePolicy
{
    protected const PERMISSION = 'payment_methods.view';
}
