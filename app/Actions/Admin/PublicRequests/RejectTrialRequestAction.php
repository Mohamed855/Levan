<?php

declare(strict_types=1);

namespace App\Actions\Admin\PublicRequests;

use App\Enums\Admin\PublicRequests\TrialRequestStatus;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\PublicRequests\TrialRequest;
use Illuminate\Support\Facades\DB;

final class RejectTrialRequestAction
{
    public function execute(TrialRequest $request, string $reason, AdminUser $admin): TrialRequest
    {
        $trialRequest = DB::connection('mysql')->transaction(function () use ($request, $reason, $admin): TrialRequest {
            $request = TrialRequest::lockForUpdate()->findOrFail($request->id);
            abort_unless($request->status === TrialRequestStatus::PENDING, 409, 'Request already processed.');
            abort_if($request->provisioning_status === 'processing', 409, 'يجري إنشاء حساب الطلب حاليًا.');
            $request->update(['status' => TrialRequestStatus::REJECTED, 'rejection_reason' => $reason, 'processed_at' => now(), 'processed_by' => $admin->getKey(), 'password_hash' => null]);

            return $request->refresh();
        });

        cache()->forget('admin:pending_trial_requests_count');

        return $trialRequest;
    }
}
