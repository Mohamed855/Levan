<?php

declare(strict_types=1);

namespace App\Policies\Tenants\GeneralSetting;

final class ContactMethodPolicy extends LookupDeletePolicy
{
    protected const PERMISSION = 'contact_methods.view';
}
