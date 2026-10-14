<?php

declare(strict_types=1);

namespace App\Policies\Tenants\GeneralSetting;

final class CountryPolicy extends LookupDeletePolicy
{
    protected const PERMISSION = 'countries.view';
}
