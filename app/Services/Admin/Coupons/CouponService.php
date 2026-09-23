<?php

namespace App\Services\Admin\Coupons;




use App\Enums\Admin\Coupons\CouponApplicableTo;
use App\Models\Admin\Coupons\Coupon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Facades\Tenancy;

class CouponService
{

    // Create Coupon
    public function create(array $data): Coupon
    {
        return DB::transaction(function () use ($data) {

            return Coupon::create([
                ...$data,
                'created_by'    => auth()->id(),
            ]);
        });
    }


    // Update coupon
    public function update(Coupon $coupon, array $data): Coupon
    {
        return DB::transaction(function () use ($coupon, $data) {
            // تحديث الكوبون
            $coupon->update([
                ...$data,
                'updated_by'    => auth()->id(),
            ]);

            return $coupon;
        });
    }


    // Delete coupon
    public function delete(Coupon $coupon): bool
    {
        return DB::transaction(function () use ($coupon) {
            return $coupon->delete();
        });
    }


    // Get coupon statistics
    public function getCouponStatistics(): array
    {
        $baseQuery = Coupon::query();

        return [
            'total'    => (clone $baseQuery)->count(),
            'active'   => (clone $baseQuery)->where('is_active', 1)->count(),
            'inactive' => (clone $baseQuery)->where('is_active', 0)->count(),
        ];
    }
}
