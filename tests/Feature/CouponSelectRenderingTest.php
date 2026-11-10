<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin\Coupons\Coupon;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

final class CouponSelectRenderingTest extends TestCase
{
    public function test_coupon_create_form_contains_discount_status_and_start_date_without_assignee(): void
    {
        $xpath = $this->renderForm(false);

        foreach (['fixed', 'percentage'] as $value) {
            self::assertSame(1, $xpath->query('//select[@name="discount_type"]/option[@value="'.$value.'"]')->length);
        }
        foreach (['0', '1'] as $value) {
            self::assertSame(1, $xpath->query('//select[@name="is_active"]/option[@value="'.$value.'"]')->length);
        }

        self::assertSame(1, $xpath->query('//input[@name="start_date"]')->length);
        self::assertSame(0, $xpath->query('//*[@name="admin_user_id"]')->length);
    }

    public function test_coupon_edit_form_preserves_saved_values_and_omits_assignee(): void
    {
        $xpath = $this->renderForm(true);

        $this->assertSelected($xpath, 'discount_type', 'percentage');

        $isActiveHidden = $xpath->query('//input[@type="hidden" and @name="is_active"]')->item(0);
        self::assertNotNull($isActiveHidden);
        self::assertSame('0', $isActiveHidden->getAttribute('value'));

        $startDateInput = $xpath->query('//input[@name="start_date"]')->item(0);
        self::assertNotNull($startDateInput);
        self::assertSame('2026-11-05', $startDateInput->getAttribute('value'));
        self::assertSame('', $startDateInput->getAttribute('min'));

        self::assertSame(0, $xpath->query('//*[@name="admin_user_id"]')->length);
    }

    public function test_old_values_take_precedence_after_validation_errors(): void
    {
        $this->withSession(['_old_input' => [
            'discount_type' => 'fixed',
            'is_active' => '1',
            'start_date' => '2026-11-12',
        ]]);

        $createXpath = $this->renderForm(false);
        $this->assertSelected($createXpath, 'discount_type', 'fixed');
        $this->assertSelected($createXpath, 'is_active', '1');

        $startDateInput = $createXpath->query('//input[@name="start_date"]')->item(0);
        self::assertNotNull($startDateInput);
        self::assertSame('2026-11-12', $startDateInput->getAttribute('value'));
        self::assertSame(0, $createXpath->query('//*[@name="admin_user_id"]')->length);
    }

    private function renderForm(bool $isEdit): DOMXPath
    {
        app('request')->setLaravelSession(app('session')->driver());
        view()->share('errors', new ViewErrorBag);
        $coupon = new Coupon([
            'name' => 'Test Coupon',
            'code' => 'TEST1234',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'max_discount' => 50,
            'start_date' => '2026-11-05',
            'is_active' => false,
        ]);
        $coupon->id = 42;

        $html = view('admin.coupons.partials._form', [
            'is_edit' => $isEdit,
            'coupon' => $coupon,
        ])->render();

        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="utf-8" ?>'.$html);

        return new DOMXPath($document);
    }

    private function assertSelected(DOMXPath $xpath, string $field, string $value): void
    {
        $option = $xpath->query('//select[@name="'.$field.'"]/option[@selected]')->item(0);
        self::assertNotNull($option);
        self::assertSame($value, $option->getAttribute('value'));
    }
}
