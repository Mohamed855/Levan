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
            $draftId = $request->input('_wizard_draft_id') ?: $request->attributes->get('wizard_draft_id');
            $resourceKey = $request->input('_wizard_resource_key') ?: $request->attributes->get('wizard_resource_key');
            $tenantId = $request->attributes->get('wizard_tenant_id') ?? (tenant()?->getKey() ?? '');

            if ($draftId || $resourceKey) {
                $query = WizardDraft::query()
                    ->where('guard', $guard)
                    ->where('user_id', $userId)
                    ->where('tenant_id', $tenantId);

                if ($draftId) {
                    $query->where('id', $draftId);
                }

                if ($resourceKey) {
                    $query->where('resource_key', $resourceKey);
                }

                $query->get()->each->delete();
            }
        }

        return $response;
    }

    private function wasStoredSuccessfully(Request $request, Response $response): bool
    {
        $hasDraftIdentifier = $request->filled('_wizard_draft_id')
            || $request->attributes->has('wizard_draft_id')
            || $request->filled('_wizard_resource_key')
            || $request->attributes->has('wizard_resource_key');

        if (! $request->isMethod('POST') || ! $hasDraftIdentifier) {
            return false;
        }

        if (! $response instanceof RedirectResponse || ! $response->isRedirection()) {
            return false;
        }

        return $response->getSession()?->has('success') ?? false;
    }
}
