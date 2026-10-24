<?php

declare(strict_types=1);

namespace App\Policies\Admin;

use App\Models\Admin\AdminUser;
use App\Models\Admin\Faq;

final class FaqPolicy
{
    public function view(AdminUser $user, Faq $faq): bool { return $user->hasPermissionTo('faq.view'); }
    public function create(AdminUser $user): bool { return $user->hasPermissionTo('faq.create'); }
    public function update(AdminUser $user, Faq $faq): bool { return $user->hasPermissionTo('faq.update'); }
    public function delete(AdminUser $user, Faq $faq): bool { return $user->hasPermissionTo('faq.update'); }
}
