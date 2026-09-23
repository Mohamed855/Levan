<?php

namespace App\Services\Tenants\Archive\Customer;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\FinancialCollection\Collection\CustomerTransaction;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CustomerArchiveService
{
    /*
    |-------------------------------------------------------------------------
    | استرجاع العميل من الأرشيف
    |--------------------------------------------------------------------------
    */
    public function restoreCustomer(Customer $customer): Customer
    {
        return DB::transaction(function () use ($customer) {
            $customer->restore();
            $customer->phones()->onlyTrashed()->restore();
            return $customer->fresh(['company', 'portfolio', 'customerStatus', 'phones']);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | حذف العميل نهائياً من الأرشيف
    |--------------------------------------------------------------------------
    */
    public function forceDeleteCustomer(Customer $customer): bool
    {
        return DB::transaction(function () use ($customer) {
            $customer->phones()->onlyTrashed()->forceDelete();
            $customer->dynamicValues()->forceDelete();
            $customer->transactions()->delete();
            return $customer->forceDelete();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | استعلام العملاء المؤرشفين المحسن للأداء
    |--------------------------------------------------------------------------
    | ✅ تحميل جميع العلاقات مرة واحدة باستخدام Eager Loading
    */
    public function getArchivedCustomersQuery(): Builder
    {
        return Customer::onlyTrashed()
            ->select([
                'customers.id',
                'customers.name',
                'customers.account_number',
                'customers.customer_type',
                'customers.company_id',
                'customers.portfolio_id',
                'customers.relationship_manager_id',
                'customers.last_contact_result_id',
                'customers.deleted_at',
                'customers.created_at'
            ])
            // ✅ تحميل جميع العلاقات مرة واحدة (Eager Loading)
            ->with([
                'company:id,name,logo_path',
                'portfolio:id,name',
                'relationshipManager:id,name',
                'lastContactResult:id,name'
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | حساب المديونية المتبقية للعميل - OPTIMIZED VERSION
    |--------------------------------------------------------------------------
    | ✅ استعلام محسّن لحساب مديونية عميل واحد
    | يُستخدم في الجدول لعرض المديونية لكل عميل
    */
    public function getCustomerDebt(int $customerId): float
    {
        $result = CustomerTransaction::where('customer_id', $customerId)
            ->selectRaw('
                COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END), 0) - 
                COALESCE(SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END), 0) as debt
            ')
            ->first();

        return (float) round($result->debt ?? 0, 2);
    }

    /*
    |--------------------------------------------------------------------------
    | إحصائيات العملاء المؤرشفين - OPTIMIZED VERSION
    |--------------------------------------------------------------------------
    | ✅ حذف استعلام إجمالي المديونيات من الإحصائيات فقط
    | ✅ استعلام واحد محسّن للإحصائيات الأساسية
    */
    public function getArchivedCustomerStatistics(): array
    {
        // ✅ استعلام واحد محسّن للإحصائيات الأساسية
        $stats = Customer::onlyTrashed()
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN customer_type = "individual" THEN 1 ELSE 0 END) as individual,
                SUM(CASE WHEN customer_type = "company" THEN 1 ELSE 0 END) as company,
                SUM(CASE WHEN deleted_at >= ? THEN 1 ELSE 0 END) as recently_archived
            ', [now()->subDays(30)])
            ->first();

        return [
            'total' => $stats->total ?? 0,
            'individual' => $stats->individual ?? 0,
            'company' => $stats->company ?? 0,
            // ✅ تم حذف total_debt من الإحصائيات فقط
            'recently_archived' => $stats->recently_archived ?? 0,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | تطبيق الفلاتر على استعلام العملاء المؤرشفين
    |--------------------------------------------------------------------------
    */
    public function filterArchivedCustomersQuery(Builder $query, array $filters): Builder
    {
        if (!empty($filters['customer_type'])) {
            $query->where('customers.customer_type', $filters['customer_type']);
        }

        if (!empty($filters['company_id'])) {
            $query->where('customers.company_id', $filters['company_id']);
        }

        if (!empty($filters['portfolio_id'])) {
            $query->where('customers.portfolio_id', $filters['portfolio_id']);
        }

        if (!empty($filters['relationship_manager_id'])) {
            $query->where('customers.relationship_manager_id', $filters['relationship_manager_id']);
        }

        if (!empty($filters['last_contact_result_id'])) {
            $query->where('customers.last_contact_result_id', $filters['last_contact_result_id']);
        }

        if (!empty($filters['search'])) {
            $searchTerm = is_array($filters['search']) ?
                trim(implode(' ', $filters['search'])) :
                trim((string) $filters['search']);

            if (!empty($searchTerm)) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('customers.name', 'like', "%{$searchTerm}%")
                        ->orWhere('customers.account_number', 'like', "%{$searchTerm}%");
                });
            }
        }
        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | استرجاع جماعي للعملاء - OPTIMIZED VERSION
    |--------------------------------------------------------------------------
    | ✅ استخدام whereIn مباشرة بدون get()
    */
    public function massRestoreCustomers(array $customerIds): int
    {
        return DB::transaction(function () use ($customerIds) {
            // ✅ استعادة العملاء بكفاءة
            Customer::onlyTrashed()
                ->whereIn('id', $customerIds)
                ->update(['deleted_at' => null]);

            // ✅ استعادة الهواتف
            DB::table('customer_phones')
                ->whereIn('customer_id', $customerIds)
                ->whereNotNull('deleted_at')
                ->update(['deleted_at' => null]);

            return count($customerIds);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | حذف نهائي جماعي للعملاء - OPTIMIZED VERSION
    |--------------------------------------------------------------------------
    | ✅ حذف بكفاءة بدون جلب السجلات
    */
    public function massForceDeleteCustomers(array $customerIds): int
    {
        return DB::transaction(function () use ($customerIds) {
            // ✅ حذف الهواتف
            DB::table('customer_phones')
                ->whereIn('customer_id', $customerIds)
                ->delete();

            // ✅ حذف القيم الديناميكية
            DB::table('customer_field_values')
                ->whereIn('customer_id', $customerIds)
                ->delete();

            // ✅ حذف المعاملات (اختياري - حسب حاجتك)
            DB::table('customer_transactions')
                ->whereIn('customer_id', $customerIds)
                ->delete();

            // ✅ حذف العملاء نهائياً
            $count = Customer::onlyTrashed()
                ->whereIn('id', $customerIds)
                ->forceDelete();

            return $count;
        });
    }
}
