<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Shared\WizardDraft;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DiscardCompletedWizardDraft
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->wasStoredSuccessfully($request, $response)) {
            return $response;
        }

        $guard = auth('admin')->check() ? 'admin' : 'tenant';
        $userId = auth($guard)->id();

        if ($userId) {
            WizardDraft::query()->where([
                'id' => $request->attributes->get('wizard_draft_id'),
                'resource_key' => $request->attributes->get('wizard_resource_key'),
                'tenant_id' => $request->attributes->get('wizard_tenant_id'),
                'guard' => $guard,
                'user_id' => $userId,
            ])->delete();
        }

        return $response;
    }

    private function wasStoredSuccessfully(Request $request, Response $response): bool
    {
        if (! $request->isMethod('POST') || ! $request->attributes->has('wizard_draft_id')) {
            return false;
        }

        if (! $response instanceof RedirectResponse || ! $response->isRedirection()) {
            return false;
        }

        $targetPath = parse_url($response->getTargetUrl(), PHP_URL_PATH);

        return is_string($targetPath) && rtrim($targetPath, '/') === rtrim($request->getPathInfo(), '/');
    }
}
