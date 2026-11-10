<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DataTables\Admin\Coupons\CouponDataTable;
use App\Enums\Admin\Coupons\CouponDiscountType;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Coupons\Coupon;
use App\Models\Admin\Tenants\Tenant;
use App\Services\Admin\AdminMangment\Users\UserService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class CouponManagementWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mysql'];

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_creating_and_updating_coupon_saves_creator_in_admin_user_id_and_handles_start_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-10 14:20:00'));

        $creator = $this->createSuperAdmin('Creator Admin');
        $otherAdmin = $this->createSuperAdmin('Editor Admin');
        $code = 'MGT'.strtoupper(Str::random(6));

        $this->actingAs($creator, 'admin')
            ->post(route('admin.coupons.store'), [
                'name' => 'كوبون تاريخ بداية',
                'code' => $code,
                'discount_type' => 'fixed',
                'discount_value' => 30,
                'start_date' => '2026-11-10',
                'expiry_date' => '2026-11-20',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.coupons.index'));

        $coupon = Coupon::query()->where('code', $code)->firstOrFail();
        self::assertSame($creator->id, $coupon->admin_user_id);
        self::assertSame($creator->id, $coupon->created_by);
        self::assertSame('2026-11-10', $coupon->start_date?->format('Y-m-d'));
        self::assertSame('2026-11-20', $coupon->expiry_date?->format('Y-m-d'));

        // Editing an already-started coupon later preserves past start_date and original admin_user_id
        Carbon::setTestNow(Carbon::parse('2026-11-15 10:00:00'));
        $this->actingAs($otherAdmin, 'admin')
            ->put(route('admin.coupons.update', $coupon->id), [
                'name' => 'كوبون محدث',
                'code' => $code,
                'discount_type' => 'fixed',
                'discount_value' => 40,
                'start_date' => '2026-11-10',
                'expiry_date' => '2026-11-25',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.coupons.index'));

        $coupon->refresh();
        self::assertSame(40.0, (float) $coupon->discount_value);
        self::assertSame($creator->id, $coupon->admin_user_id);
        self::assertSame($otherAdmin->id, $coupon->updated_by);
        self::assertSame('2026-11-10', $coupon->start_date?->format('Y-m-d'));
    }

    public function test_coupon_validation_rejects_expiry_date_earlier_than_start_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-10 14:20:00'));

        $admin = $this->createSuperAdmin('Validation Admin');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.coupons.store'), [
                'name' => 'كوبون تواريخ خاطئة',
                'code' => 'BAD'.strtoupper(Str::random(6)),
                'discount_type' => 'fixed',
                'discount_value' => 10,
                'start_date' => '2026-11-15',
                'expiry_date' => '2026-11-12',
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('expiry_date');
    }

    public function test_coupon_datatable_exposes_created_by_and_start_date_columns_with_creator_fallback(): void
    {
        $dataTable = app(CouponDataTable::class);
        $columnsMethod = new \ReflectionMethod($dataTable, 'getColumns');
        $columnsMethod->setAccessible(true);
        $columns = $columnsMethod->invoke($dataTable);

        $createdByColumn = collect($columns)->firstWhere('data', 'created_by');
        $startDateColumn = collect($columns)->firstWhere('data', 'start_date');

        self::assertNotNull($createdByColumn);
        self::assertSame('أنشئ بواسطة', $createdByColumn['title']);
        self::assertNotNull($startDateColumn);
        self::assertSame('تاريخ البداية', $startDateColumn['title']);

        $fake = new class
        {
            public array $edited = [];

            public function addColumn(string $name, $cb): self
            {
                return $this;
            }

            public function editColumn(string $name, $cb): self
            {
                $this->edited[$name] = $cb;

                return $this;
            }
        };

        $addCustomColumns = new \ReflectionMethod($dataTable, 'addCustomColumns');
        $addCustomColumns->setAccessible(true);
        $addCustomColumns->invoke($dataTable, $fake);

        $creator = new AdminUser(['name' => 'أحمد المنشئ', 'email' => 'creator@example.test']);
        $couponWithAdminUser = new Coupon([
            'discount_type' => CouponDiscountType::FIXED,
            'start_date' => '2026-11-10',
        ]);
        $couponWithAdminUser->setRelation('admin_user', $creator);

        $couponLegacyFallback = new Coupon([
            'discount_type' => CouponDiscountType::FIXED,
            'start_date' => null,
        ]);
        $couponLegacyFallback->setRelation('admin_user', null);
        $couponLegacyFallback->setRelation('creator', $creator);

        self::assertSame('أحمد المنشئ', ($fake->edited['created_by'])($couponWithAdminUser));
        self::assertSame('أحمد المنشئ', ($fake->edited['created_by'])($couponLegacyFallback));
        self::assertSame('10-11-2026', ($fake->edited['start_date'])($couponWithAdminUser));
        self::assertSame('فوري', ($fake->edited['start_date'])($couponLegacyFallback));
    }

    public function test_coupon_details_view_shows_created_by_label_and_start_date(): void
    {
        $admin = $this->createSuperAdmin('سارة المنشئة');
        $coupon = Coupon::create([
            'name' => 'كوبون التفاصيل',
            'code' => 'DTL'.strtoupper(Str::random(6)),
            'discount_type' => CouponDiscountType::FIXED,
            'discount_value' => 20,
            'start_date' => '2026-11-10',
            'expiry_date' => '2026-11-20',
            'is_active' => true,
            'admin_user_id' => $admin->id,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.coupons.details', $coupon->id));

        $response->assertOk()
            ->assertSee('أنشئ بواسطة')
            ->assertSee('سارة المنشئة')
            ->assertSee('10-11-2026')
            ->assertDontSee('المستخدم الإداري')
            ->assertDontSee('كوبون عام')
            ->assertDontSee('غير مسند لمستخدم إداري محدد');
    }

    public function test_coupon_details_tenants_table_renders_only_index_style_eye_action_button(): void
    {
        $admin = $this->createSuperAdmin('أدمن التفاصيل');
        $coupon = new Coupon([
            'name' => 'كوبون الشركات',
            'code' => 'TEN'.strtoupper(Str::random(5)),
            'discount_type' => CouponDiscountType::FIXED,
            'discount_value' => 25,
            'is_active' => true,
        ]);
        $coupon->id = 999;
        $coupon->setRelation('admin_user', $admin);

        $bill = new Bill([
            'amount' => 150,
            'created_at' => Carbon::parse('2026-11-10 12:00:00'),
        ]);

        $tenant = new Tenant([
            'id' => 'tenant-eye-test',
            'company_name' => 'شركة اختبار الإجراءات',
            'commercial_number' => '1010123456',
            'primary_contact_name' => 'أحمد',
            'primary_contact_email' => 'ahmed@example.test',
        ]);
        $tenant->setRelation('bills', collect([$bill]));
        $tenant->setRelation('owner', null);

        $this->actingAs($admin, 'admin');

        $html = view('admin.coupons.details', [
            'couponDetails' => $coupon,
            'tenants' => collect([$tenant]),
        ])->render();

        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        $xpath = new \DOMXPath($document);

        $actionLinks = $xpath->query('//table//tbody/tr[1]/td[last()]//a');
        self::assertSame(1, $actionLinks->length);

        $eyeLink = $actionLinks->item(0);
        self::assertNotNull($eyeLink);
        self::assertSame(route('admin.clients.show', $tenant->id), $eyeLink->getAttribute('href'));
        self::assertStringContainsString('btn-icon', $eyeLink->getAttribute('class'));
        self::assertStringContainsString('btn-label-primary', $eyeLink->getAttribute('class'));
        self::assertSame(1, $xpath->query('.//i[contains(@class, "ti-eye")]', $eyeLink)->length);
        self::assertStringNotContainsString('ti-external-link', $html);
    }

    public function test_admin_user_show_and_profile_data_do_not_expose_assigned_coupons(): void
    {
        $admin = $this->createSuperAdmin('أدمن العرض');
        Coupon::create([
            'name' => 'كوبون لا يظهر بالمستخدم',
            'code' => 'USR'.strtoupper(Str::random(6)),
            'discount_type' => CouponDiscountType::FIXED,
            'discount_value' => 10,
            'is_active' => true,
            'admin_user_id' => $admin->id,
            'created_by' => $admin->id,
        ]);

        $profileData = app(UserService::class)->getProfileData($admin);
        self::assertArrayNotHasKey('coupons', $profileData);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.users.profile', $admin->id))
            ->assertOk()
            ->assertDontSee('الكوبونات المُسندة')
            ->assertDontSee('كوبون لا يظهر بالمستخدم');
    }

    private function createSuperAdmin(string $name): AdminUser
    {
        $role = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'admin']);
        $user = AdminUser::create([
            'name' => $name,
            'email' => 'admin-'.Str::lower(Str::random(8)).'@example.test',
            'phone' => '05'.random_int(10000000, 99999999),
            'password' => bcrypt('Secret123!'),
            'status' => 'active',
            'is_super_admin' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }
}
