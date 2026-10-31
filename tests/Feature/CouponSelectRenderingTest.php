<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Coupons\Coupon;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

final class CouponSelectRenderingTest extends TestCase
{
    public function test_coupon_create_form_contains_discount_status_and_assignee_options(): void
    {
        $xpath = $this->renderForm(false);

        foreach (['fixed', 'percentage'] as $value) {
            self::assertSame(1, $xpath->query('//select[@name="discount_type"]/option[@value="'.$value.'"]')->length);
        }
        foreach (['0', '1'] as $value) {
            self::assertSame(1, $xpath->query('//select[@name="is_active"]/option[@value="'.$value.'"]')->length);
        }
        self::assertSame('Assigned Admin', trim($xpath->query('//select[@name="admin_user_id"]/option[@value="7"]')->item(0)->textContent));
    }

    public function test_coupon_edit_form_preserves_saved_select_values_including_inactive_status(): void
    {
        $xpath = $this->renderForm(true);

        $this->assertSelected($xpath, 'discount_type', 'percentage');
        $this->assertSelected($xpath, 'is_active', '0');
        $this->assertSelected($xpath, 'admin_user_id', '7');
    }

    public function test_old_select_values_take_precedence_after_validation_errors(): void
    {
        $this->withSession(['_old_input' => ['discount_type' => 'fixed', 'is_active' => '1', 'admin_user_id' => '8']]);
        $xpath = $this->renderForm(true);

        $this->assertSelected($xpath, 'discount_type', 'fixed');
        $this->assertSelected($xpath, 'is_active', '1');
        $this->assertSelected($xpath, 'admin_user_id', '8');
    }

    private function renderForm(bool $isEdit): DOMXPath
    {
        app('request')->setLaravelSession(app('session')->driver());
        view()->share('errors', new ViewErrorBag);
        $coupon = new Coupon([
            'name' => 'Test Coupon', 'code' => 'TEST1234', 'discount_type' => 'percentage',
            'discount_value' => 20, 'max_discount' => 50, 'is_active' => false, 'admin_user_id' => 7,
        ]);
        $coupon->id = 42;
        $admins = collect([7 => 'Assigned Admin', 8 => 'Other Admin'])->map(function ($name, $id) {
            $admin = new AdminUser(['name' => $name]);
            $admin->id = $id;

            return $admin;
        });
        $html = view('admin.coupons.partials._form', [
            'is_edit' => $isEdit, 'coupon' => $coupon, 'admin_users' => $admins,
        ])->render();
        $document = new DOMDocument;
        @$document->loadHTML($html);

        return new DOMXPath($document);
    }

    private function assertSelected(DOMXPath $xpath, string $field, string $value): void
    {
        $option = $xpath->query('//select[@name="'.$field.'"]/option[@selected]')->item(0);
        self::assertNotNull($option);
        self::assertSame($value, $option->getAttribute('value'));
    }
}
