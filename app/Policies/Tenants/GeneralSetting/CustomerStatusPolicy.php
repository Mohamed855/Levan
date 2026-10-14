<?php

declare(strict_types=1);

namespace App\Policies\Tenants\GeneralSetting;

final class CustomerStatusPolicy extends LookupDeletePolicy
{
    protected const PERMISSION = 'customer_statuses.view';
}
