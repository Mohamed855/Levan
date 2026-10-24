<?php

declare(strict_types=1);

namespace App\Actions\Admin\PublicRequests;

use App\Models\Admin\AdminUser;
use App\Models\Admin\PublicRequests\ContactRequest;
use App\Models\Admin\PublicRequests\TrialRequest;
use Illuminate\Support\Facades\DB;

final class ProcessPublicRequestAction
{
    public function reply(ContactRequest $contactRequest, string $reply, AdminUser $admin): ContactRequest
    {
        return DB::transaction(function () use ($contactRequest, $reply, $admin): ContactRequest {
            $contactRequest = ContactRequest::query()->lockForUpdate()->findOrFail($contactRequest->id);
            abort_if($contactRequest->status === 'replied', 409, 'هذا الطلب مُجاب عنه بالفعل.');

            $contactRequest->update([
                'status' => 'replied',
                'reply' => $reply,
                'replied_by' => $admin->id,
                'replied_at' => now(),
            ]);

            return $contactRequest->refresh();
        });
    }

    public function approve(TrialRequest $trialRequest, AdminUser $admin): TrialRequest
    {
        return DB::transaction(function () use ($trialRequest, $admin): TrialRequest {
            $trialRequest = TrialRequest::query()->lockForUpdate()->findOrFail($trialRequest->id);
            abort_unless($trialRequest->status === 'pending', 409, 'هذا الطلب لم يعد معلقاً.');

            $trialRequest->update([
                'status' => 'approved',
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

            return $trialRequest->refresh();
        });
    }

    public function reject(TrialRequest $trialRequest, string $reason, AdminUser $admin): TrialRequest
    {
        return DB::transaction(function () use ($trialRequest, $reason, $admin): TrialRequest {
            $trialRequest = TrialRequest::query()->lockForUpdate()->findOrFail($trialRequest->id);
            abort_unless($trialRequest->status === 'pending', 409, 'هذا الطلب لم يعد معلقاً.');

            $trialRequest->update([
                'status' => 'rejected',
                'processed_by' => $admin->id,
                'processed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            return $trialRequest->refresh();
        });
    }
}
