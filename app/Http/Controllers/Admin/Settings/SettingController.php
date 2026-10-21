<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Settings;

use App\Actions\Admin\Settings\UpdateSettingsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\UpdateSettingsRequest;
use App\Models\Admin\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class SettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:settings.view')->only('index');
        $this->middleware('can:settings.update')->only('update');
    }

    public function index(): View
    {
        $general = Setting::get('general', []);
        $etmam = Setting::get('etmam', []);
        $microsoft = Setting::get('microsoft', []);
        $security = Setting::get('security', []);

        return view('admin.settings.index', compact('general', 'etmam', 'microsoft', 'security'));
    }

    public function update(UpdateSettingsRequest $request, UpdateSettingsAction $action): RedirectResponse
    {
        $action->execute($request->validated());

        return back()->with('success', 'تم حفظ إعدادات النظام بنجاح.');
    }
}
