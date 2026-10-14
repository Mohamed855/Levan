<?php

declare(strict_types=1);

namespace App\Policies\Tenants\GeneralSetting;

final class ContactResultPolicy extends LookupDeletePolicy
{
    protected const PERMISSION = 'contact_results.view';
}
