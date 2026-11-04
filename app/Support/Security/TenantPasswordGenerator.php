<?php

declare(strict_types=1);

namespace App\Support\Security;

use Illuminate\Support\Str;

final class TenantPasswordGenerator
{
    /**
     * Generate a secure random password satisfying the system complexity policy.
     */
    public static function generate(int $length = 12): string
    {
        do {
            $password = Str::password($length);
        } while (
            ! preg_match('/[A-Z]/', $password)
            || ! preg_match('/[a-z]/', $password)
            || ! preg_match('/\pN/u', $password)
            || ! preg_match('/\p{Z}|\p{S}|\p{P}/u', $password)
        );

        return $password;
    }
}

