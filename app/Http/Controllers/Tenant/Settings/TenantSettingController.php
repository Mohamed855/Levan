<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Settings;

use App\Actions\Tenant\Settings\SaveTenantSettingsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Settings\UpdateTenantSettingsRequest;
use App\Models\Tenant\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class TenantSettingController extends Controller
{
    public function __construct(
        private readonly SaveTenantSettingsAction $saveAction
    ) {
        $this->middleware('can:settings.view')->only(['index']);
        $this->middleware('can:settings.update')->only(['update']);
    }

    public function index(): View
    {
        $settings = Setting::query()->first() ?? new Setting();

        return view('tenant.settings.index', [
            'settings' => $settings,
        ]);
    }

    public function update(UpdateTenantSettingsRequest $request): RedirectResponse
    {
        $this->saveAction->execute(
            $request->validated(),
            $request->file('image')
        );

        $tab = $request->input('active_tab', 'general');

        return redirect()->route('tenant.settings.index', ['tab' => $tab])
            ->with('success', 'تم تحديث إعدادات المنشأة بنجاح.');
    }
}
