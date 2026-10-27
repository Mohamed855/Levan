<?php

declare(strict_types=1);

namespace App\Actions\Admin\PublicRequests;

use App\Models\Admin\PublicRequests\TrialRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateTrialRequestAction
{
    public function execute(array $data): TrialRequest
    {
        $request = DB::connection('mysql')->transaction(function () use ($data): TrialRequest {
            $existing = TrialRequest::where('submission_token', $data['submission_token'])->first();
            if ($existing) {
                return $existing;
            }
            if (TrialRequest::where('status', 'pending')->whereNotNull('email_verified_at')->where(function ($query) use ($data) {
                $query->where('customer_email', $data['billing_email'])->orWhere('commercial_number', $data['billing_commercial_number']);
            })->exists()) {
                throw ValidationException::withMessages(['billing_email' => 'يوجد طلب تجربة قيد المراجعة لهذه البيانات.']);
            }
            $attributes = [
                'customer_name' => $data['billing_name'], 'customer_email' => $data['billing_email'],
                'customer_phone' => $data['billing_phone'], 'company_name' => $data['billing_company_name'],
                'commercial_number' => $data['billing_commercial_number'], 'tax_number' => $data['billing_tax_number'],
                'city_id' => $data['billing_city_id'], 'address' => $data['billing_address'],
                'password_hash' => $data['billing_password_hash'], 'email_verified_at' => $data['email_verified_at'],
                'submission_token' => $data['submission_token'], 'status' => 'pending',
            ];
            $legacy = TrialRequest::where('customer_email', $data['billing_email'])->where('status', 'pending')->whereNull('email_verified_at')->lockForUpdate()->first();
            if ($legacy) {
                $legacy->update($attributes);

                return $legacy->refresh();
            }

            return TrialRequest::create($attributes);
        });

        cache()->forget('admin:pending_trial_requests_count');

        \App\Services\Shared\Mail\SystemMailService::sendTrialRequestReceived(
            $request->customer_email,
            $request->customer_name,
            $request->company_name
        );
        \App\Services\Shared\Mail\SystemMailService::sendTrialRequestAdminAlert($request);

        return $request;
    }
}
