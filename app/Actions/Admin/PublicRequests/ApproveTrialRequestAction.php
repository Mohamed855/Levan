<?php

declare(strict_types=1);

namespace App\Actions\Admin\PublicRequests;

use App\Enums\Admin\PublicRequests\TrialRequestStatus;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\PublicRequests\TrialRequest;
use Illuminate\Support\Facades\DB;

final class ApproveTrialRequestAction
{
    public function execute(TrialRequest $request, AdminUser $admin): TrialRequest
    {
        return DB::transaction(function () use ($request, $admin): TrialRequest {
            abort_unless($request->status === TrialRequestStatus::PENDING, 409, 'Request already processed.');
            $request->update(['status' => TrialRequestStatus::APPROVED, 'processed_at' => now(), 'processed_by' => $admin->getKey()]);
            return $request->refresh();
        });
    }
}
