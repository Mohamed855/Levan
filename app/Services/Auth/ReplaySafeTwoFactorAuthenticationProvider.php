<?php

declare(strict_types=1);

namespace App\Services\Auth;

use Illuminate\Contracts\Cache\Repository;
use Laravel\Fortify\TwoFactorAuthenticationProvider;
use PragmaRX\Google2FA\Google2FA;

final class ReplaySafeTwoFactorAuthenticationProvider extends TwoFactorAuthenticationProvider
{
    public function __construct(Google2FA $engine, Repository $cache)
    {
        parent::__construct($engine, $cache);
    }

    public function verify($secret, $code): bool
    {
        if (is_int($window = config('fortify-options.two-factor-authentication.window'))) {
            $this->engine->setWindow($window);
        }

        // A non-null lower bound makes Google2FA return the matched step, including T+1.
        $timestamp = $this->engine->verifyKeyNewer($secret, $code, 0);
        if ($timestamp === false) {
            return false;
        }

        $key = 'fortify.2fa_used.'.hash('sha256', $secret).'.'.$timestamp;
        // Keep even a future-step code consumed until it leaves the entire acceptance window.
        $ttl = (2 * $this->engine->getWindow() + 1) * $this->engine->getKeyRegeneration();

        return $this->cache->add($key, true, $ttl);
    }
}
