<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

final class CentralCouponsGoldenPatternTest extends TestCase
{
    public function test_coupon_forms_use_shared_form_partial_and_draft_contract(): void
    {
        $create = file_get_contents(resource_path('views/admin/coupons/create.blade.php'));
        $edit = file_get_contents(resource_path('views/admin/coupons/edit.blade.php'));
        $formPartial = file_get_contents(resource_path('views/admin/coupons/partials/_form.blade.php'));

        self::assertStringContainsString('admin.coupons.partials._form', $create);
        self::assertStringContainsString('admin.coupons.partials._form', $edit);
        self::assertStringContainsString("'is_edit' => false", $create);
        self::assertStringContainsString("'is_edit' => true", $edit);

        self::assertStringContainsString('data-form-validate', $formPartial);
        self::assertStringContainsString('data-draft-resource-key="admin.coupon-create"', $formPartial);
        self::assertStringContainsString('<div class="d-flex gap-2">', $formPartial);
        self::assertStringContainsString('<x-form.wizard.draft-button', $formPartial);
        self::assertStringContainsString('<x-form.wizard.save-button', $formPartial);
        self::assertStringContainsString('<x-form.wizard.previous-button', $formPartial);
    }
}
