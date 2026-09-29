<?php

namespace App\Services\Tenants\Archive\Collection;

use App\Models\Tenants\FinancialCollection\Collection\Collection;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\GeneralSetting\PaymentMethod\SettingsPaymentMethod;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CollectionArchiveService
{
    /*
    |--------------------------------------------------------------------------
    | Restore Collection
    |--------------------------------------------------------------------------
    | Handle collection restoration with all related operations.
    */
    public function restoreCollection(Collection $collection): Collection
    {
        return DB::transaction(function () use ($collection) {
            // استرجاع التحصيل
            $collection->restore();

            return $collection->fresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Force Delete Collection
    |--------------------------------------------------------------------------
    | Handle collection permanent deletion with cleanup.
    */
    public function forceDeleteCollection(Collection $collection): bool
    {
        return DB::transaction(function () use ($collection) {
            // حذف الملف المرفق إن وجد
            if ($collection->attachment) {
                \Storage::disk('tenant_public')->delete($collection->attachment);
            }

            // حذف نهائي للتحصيل
            return $collection->forceDelete();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Get Archived Collections Query
    |--------------------------------------------------------------------------
    | Get base query for archived collections with eager loading.
    */
    public function getArchivedCollectionsQuery(): Builder
    {
        return Collection::onlyTrashed()
            ->select([
                'id',
                'customer_id',
                'collected_amount',
                'collection_type',
                'payment_method_id',
                'receipt_number',
                'closing_code',
                'collection_date',
                'payment_type',
                'attachment',
                'status',
                'added_by',
                'approved_by',
                'approved_at',
                'rejection_reason',
                'deleted_at',
                'created_at'
            ])->with([
                'customer:id,name,phone',
                'paymentMethod:id,name',
                'addedBy:id,name',
                'approvedBy:id,name'
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Archived Collection Statistics
    |--------------------------------------------------------------------------
    | Get archived collection statistics for dashboard.
    */
    public function getArchivedCollectionStatistics(): array
    {
        $baseQuery = $this->getArchivedCollectionsQuery();

        return [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'approved' => (clone $baseQuery)->where('status', 'approved')->count(),
            'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
            'total_amount' => (clone $baseQuery)->where('status', 'approved')->sum('collected_amount'),
            'cash_collections' => (clone $baseQuery)->where('collection_type', 'cash')->count(),
            'scheduled_collections' => (clone $baseQuery)->where('collection_type', 'scheduled')->count(),
            'recently_archived' => (clone $baseQuery)
                ->where('deleted_at', '>=', now()->subDays(30))
                ->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Filter Archived Collections Query
    |--------------------------------------------------------------------------
    | Apply filters to archived collections query.
    */
    public function filterArchivedCollectionsQuery(Builder $query, array $filters): Builder
    {
        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by customer
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        // Filter by payment method
        if (!empty($filters['payment_method_id'])) {
            $query->where('payment_method_id', $filters['payment_method_id']);
        }

        // Filter by collection type
        if (!empty($filters['collection_type'])) {
            $query->where('collection_type', $filters['collection_type']);
        }

        // Filter by payment type
        if (!empty($filters['payment_type'])) {
            $query->where('payment_type', $filters['payment_type']);
        }

        // Filter by date range
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->whereBetween('collection_date', [$filters['date_from'], $filters['date_to']]);
        } elseif (!empty($filters['date_from'])) {
            $query->whereDate('collection_date', '>=', $filters['date_from']);
        } elseif (!empty($filters['date_to'])) {
            $query->whereDate('collection_date', '<=', $filters['date_to']);
        }

        // Filter by added by
        if (!empty($filters['added_by'])) {
            $query->where('added_by', $filters['added_by']);
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Related Data for Forms
    |--------------------------------------------------------------------------
    | Get data needed for archived collection filters.
    */
    public function getFormData(): array
    {
        return [
            'customers' => Customer::select('id', 'name', 'phone')->orderBy('name')->get(),
            'paymentMethods' => SettingsPaymentMethod::where('status', 'active')
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
            'users' => User::select('id', 'name')->orderBy('name')->get(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Mass Restore Collections
    |--------------------------------------------------------------------------
    | Handle mass restoration of collections.
    */
    public function massRestoreCollections(array $collectionIds): int
    {
        return DB::transaction(function () use ($collectionIds) {
            $collections = Collection::onlyTrashed()->whereIn('id', $collectionIds);
            $count = $collections->count();

            if ($count > 0) {
                $collections->restore();
            }

            return $count;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Mass Force Delete Collections
    |--------------------------------------------------------------------------
    | Handle mass permanent deletion of collections.
    */
    public function massForceDeleteCollections(array $collectionIds): int
    {
        return DB::transaction(function () use ($collectionIds) {
            $collections = Collection::onlyTrashed()->whereIn('id', $collectionIds)->get();
            $count = 0;

            foreach ($collections as $collection) {
                // حذف الملف المرفق إن وجد
                if ($collection->attachment) {
                    \Storage::disk('tenant_public')->delete($collection->attachment);
                }

                if ($collection->forceDelete()) {
                    $count++;
                }
            }

            return $count;
        });
    }
}
