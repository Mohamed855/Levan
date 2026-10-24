<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Settings\RotateApiTokenRequest;
use App\Services\Tenant\Shared\CentralTenantApiTokenGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class TenantApiTokenController extends Controller
{
    public function __construct(private readonly CentralTenantApiTokenGateway $tokens)
    {
        $this->middleware('can:settings.view')->only('index');
        $this->middleware('can:settings.update')->only(['rotate', 'revoke']);
    }

    public function index(): View
    {
        return view('tenant.settings.api-token', ['token' => $this->tokens->activeToken()]);
    }

    public function rotate(RotateApiTokenRequest $request): RedirectResponse
    {
        $result = $this->tokens->rotate((string) ($request->validated('name') ?: 'default'));

        return to_route('tenant.api-token.index')->with('new_api_token', $result['plain_text'])
            ->with('success', 'تم إنشاء مفتاح API جديد. انسخه الآن؛ لن يظهر مرة أخرى.');
    }

    public function revoke(Request $request): RedirectResponse
    {
        $this->authorize('settings.update');
        $this->tokens->revoke();

        return to_route('tenant.api-token.index')->with('success', 'تم إلغاء مفتاح API النشط.');
    }
}
