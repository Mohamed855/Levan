<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Admin\Coupons\CouponDiscountType;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Coupons\Coupon;
use App\Services\Shared\Coupons\CouponService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

final class CouponExpirationTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mysql'];

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_coupon_remains_not_expired_until_end_of_expiry_day(): void
    {
        $coupon = new Coupon([
            'expiry_date' => '2026-11-10',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-11-10 00:00:00'));
        self::assertFalse($coupon->isExpired());

        Carbon::setTestNow(Carbon::parse('2026-11-10 14:20:00'));
        self::assertFalse($coupon->isExpired());

        Carbon::setTestNow(Carbon::parse('2026-11-10 23:59:59'));
        self::assertFalse($coupon->isExpired());

        Carbon::setTestNow(Carbon::parse('2026-11-11 00:00:00'));
        self::assertTrue($coupon->isExpired());
    }

    public function test_coupon_without_expiry_date_is_never_expired(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-10 14:20:00'));

        $coupon = new Coupon([
            'expiry_date' => null,
        ]);

        self::assertFalse($coupon->isExpired());
    }

    public function test_coupon_is_not_active_before_start_date_and_becomes_active_at_start_of_day(): void
    {
        $coupon = new Coupon([
            'start_date' => '2026-11-12',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-11-11 23:59:59'));
        self::assertTrue($coupon->hasNotStarted());

        Carbon::setTestNow(Carbon::parse('2026-11-12 00:00:00'));
        self::assertFalse($coupon->hasNotStarted());

        Carbon::setTestNow(Carbon::parse('2026-11-12 14:20:00'));
        self::assertFalse($coupon->hasNotStarted());
    }

    public function test_coupon_service_allows_coupon_throughout_its_expiry_day_and_rejects_next_day(): void
    {
        $admin = $this->resolveAdminUser();
        $code = 'EXP'.strtoupper(Str::random(6));

        $coupon = Coupon::create([
            'name' => 'كوبون ينتهي اليوم',
            'code' => $code,
            'discount_type' => CouponDiscountType::FIXED,
            'discount_value' => 25,
            'expiry_date' => '2026-11-10',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        /** @var CouponService $couponService */
        $couponService = app(CouponService::class);

        Carbon::setTestNow(Carbon::parse('2026-11-10 14:20:00'));
        $midDayValidation = $couponService->validateAndCalculate($code, 100.0);
        self::assertTrue($midDayValidation['success'], $midDayValidation['message'] ?? '');
        self::assertSame('25.00', $midDayValidation['raw_discount']);

        $midDayProcess = $couponService->processCoupon($code, 100.0);
        self::assertArrayNotHasKey('error', $midDayProcess);
        self::assertSame($coupon->id, $midDayProcess['coupon_id']);
        self::assertSame('75.00', $midDayProcess['final_total']);

        Carbon::setTestNow(Carbon::parse('2026-11-10 23:59:59'));
        $endOfDayValidation = $couponService->validateAndCalculate($code, 100.0);
        self::assertTrue($endOfDayValidation['success'], $endOfDayValidation['message'] ?? '');

        Carbon::setTestNow(Carbon::parse('2026-11-11 00:00:00'));
        $nextDayValidation = $couponService->validateAndCalculate($code, 100.0);
        self::assertFalse($nextDayValidation['success']);
        self::assertSame('انتهت صلاحية كود الخصم', $nextDayValidation['message']);

        $nextDayProcess = $couponService->processCoupon($code, 100.0);
        self::assertSame('انتهت صلاحية كود الخصم', $nextDayProcess['error'] ?? null);
    }

    public function test_coupon_service_rejects_coupon_before_start_date_and_allows_within_range(): void
    {
        $admin = $this->resolveAdminUser();
        $code = 'STR'.strtoupper(Str::random(6));

        Coupon::create([
            'name' => 'كوبون مجدول',
            'code' => $code,
            'discount_type' => CouponDiscountType::FIXED,
            'discount_value' => 15,
            'start_date' => '2026-11-12',
            'expiry_date' => '2026-11-15',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        /** @var CouponService $couponService */
        $couponService = app(CouponService::class);

        Carbon::setTestNow(Carbon::parse('2026-11-11 23:59:59'));
        $beforeStart = $couponService->validateAndCalculate($code, 100.0);
        self::assertFalse($beforeStart['success']);
        self::assertSame('لم تبدأ صلاحية كود الخصم بعد', $beforeStart['message']);

        Carbon::setTestNow(Carbon::parse('2026-11-12 00:00:00'));
        $atStart = $couponService->validateAndCalculate($code, 100.0);
        self::assertTrue($atStart['success'], $atStart['message'] ?? '');

        Carbon::setTestNow(Carbon::parse('2026-11-15 23:59:59'));
        $atEnd = $couponService->validateAndCalculate($code, 100.0);
        self::assertTrue($atEnd['success'], $atEnd['message'] ?? '');

        Carbon::setTestNow(Carbon::parse('2026-11-16 00:00:00'));
        $afterEnd = $couponService->validateAndCalculate($code, 100.0);
        self::assertFalse($afterEnd['success']);
        self::assertSame('انتهت صلاحية كود الخصم', $afterEnd['message']);
    }

    public function test_coupon_valid_scope_respects_start_date_and_expiry_date_boundaries(): void
    {
        $admin = $this->resolveAdminUser();

        $coupon = Coupon::create([
            'name' => 'كوبون ساري اليوم',
            'code' => 'VAL'.strtoupper(Str::random(6)),
            'discount_type' => CouponDiscountType::FIXED,
            'discount_value' => 10,
            'start_date' => '2026-11-10',
            'expiry_date' => '2026-11-12',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-11-09 23:59:59'));
        self::assertFalse(Coupon::query()->valid()->whereKey($coupon->id)->exists());

        Carbon::setTestNow(Carbon::parse('2026-11-10 14:20:00'));
        self::assertTrue(Coupon::query()->valid()->whereKey($coupon->id)->exists());

        Carbon::setTestNow(Carbon::parse('2026-11-12 23:59:59'));
        self::assertTrue(Coupon::query()->valid()->whereKey($coupon->id)->exists());

        Carbon::setTestNow(Carbon::parse('2026-11-13 00:00:00'));
        self::assertFalse(Coupon::query()->valid()->whereKey($coupon->id)->exists());
    }

    private function resolveAdminUser(): AdminUser
    {
        return AdminUser::query()->first() ?? AdminUser::create([
            'name' => 'Test Admin',
            'email' => 'coupon-test-'.Str::lower(Str::random(6)).'@example.test',
            'password' => bcrypt('Secret123!'),
            'status' => 'active',
        ]);
    }
}
