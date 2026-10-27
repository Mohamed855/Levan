<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Actions\Admin\PublicRequests\CreateTrialRequestAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreTrialRequest;
use App\Http\Requests\Site\VerifyTrialOtpRequest;
use App\Models\Admin\City;
use App\Services\Shared\Registration\RegistrationOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TrialController extends Controller
{
    public function index(Request $request)
    {
        $verification = $request->session()->get('trial_registration', []);
        if (($verification['expires_at'] ?? 0) < now()->timestamp) {
            $request->session()->forget('trial_registration');
            $verification = [];
        }

        return view('web.trial', [
            'cities' => City::all(), 'otpStep' => isset($verification['otp_hash']),
            'otpExpiresAt' => $verification['expires_at'] ?? null,
            'checkoutData' => collect($verification)->only(['billing_company_name', 'billing_commercial_number', 'billing_tax_number', 'billing_city_id', 'billing_address', 'billing_name', 'billing_email', 'billing_phone'])->all(),
        ]);
    }

    public function demoRequest(StoreTrialRequest $request, RegistrationOtpService $otp)
    {
        $data = $request->validated();
        $data['billing_password_hash'] = Hash::make($data['billing_password']);
        unset($data['billing_password'], $data['billing_password_confirmation']);
        $data['otp_hash'] = $otp->issue($data['billing_email']);
        $data['expires_at'] = now()->addMinutes(2)->timestamp;
        $data['attempts'] = 0;
        $data['submission_token'] = (string) Str::uuid();
        $request->session()->put('trial_registration', $data);

        return redirect()->route('site.trial');
    }

    public function edit(Request $request)
    {
        $registration = $request->session()->get('trial_registration', []);
        $safe = collect($registration)->only(['billing_company_name', 'billing_commercial_number', 'billing_tax_number', 'billing_city_id', 'billing_address', 'billing_name', 'billing_email', 'billing_phone'])->all();
        unset($registration['otp_hash'], $registration['attempts'], $registration['submission_token']);
        $registration['expires_at'] = now()->addHour()->timestamp;
        $request->session()->put('trial_registration', $registration);

        return redirect()->route('site.trial')->withInput($safe);
    }

    public function verify(VerifyTrialOtpRequest $request, CreateTrialRequestAction $create)
    {
        $data = $request->session()->get('trial_registration');
        if (! is_array($data) || ! isset($data['otp_hash']) || ($data['expires_at'] ?? 0) < now()->timestamp || ($data['attempts'] ?? 0) >= 5) {
            $request->session()->forget('trial_registration');

            return redirect()->route('site.trial')->withErrors(['otp' => 'انتهت صلاحية رمز التحقق. يرجى إعادة الطلب.']);
        }
        if (! hash_equals($data['otp_hash'], hash('sha256', (string) $request->validated('otp')))) {
            $data['attempts']++;
            $request->session()->put('trial_registration', $data);

            return back()->withErrors(['otp' => 'رمز التحقق غير صحيح.']);
        }
        $create->execute($data + ['email_verified_at' => now()]);
        $request->session()->forget('trial_registration');

        return redirect()->route('site.index')->with('success', 'تم إرسال طلب التجربة بنجاح، وطلبك الآن قيد مراجعة الإدارة.');
    }

    public function submitted()
    {
        return view('web.trial-submitted');
    }
}
