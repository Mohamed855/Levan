<?php

declare(strict_types=1);

namespace App\Support\Auth;

use Illuminate\Http\Request;

final class PasswordResetSessionContext
{
    public const ADMIN = 'admin';

    public const TENANT = 'tenant';

    public static function sessionKey(string $context): string
    {
        return match ($context) {
            self::ADMIN => 'admin_password_reset_verification',
            self::TENANT => 'password_reset_verification',
            default => throw new \InvalidArgumentException("Unsupported password reset context [{$context}]."),
        };
    }

    public static function get(Request $request, string $context): mixed
    {
        return $request->session()->get(self::sessionKey($context));
    }

    public static function put(Request $request, string $context, array $payload): void
    {
        $request->session()->put(self::sessionKey($context), $payload);
    }

    public static function forget(Request $request, string $context): void
    {
        $request->session()->forget(self::sessionKey($context));
    }

    public static function matches(mixed $verification, string $context): bool
    {
        if (! is_array($verification)) {
            return false;
        }

        if ($context === self::ADMIN) {
            return ($verification['context'] ?? null) === self::ADMIN
                && ! array_key_exists('tenant_id', $verification)
                && isset($verification['user_id'], $verification['email']);
        }

        if ($context === self::TENANT) {
            $tenantId = function_exists('tenant') ? tenant('id') : null;
            $storedContext = $verification['context'] ?? self::TENANT;

            return $storedContext === self::TENANT
                && $tenantId !== null
                && isset($verification['tenant_id'])
                && (string) $verification['tenant_id'] === (string) $tenantId;
        }

        return false;
    }
}
