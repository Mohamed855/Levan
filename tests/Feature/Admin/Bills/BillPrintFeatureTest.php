<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Bills;

use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Billing\BillType;
use App\Enums\Admin\Billing\PaymentMethod;
use App\Helpers\Admin\Settings\AdminSettingsHelper;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Settings\Settings;
use App\Services\Shared\Bills\BillService;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class BillPrintFeatureTest extends TestCase
{
    private AdminUser $adminUser;

    private Bill $bill;

    private ?Settings $settings;

    protected function setUp(): void
    {
        parent::setUp();

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        $superAdminRole = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'admin']);
        $this->adminUser = AdminUser::query()->first() ?? AdminUser::create([
            'name' => 'Admin Test',
            'email' => 'admin_test_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
        ]);

        if (! $this->adminUser->hasRole('SuperAdmin')) {
            $this->adminUser->assignRole($superAdminRole);
        }

        $this->settings = Settings::first() ?? Settings::create([
            'office_name' => 'لوت LOT',
            'logo_text' => 'لوت LOT',
            'image' => 'default-office.png',
            'office_address' => 'الرياض',
            'phone_number' => '0500000000',
            'email' => 'info@lot.sa',
        ]);

        $this->bill = Bill::create([
            'bill_number' => 'INV-TEST-'.uniqid(),
            'amount' => 1150.00,
            'original_amount' => 1150.00,
            'discount_amount' => 0.00,
            'type' => BillType::NEW_SUBSCRIPTION,
            'payment_method' => PaymentMethod::BANK_TRANSFER,
            'status' => BillStatus::PAID,
            'due_date' => now()->addDays(7),
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->bill)) {
            $this->bill->forceDelete();
        }

        parent::tearDown();
    }

    public function test_settings_logo_url_resolves_fallback_when_image_is_default_or_null(): void
    {
        $settings = new Settings(['image' => 'default-office.png']);
        self::assertStringContainsString('static/brand/logos/lot/trans-profile.png', $settings->logo_url);

        $settingsNull = new Settings(['image' => null]);
        self::assertStringContainsString('static/brand/logos/lot/trans-profile.png', $settingsNull->logo_url);

        self::assertStringContainsString('static/brand/logos/lot/trans-profile.png', AdminSettingsHelper::getLogoUrl());
    }

    public function test_settings_logo_url_resolves_uploaded_image_when_present_in_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('image/custom_logo.png', 'fake_image_content');

        $settings = new Settings(['image' => 'image/custom_logo.png']);
        self::assertStringContainsString('storage/image/custom_logo.png', $settings->logo_url);
    }

    public function test_admin_bill_print_view_renders_platform_logo_and_removes_inline_onclick(): void
    {
        $this->actingAs($this->adminUser, 'admin');

        $response = $this->get(route('admin.bills.print', $this->bill->id));
        $response->assertOk();

        $content = $response->getContent();

        // 1. Platform logo must be present
        self::assertStringContainsString('company-logo', $content);
        self::assertStringContainsString('trans-profile.png', $content);

        // 2. Inline onclick must NOT be present on the print button (prevents CSP violation)
        self::assertStringNotContainsString('onclick="window.print()"', $content);
        self::assertStringNotContainsString('onclick=', $content);

        // 3. Button ID and script listener must be present
        self::assertStringContainsString('id="btn-print-invoice"', $content);
        self::assertStringContainsString('btn-print-invoice', $content);
        self::assertStringContainsString('window.print()', $content);
    }

    public function test_tenant_bill_print_view_renders_platform_logo_and_removes_inline_onclick(): void
    {
        $viewData = (new BillService)->getPrintData($this->bill);
        $viewData['isAdmin'] = false;
        $viewData['backUrl'] = '#';

        $html = view('admin.bills.print', $viewData)->render();

        self::assertStringContainsString('company-logo', $html);
        self::assertStringContainsString('trans-profile.png', $html);
        self::assertStringNotContainsString('onclick=', $html);
        self::assertStringContainsString('id="btn-print-invoice"', $html);
        self::assertStringContainsString('window.print()', $html);
    }
}
