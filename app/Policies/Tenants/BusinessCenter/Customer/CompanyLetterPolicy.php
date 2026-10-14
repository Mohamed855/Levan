<?php

declare(strict_types=1);

namespace App\Policies\Tenants\BusinessCenter\Customer;

use App\Models\Tenants\BusinessCenter\Customers\Letter\CompanyLetter;
use App\Models\Tenants\UserMangment\User\User;

class CompanyLetterPolicy
{
    public function viewAny(User $user): bool
    {
        return tenancy()->initialized && $user->can('company_letters.view');
    }

    public function view(User $user, CompanyLetter $letter): bool
    {
        return $this->viewAny($user);
    }

    public function send(User $user, CompanyLetter $letter): bool
    {
        return tenancy()->initialized && $user->can('company_letters.send');
    }
}
