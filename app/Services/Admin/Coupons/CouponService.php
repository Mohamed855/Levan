<?php

namespace App\Services\Admin\Coupons;

use App\Models\Admin\Coupons\Coupon;
use Illuminate\Support\Facades\DB;

class CouponService
{
    // Create Coupon
    public function create(array $data): Coupon
    {
        return DB::transaction(function () use ($data) {

            return Coupon::create([
                ...$data,
                'created_by' => auth('admin')->id() ?? auth()->id(),
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
                'updated_by' => auth('admin')->id() ?? auth()->id(),
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

    // Toggle active status
    public function toggleActive(Coupon $coupon, ?bool $status = null): Coupon
    {
        return DB::transaction(function () use ($coupon, $status) {
            $newStatus = $status !== null ? $status : ! $coupon->is_active;

            $coupon->update([
                'is_active' => $newStatus,
                'updated_by' => auth('admin')->id() ?? auth()->id(),
            ]);

            return $coupon->fresh();
        });
    }

    // Get coupon statistics
    public function getCouponStatistics(): array
    {
        $baseQuery = Coupon::query();

        return [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('is_active', 1)->count(),
            'inactive' => (clone $baseQuery)->where('is_active', 0)->count(),
            'expired' => (clone $baseQuery)->whereNotNull('expiry_date')->where('expiry_date', '<', now()->toDateString())->count(),
        ];
    }

    // Generate unique coupon code
    public function generateUniqueCode(int $length = 8): string
    {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[random_int(0, strlen($characters) - 1)];
            }
        } while (Coupon::where('code', $code)->exists());

        return $code;
    }
}
