<?php

declare(strict_types=1);

namespace App\Policies\Admin\PublicRequests;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\PublicRequests\ContactRequest;

final class ContactRequestPolicy
{
    public function viewAny(AdminUser $user): bool { return $user->can('contact_requests.view'); }
    public function view(AdminUser $user, ContactRequest $request): bool { return $user->can('contact_requests.view'); }
    public function reply(AdminUser $user, ContactRequest $request): bool { return $user->can('contact_requests.reply') && $request->status->value === 'pending'; }
}
