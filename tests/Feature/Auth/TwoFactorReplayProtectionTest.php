<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Laravel\Fortify\TwoFactorAuthenticationProvider;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

final class TwoFactorReplayProtectionTest extends TestCase
{
    private function engine(): Google2FA
    {
        $engine = new class extends Google2FA {
            public int $timestamp = 60000000;

            public function getTimestamp(): int
            {
                return $this->timestamp;
            }
        };
        $this->app->instance(Google2FA::class, $engine);

        return $engine;
    }

    public function test_next_window_code_remains_consumed_for_its_entire_acceptance_window(): void
    {
        $engine = $this->engine();
        $provider = app(TwoFactorAuthenticationProvider::class);
        $secret = $engine->generateSecretKey();
        $code = $engine->oathTotp($secret, $engine->getTimestamp() + 1);

        self::assertTrue($provider->verify($secret, $code));
        self::assertFalse($provider->verify($secret, $code));

        $this->travel(61)->seconds();
        $engine->timestamp += 2;
        self::assertFalse($provider->verify($secret, $code));
        $this->travelBack();
    }

    public function test_same_numeric_code_on_different_secrets_does_not_block_another_account(): void
    {
        $engine = $this->engine();
        $codes = [];
        // Real HMAC/TOTP; find a deterministic birthday collision without mocking verification.
        for ($i = 1; $i <= 10000; $i++) {
            $secret = strtr(strtoupper(substr(hash('sha256', (string) $i), 0, 16)), '0189', 'GHIJ');
            $code = $engine->oathTotp($secret, $engine->getTimestamp());
            if (isset($codes[$code])) {
                $provider = app(TwoFactorAuthenticationProvider::class);
                self::assertTrue($provider->verify($codes[$code], $code));
                self::assertTrue($provider->verify($secret, $code));
                self::assertFalse($provider->verify($codes[$code], $code));
                self::assertFalse($provider->verify($secret, $code));

                return;
            }
            $codes[$code] = $secret;
        }

        self::fail('Deterministic fixture must contain a real numeric TOTP collision.');
    }
}
