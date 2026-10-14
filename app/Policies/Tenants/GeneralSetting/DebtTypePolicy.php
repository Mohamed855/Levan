<?php

declare(strict_types=1);

namespace App\Policies\Tenants\GeneralSetting;

final class DebtTypePolicy extends LookupDeletePolicy
{
    protected const PERMISSION = 'debt_types.view';
}
