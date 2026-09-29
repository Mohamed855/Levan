<?php

namespace App\Services\Tenants\BusinessCenter\Customer\PaymentHistory;

use App\Models\Tenants\FinancialCollection\Collection\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class CustomerPaymentHistoryService
{
    /*
    |--------------------------------------------------------------------------
    | Get Customer Payments (Approved Collections)
    |--------------------------------------------------------------------------
    | جلب سجل السدادات المعتمدة للعميل
    */
    public function getCustomerPayments(int $customerId): EloquentCollection
    {
        return Collection::where('customer_id', $customerId)
            ->where('status', 'approved')
            ->with([
                'paymentMethod:id,name',
                'addedBy:id,name',
                'approvedBy:id,name'
            ])
            ->orderBy('collection_date', 'desc')
            ->orderBy('approved_at', 'desc')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Payment Statistics
    |--------------------------------------------------------------------------
    | جلب إحصائيات السدادات للعميل
    */
    public function getPaymentStatistics(int $customerId): array
    {
        $payments = Collection::where('customer_id', $customerId)
            ->where('status', 'approved');

        return [
            'total_count' => $payments->count(),
            'total_amount' => $payments->sum('collected_amount'),
            'cash_count' => (clone $payments)->where('collection_type', 'cash')->count(),
            'cash_amount' => (clone $payments)->where('collection_type', 'cash')->sum('collected_amount'),
            'scheduled_count' => (clone $payments)->where('collection_type', 'scheduled')->count(),
            'scheduled_amount' => (clone $payments)->where('collection_type', 'scheduled')->sum('collected_amount'),
            'with_settlement_count' => (clone $payments)->where('payment_type', 'settlement')->count(),
            'with_settlement_amount' => (clone $payments)->where('payment_type', 'settlement')->sum('collected_amount'),
            'without_settlement_count' => (clone $payments)->where('payment_type', 'normal')->count(),
            'without_settlement_amount' => (clone $payments)->where('payment_type', 'normal')->sum('collected_amount'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Recent Payments
    |--------------------------------------------------------------------------
    | جلب آخر السدادات (للعرض السريع)
    */
    public function getRecentPayments(int $customerId, int $limit = 5): EloquentCollection
    {
        return Collection::where('customer_id', $customerId)
            ->where('status', 'approved')
            ->with([
                'paymentMethod:id,name',
                'addedBy:id,name'
            ])
            ->orderBy('collection_date', 'desc')
            ->limit($limit)
            ->get();
    }
}