<?php

declare(strict_types=1);

namespace App\Support\Auth;

final class TenantPasswordResetContext
{
    public static function matchesCurrentTenant(mixed $verification): bool
    {
        return PasswordResetSessionContext::matches($verification, PasswordResetSessionContext::TENANT);
    }
}
