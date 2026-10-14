<?php

declare(strict_types=1);

namespace App\Policies\Tenants\GeneralSetting;

final class CustomerDistributionPolicy extends LookupDeletePolicy
{
    protected const PERMISSION = 'customer_distributions.create';
}
