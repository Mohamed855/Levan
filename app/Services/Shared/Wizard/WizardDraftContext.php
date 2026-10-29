<?php

declare(strict_types=1);

namespace App\Services\Shared\Wizard;

use Illuminate\Http\Request;

final class WizardDraftContext
{
    public function guard(Request $request): string
    {
        foreach ($request->route()?->gatherMiddleware() ?? [] as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }
            foreach (['admin', 'tenant'] as $guard) {
                if ($middleware === 'auth:'.$guard || $middleware === \Illuminate\Auth\Middleware\Authenticate::class.':'.$guard) {
                    return $guard;
                }
            }
        }

        abort(403, 'The form must belong to an authenticated dashboard route.');
    }

    public function identity(Request $request): array
    {
        $guard = $this->guard($request);
        $user = $request->user($guard);
        abort_unless($user, 401);
        abort_if($guard === 'tenant' && ! tenant(), 403);

        return ['guard' => $guard, 'user_id' => (int) $user->getAuthIdentifier(), 'tenant_id' => $guard === 'tenant' ? (string) tenant()->getKey() : ''];
    }

    public function connection(Request $request): string
    {
        return $this->guard($request) === 'tenant'
            ? (config('tenancy.database.connection') ?: 'tenant')
            : (config('tenancy.database.central_connection') ?: config('database.default'));
    }
}
