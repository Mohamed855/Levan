<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class AuthenticationPagesTest extends TestCase
{
    public function test_admin_login_page_is_available(): void
    {
        $this->get(route('admin.login'))->assertOk()->assertSee('تسجيل الدخول للوحة تحكم الإدارة المركزية');
    }

    public function test_tenant_login_page_is_available(): void
    {
        $this->get(route('tenant.login'))->assertOk()->assertSee('سجل الدخول بحساب الشركة لاستخدام المنصة');
    }
}