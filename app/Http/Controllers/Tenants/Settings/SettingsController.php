<?php

namespace App\Http\Controllers\Tenants\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Settings\MainSettingsRequest;
use App\Models\Tenants\Settings\Settings;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:system.settings')->only(['index', 'update']);
    }

    /*
    |--------------------------------------------------------------------------
    | Display the settings form.
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $settings = Settings::firstOrNew();
        return view('tenants.settings.settings', compact('settings'));
    }

    /*
    |--------------------------------------------------------------------------
    | Update the settings.
    |--------------------------------------------------------------------------
    */
    public function update(MainSettingsRequest $request)
    {
        // تخزين التبويبة النشطة في الجلسة
        if ($request->has('active_tab')) {
            session(['active_tab' => $request->input('active_tab')]);
        }

        // التحقق من صحة البيانات
        $validated = $request->validated();

        // إما جلب السجل الأول أو إنشاء جديد
        $settings = Settings::first();
        if (!$settings) {
            $settings = new Settings();
        }

        if ($request->input('active_tab') === '#generalSettings') {
            $settings->office_name = $validated['office_name'];
            $settings->logo_text = $validated['logo_text'];
            // تحديث وضع الصيانة
            $settings->maintenance_mode = $request->has('maintenance_mode');

            $settings->breadcrumb_visibility = $request->has('breadcrumb_visibility');


            if ($request->hasFile('image')) {
                $settings->image = $request->file('image')->store('image', 'tenant_public');
            }
        }

        // إعدادات طباعة
        if ($request->input('active_tab') === '#printSettings') {
            foreach ([
                'horizontal_header_image',
                'vertical_header_image',
                'horizontal_footer_image',
                'vertical_footer_image',
                'signature',
            ] as $field) {
                if ($request->hasFile($field)) {
                    $settings->{$field} = $request->file($field)->store('settings/print', 'tenant_public');
                }
            }
        }

        // if ($request->has('twilio_account_sid')) {
        //     // إعدادات WhatsApp (Twilio)
        //     $settings->twilio_account_sid   = $validated['twilio_account_sid']   ?? $settings->twilio_account_sid;
        //     $settings->twilio_auth_token    = $validated['twilio_auth_token']    ?? $settings->twilio_auth_token;
        //     $settings->twilio_whatsapp_from = $validated['twilio_whatsapp_from'] ?? $settings->twilio_whatsapp_from;
        //     $settings->whatsapp_enabled     = $request->has('whatsapp_enabled');
        // }

        if ($request->input('active_tab') === '#SmsSettings') {
            $settings->sms_provider     = $validated['sms_provider']      ?? $settings->sms_provider;
            $settings->sms_api_key      = $validated['sms_api_key']       ?? $settings->sms_api_key;
            $settings->sms_api_secret   = $validated['sms_api_secret']    ?? $settings->sms_api_secret;
            $settings->sms_api_endpoint = $validated['sms_api_endpoint']  ?? $settings->sms_api_endpoint;
            $settings->sms_sender_id    = $validated['sms_sender_id']     ?? $settings->sms_sender_id;
            $settings->sms_from_number  = $validated['sms_from_number']   ?? $settings->sms_from_number;
            $settings->sms_callback_url = $validated['sms_callback_url']  ?? $settings->sms_callback_url;
            $smsEnabled = $request->has('sms_enabled');
            $settings->sms_enabled = $smsEnabled ? 1 : 0;
            $settings->sms_auto_reminders_enabled = ($smsEnabled && $request->has('sms_auto_reminders_enabled')) ? 1 : 0;
            $settings->sms_rate_limit   = $validated['sms_rate_limit']    ?? $settings->sms_rate_limit;
        }

        if ($request->input('active_tab') === '#twoFactorSettings') {
            $settings->force_two_factor_auth = $request->has('force_two_factor_auth');
        }

        if ($request->has('support_emails')) {
            $settings->support_emails = $validated['support_emails'] ?? null;
        }

        $settings->save();
        Cache::forget('app_settings');

        return redirect(tenant_route_name('settings.index'))->withFragment($request->input('active_tab', null))->with('success', 'تم تحديث الإعدادات بنجاح');
    }


    /*
    |--------------------------------------------------------------------------
    | عرض مركز الإعدادات التفصيلية
    |--------------------------------------------------------------------------
    */
    public function center()
    {
        return view('tenants.settings.center');
    }
}
