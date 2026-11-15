<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Faq;
use App\Models\Tenants\UserMangment\User\User as TenantUser;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

final class ActionButtonsNoOnclickTest extends TestCase
{
    public function test_faq_action_uses_shared_delete_button_without_inline_onclick(): void
    {
        $user = (new AdminUser)->forceFill(['id' => 10]);
        $this->actingAs($user, 'admin');
        Gate::before(fn () => true);
        $row = (new Faq)->forceFill(['id' => 42]);
        $route = 'admin.faqs';

        $html = view('admin.faqs.action', compact('row', 'route'))->render();

        self::assertStringContainsString('js-delete-btn', $html);
        self::assertStringContainsString('data-delete-url="'.route($route.'.destroy', 42).'"', $html);
        self::assertStringNotContainsString('onclick=', $html);
    }

    public function test_bill_archive_action_uses_delegated_buttons_without_inline_onclick(): void
    {
        $user = (new AdminUser)->forceFill(['id' => 10]);
        $this->actingAs($user, 'admin');
        Gate::before(fn () => true);
        $id = 42;

        $html = view('admin.archive.bill.action', compact('id'))->render();

        self::assertStringContainsString('js-restore-btn', $html);
        self::assertStringContainsString('js-force-btn', $html);
        self::assertStringContainsString('data-id="42"', $html);
        self::assertStringNotContainsString('onclick=', $html);
        self::assertStringContainsString('btn-label-primary', $html);
        self::assertStringNotContainsString('btn-label-danger', $html);
    }

    public function test_tenant_user_archive_action_uses_delegated_buttons_without_inline_onclick(): void
    {
        $user = (new TenantUser)->forceFill(['id' => 10]);
        $this->actingAs($user, 'tenant');
        Gate::before(fn () => true);
        $id = 42;

        $html = view('tenant.archive.users.action', compact('id'))->render();

        self::assertStringContainsString('js-restore-btn', $html);
        self::assertStringContainsString('js-force-btn', $html);
        self::assertStringContainsString('data-id="42"', $html);
        self::assertStringNotContainsString('onclick=', $html);
        self::assertStringContainsString('btn-label-primary', $html);
        self::assertStringNotContainsString('btn-label-danger', $html);
    }

    public function test_shared_restore_and_force_delete_action_components_render_consistent_label_primary_styling(): void
    {
        $restoreHtml = view('components.shared.actions.restore', [
            'id' => 77,
            'url' => 'http://example.com/restore/77',
            'title' => 'استرجاع السجل',
        ])->render();

        self::assertStringContainsString('btn-icon btn-label-primary', $restoreHtml);
        self::assertStringContainsString('js-restore-btn', $restoreHtml);
        self::assertStringContainsString('js-restore', $restoreHtml);
        self::assertStringContainsString('data-id="77"', $restoreHtml);
        self::assertStringContainsString('data-url="http://example.com/restore/77"', $restoreHtml);
        self::assertStringNotContainsString('btn-primary ', $restoreHtml);

        $forceDeleteHtml = view('components.shared.actions.force-delete', [
            'id' => 77,
            'url' => 'http://example.com/force-delete/77',
            'title' => 'حذف السجل نهائياً',
        ])->render();

        self::assertStringContainsString('btn-icon btn-label-primary', $forceDeleteHtml);
        self::assertStringContainsString('js-force-delete-btn', $forceDeleteHtml);
        self::assertStringContainsString('js-force-btn', $forceDeleteHtml);
        self::assertStringContainsString('js-force-delete', $forceDeleteHtml);
        self::assertStringContainsString('data-id="77"', $forceDeleteHtml);
        self::assertStringContainsString('data-url="http://example.com/force-delete/77"', $forceDeleteHtml);
        self::assertStringNotContainsString('btn-label-danger', $forceDeleteHtml);
    }

    public function test_tenant_notifications_action_uses_delegated_buttons_without_inline_onclick(): void
    {
        $user = (new TenantUser)->forceFill(['id' => 10]);
        $this->actingAs($user, 'tenant');
        $id = 'notif-99';

        $html = view('tenant.notifications.action', compact('id'))->render();

        self::assertStringContainsString('js-mark-read-btn', $html);
        self::assertStringContainsString('js-delete-notification-btn', $html);
        self::assertStringContainsString('data-id="notif-99"', $html);
        self::assertStringNotContainsString('onclick=', $html);
    }
}
