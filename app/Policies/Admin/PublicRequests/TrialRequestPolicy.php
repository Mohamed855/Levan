<?php

declare(strict_types=1);

namespace App\Policies\Admin\PublicRequests;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\PublicRequests\TrialRequest;

final class TrialRequestPolicy
{
    public function viewAny(AdminUser $user): bool { return $user->can('trial_requests.view'); }
    public function view(AdminUser $user, TrialRequest $request): bool { return $user->can('trial_requests.view'); }
    public function approve(AdminUser $user, TrialRequest $request): bool { return $user->can('trial_requests.approve') && $request->status->value === 'pending'; }
    public function reject(AdminUser $user, TrialRequest $request): bool { return $user->can('trial_requests.reject') && $request->status->value === 'pending'; }
}
