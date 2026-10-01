<?php

namespace App\Services\Tenants\BusinessCenter\Customer;

use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Customers\FieldValue\CustomerFieldValue;
use App\Models\Tenants\BusinessCenter\Customers\Phone\CustomerPhone;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\History\CustomerRelationshipHistory;
use App\Models\Tenants\BusinessCenter\Customers\Settlement\CustomerSettlement;
use App\Models\Tenants\BusinessCenter\Customers\UpdateRequest\CustomerUpdateRequest;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\BusinessCenter\Portfolio\PortfolioField;
use App\Models\Tenants\FinancialCollection\Collection as FinancialCollectionCollection;
use App\Models\Tenants\FinancialCollection\Collection\CustomerTransaction;
use App\Models\Tenants\GeneralSetting\Country\SettingsCountry;
use App\Models\Tenants\GeneralSetting\CustomerStatus\SettingsCustomerStatus;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Customer Management Service
 * Handles all customer-related operations (excluding messaging).
 */
class CustomerService
{
    /*
    |--------------------------------------------------------------------------
    | Create a new customer
    |--------------------------------------------------------------------------
    */
    public function createCustomer(array $data): Customer
    {
        return DB::transaction(function () use ($data) {
            $data = $this->handleNameLogic($data);

            $dynamic = $data['dynamic'] ?? [];
            unset($data['dynamic']);
            $phones = $data['phones'] ?? [];
            unset($data['phones']);

            $data['created_by'] = Auth::id();
            $customer = Customer::create($data);

            $this->createCustomerPhones($customer->id, $phones);
            $this->saveDynamicValues($customer->id, $dynamic);

            if (!empty($data['relationship_manager_id'])) {
                $this->logRelationshipChange($customer, null, $data['relationship_manager_id']);
            }

            return $customer;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Update customer data
    |--------------------------------------------------------------------------
    */
    public function updateCustomer(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            $data = $this->handleNameLogic($data);
            $dynamic = $data['dynamic'] ?? [];
            unset($data['dynamic']);
            $phones = $data['phones'] ?? null;
            unset($data['phones']);

            $oldManagerId = $customer->relationship_manager_id;

            $customer->update($data);

            if (isset($data['relationship_manager_id']) && $oldManagerId != $data['relationship_manager_id']) {
                $this->logRelationshipChange($customer, $oldManagerId, $data['relationship_manager_id']);
            }

            if (is_array($phones)) {
                $this->updateCustomerPhones($customer->id, $phones);
            }

            CustomerFieldValue::withTrashed()->where('customer_id', $customer->id)->forceDelete();
            $this->saveDynamicValues($customer->id, $dynamic);

            return $customer->fresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Create Update Request
    |--------------------------------------------------------------------------
    | إنشاء طلب  تحديث البيانات .
    */
    public function createUpdateRequest(Customer $customer, array $data): CustomerUpdateRequest
    {
        return DB::transaction(function () use ($customer, $data) {
            unset($data['id'], $data['created_at'], $data['updated_at'], $data['deleted_at']);

            return CustomerUpdateRequest::create([
                'customer_id' => $customer->id,
                'requested_by' => Auth::id(),
                'status' => CustomerUpdateRequest::STATUS_PENDING,
                'requested_data' => $data,
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Can Update Directly
    |--------------------------------------------------------------------------
    | التحقق من الصلاحيات  .
    */
    public function canUpdateDirectly(): bool
    {
        return auth()->user()->can('customers.update_directly');
    }

    /*
    |--------------------------------------------------------------------------
    | Delete customer
    |--------------------------------------------------------------------------
    */
    public function deleteCustomer(Customer $customer): bool
    {
        return DB::transaction(function () use ($customer) {
            $customer->phones()->delete();

            $result = $customer->delete();

            return $result;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Phone Management
    |--------------------------------------------------------------------------
    | Methods for managing customer phone numbers.
    */

    /*
    |--------------------------------------------------------------------------
    | Create customer phone numbers
    |--------------------------------------------------------------------------
    */
    private function createCustomerPhones(int $customerId, array $phonesData): void
    {
        foreach ($phonesData as $index => $phoneData) {
            if (!empty($phoneData['phone']) && $this->isValidPhoneNumber($phoneData['phone'])) {
                CustomerPhone::create([
                    'customer_id' => $customerId,
                    'phone' => $this->cleanPhoneNumber($phoneData['phone']),
                    'type' => $phoneData['type'] ?? 'mobile',
                    'is_primary' => $index === 0,
                    'notes' => $phoneData['notes'] ?? null,
                ]);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update customer phone numbers
    |--------------------------------------------------------------------------
    */
    private function updateCustomerPhones(int $customerId, array $phonesData): void
    {
        CustomerPhone::where('customer_id', $customerId)->delete();

        if (!empty($phonesData)) {
            $this->createCustomerPhones($customerId, $phonesData);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Data Retrieval & Queries
    |--------------------------------------------------------------------------
    | Methods for retrieving and querying customer data.
    */

    /*
    |--------------------------------------------------------------------------
    |Get base customer query with relationships
    |--------------------------------------------------------------------------
    */
    public function getCustomersQuery(string $viewType = 'all'): Builder
    {
        $query = Customer::query()
            ->with([
                'company:id,name,logo_path',
                'portfolio:id,name',
                'nationalityCountry:id,name',
                'relationshipManager:id,name',
                'createdBy:id,name',
                'customerStatus:id,name',
                'lastContactResult:id,name',
                'phones'
            ]);

        if ($viewType === 'my') {
            $query->where('relationship_manager_id', auth()->id());
        }

        return $query;
    }


    /*
    |--------------------------------------------------------------------------
    | Apply filters to customer query
    |--------------------------------------------------------------------------
    */
    public function filterCustomersQuery(Builder $query, array $filters): Builder
    {
        if (!empty($filters['customer_status_id'])) {
            $query->where('customer_status_id', $filters['customer_status_id']);
        }

        if (!empty($filters['customer_type'])) {
            $query->where('customer_type', $filters['customer_type']);
        }

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['portfolio_id'])) {
            $query->where('portfolio_id', $filters['portfolio_id']);
        }

        if (!empty($filters['nationality'])) {
            $query->where('nationality', $filters['nationality']);
        }

        if (!empty($filters['relationship_manager_id'])) {
            $managerIds = $filters['relationship_manager_id'];

            if (is_array($managerIds) && count($managerIds) > 0) {
                $query->whereIn('relationship_manager_id', $managerIds);
            } elseif (!is_array($managerIds) && !empty($managerIds)) {
                $query->where('relationship_manager_id', $managerIds);
            }
        }

        if (request()->filled('last_contact_result_id')) {
            $val = request('last_contact_result_id');
            if ($val === '__none') {
                $query->whereNull('last_contact_result_id');
            } else {
                $query->where('last_contact_result_id', $val);
            }
        }

        if (!empty($filters['sms_status'])) {
            if ($filters['sms_status'] === 'sent') {
                $query->whereNotNull('last_sms_at');
            } elseif ($filters['sms_status'] === 'not_sent') {
                $query->whereNull('last_sms_at');
            }
        }

        if (!empty($filters['sms_date_from'])) {
            $query->where('last_sms_at', '>=', $filters['sms_date_from'] . ' 00:00:00');
        }

        if (!empty($filters['sms_date_to'])) {
            $query->where('last_sms_at', '<=', $filters['sms_date_to'] . ' 23:59:59');
        }

        if (!empty($filters['search'])) {
            $searchTerm = trim($filters['search']);
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('identity_number', 'like', "%{$searchTerm}%")
                    ->orWhere('phone', 'like', "%{$searchTerm}%")
                    ->orWhereHas('phones', function ($phoneQuery) use ($searchTerm) {
                        $phoneQuery->where('phone', 'like', "%{$searchTerm}%");
                    });
            });
        }

        return $query;
    }

    /**
     * Get customer statistics
     */
    public function getCustomerStatistics(string $viewType = 'all'): array
    {
        // ==========================================
        // إحصائيات العملاء (query واحد خفيف)
        // ==========================================
        $baseWhere = $viewType === 'my'
            ? ['relationship_manager_id' => auth()->id()]
            : [];

        $customerStats = Customer::selectRaw("
        COUNT(*) as total,
        SUM(CASE WHEN customer_type = 'individual' THEN 1 ELSE 0 END) as individual,
        SUM(CASE WHEN customer_type = 'company' THEN 1 ELSE 0 END) as company
      ")
            ->where($baseWhere)
            ->first();

        return [
            // إحصائيات العملاء
            'total' => (int) $customerStats->total,
            'individual' => (int) $customerStats->individual,
            'company' => (int) $customerStats->company,
        ];
    }

    /**
     * Get form data
     */
    public function getFormData(): array
    {
        return [
            'companies' => Company::active()->select('id', 'name')->orderBy('name')->get(),
            'portfolios' => Portfolio::active()->select('id', 'name', 'company_id', 'has_split_names')
                ->with('company:id,name')->orderBy('name')->get(),
            'countries' => SettingsCountry::where('status', 'active')
                ->select('id', 'name')->orderBy('name')->get(),
            'users' => User::select('id', 'name')->orderBy('name')->get(),
            'customer_statuses' => SettingsCustomerStatus::where('status', 'active')
                ->select('id', 'name')->orderBy('name')->get(),
        ];
    }

    /**
     * Get portfolios by company
     */
    public function getPortfoliosByCompany(int $companyId): array
    {
        return Portfolio::active()
            ->where('company_id', $companyId)
            ->select('id', 'name', 'has_split_names')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Get all customer IDs based on filters
     */
    public function getAllCustomerIds(array $filters, string $viewType = 'all'): array
    {
        try {
            $query = $this->getCustomersQuery($viewType);
            $query = $this->filterCustomersQuery($query, $filters);

            return $query->pluck('customers.id')->toArray();
        } catch (\Exception $e) {
            throw $e;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Get Customers Count Based on Filters
    |--------------------------------------------------------------------------
    | جلب عدد العملاء حسب الفلاتر (للاستخدام في العداد)
    */
    public function getCustomersCount(array $filters, string $viewType = 'all'): int
    {
        try {
            $query = $this->getCustomersQuery($viewType);
            $query = $this->filterCustomersQuery($query, $filters);

            return $query->count();
        } catch (\Exception $e) {
            return 0;
        }
    }



    /*
    |--------------------------------------------------------------------------
    | Validation Methods
    |--------------------------------------------------------------------------
    | Methods for validating customer data.
    */

    /**
     * Check if customer has valid phone number
     */
    public function hasValidPhone(int $customerId): bool
    {
        $customer = Customer::find($customerId);
        return $customer && !empty($customer->phone) && $this->isValidPhoneNumber($customer->phone);
    }

    /**
     * Validate phone number format
     */
    public function isValidPhoneNumber(string $phone): bool
    {
        $phone = $this->cleanPhoneNumber($phone);

        $patterns = [
            '/^966[0-9]{9}$/',      // 966xxxxxxxxx
            '/^\+966[0-9]{9}$/',    // +966xxxxxxxxx
            '/^05[0-9]{8}$/',       // 05xxxxxxxx
            '/^5[0-9]{8}$/',        // 5xxxxxxxx
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $phone)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Clean phone number from special characters
     */
    public function cleanPhoneNumber(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', trim($phone));
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Data Retrieval
    |--------------------------------------------------------------------------
    | Methods for retrieving specific customer data sets.
    */

    /**
     * Get customers by status
     */
    public function getCustomersByStatus(int $statusId): Collection
    {
        return Customer::where('customer_status_id', $statusId)
            ->select('id', 'name', 'phone', 'customer_type')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get recently added customers
     */
    public function getRecentCustomers(int $days = 30): Collection
    {
        return Customer::where('created_at', '>=', now()->subDays($days))
            ->with(['company:id,name', 'portfolio:id,name', 'customerStatus:id,name'])
            ->select('id', 'name', 'phone', 'customer_type', 'company_id', 'portfolio_id', 'customer_status_id', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Relations
    |--------------------------------------------------------------------------
    | Methods for retrieving customers by their relations.
    */

    /**
     * Get customers by company
     */
    public function getCustomersByCompany(int $companyId): Collection
    {
        return Customer::where('company_id', $companyId)
            ->with('customerStatus:id,name')
            ->select('id', 'name', 'phone', 'customer_type', 'customer_status_id')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get customers by portfolio
     */
    public function getCustomersByPortfolio(int $portfolioId): Collection
    {
        return Customer::where('portfolio_id', $portfolioId)
            ->with('customerStatus:id,name')
            ->select('id', 'name', 'phone', 'customer_type', 'customer_status_id')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get customers by relationship manager
     */
    public function getCustomersByRelationshipManager(int $managerId): Collection
    {
        return Customer::where('relationship_manager_id', $managerId)
            ->with('customerStatus:id,name')
            ->select('id', 'name', 'phone', 'customer_type', 'customer_status_id', 'debt_amount')
            ->orderBy('name')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Utility & Helper Methods
    |--------------------------------------------------------------------------
    | Internal utility methods for supporting other operations.
    */

    /**
     * Get quick statistics for dashboard
     */
    public function getQuickStats(): array
    {
        return [
            'total_customers' => Customer::count(),
            'customers_by_status' => Customer::join('settings_customer_status', 'customers.customer_status_id', '=', 'settings_customer_status.id')
                ->select('settings_customer_status.name', DB::raw('count(*) as count'))
                ->groupBy('settings_customer_status.name')
                ->pluck('count', 'name')
                ->toArray(),
            'customers_with_phones' => Customer::whereNotNull('phone')->where('phone', '!=', '')->count(),
        ];
    }

    /**
     * Get customer financial statistics
     * جلب الإحصائيات المالية للعميل
     */
    public function getCustomerFinancialStats(int $customerId): array
    {
        $customer = Customer::with('transactions')->find($customerId);
        if (!$customer) {
            throw new \Exception('العميل غير موجود');
        }

        $totalDebt = $customer->transactions->where('type', 'debit')->sum('amount');

        $paidAmount = $customer->transactions->where('type', 'credit')->sum('amount');

        $remainingAmount = $customer->remaining_debt;

        $settlementAmount = \Illuminate\Support\Facades\Schema::hasTable('customer_settlements')
            ? (CustomerSettlement::where('customer_id', $customerId)
                ->whereNull('deleted_at')
                ->sum('settlement_amount') ?? 0)
            : 0;

        return [
            'total_debt' => $totalDebt,
            'paid_amount' => $paidAmount,
            'settlement_amount' => $settlementAmount,
            'remaining_amount' => $remainingAmount,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function getPortfolioFields(int $portfolioId)
    {
        return PortfolioField::where('portfolio_id', $portfolioId)
            ->select([
                'id',
                'field_name      as name',
                'field_type',
                'is_required'
            ])
            ->orderBy('id')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    private function saveDynamicValues(int $customerId, array $dynamic): void
    {
        foreach ($dynamic as $fieldId => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    CustomerFieldValue::create([
                        'customer_id'        => $customerId,
                        'portfolio_field_id' => $fieldId,
                        'value'              => $v,
                    ]);
                }
            } else {
                CustomerFieldValue::create([
                    'customer_id'        => $customerId,
                    'portfolio_field_id' => $fieldId,
                    'value'              => $value,
                ]);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Handle Name Logic
    |--------------------------------------------------------------------------
    | يعالج منطق الاسم: يجمع الأجزاء إلى اسم كامل، ولكنه لا يقسم الاسم الكامل.
    */
    private function handleNameLogic(array $data): array
    {
        $hasNameParts = !empty($data['first_name']) ||
            !empty($data['father_name']) ||
            !empty($data['grandfather_name']) ||
            !empty($data['family_name']);

        if ($hasNameParts) {
            $firstName = trim($data['first_name'] ?? '');
            $fatherName = trim($data['father_name'] ?? '');
            $grandfatherName = trim($data['grandfather_name'] ?? '');
            $familyName = trim($data['family_name'] ?? '');

            $fullNameParts = array_filter([$firstName, $fatherName, $grandfatherName, $familyName]);
            $data['name'] = implode(' ', $fullNameParts);
        }

        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Customer Accounts By Identity
    |--------------------------------------------------------------------------
    | Get all customer accounts by identity number .
    */
    public function getCustomerAccountsByIdentity(string $identityNumber): Collection
    {
        return Customer::where('identity_number', $identityNumber)
            ->with([
                'company:id,name,logo_path',
                'portfolio:id,name',
                'nationalityCountry:id,name',
                'relationshipManager:id,name',
                'createdBy:id,name',
                'customerStatus:id,name',
                'phones',
                'dynamicValues.portfolioField'
            ])
            ->addSelect([
                '*',
                'total_debit' => CustomerTransaction::selectRaw('COALESCE(sum(amount), 0)')
                    ->whereColumn('customer_id', 'customers.id')
                    ->where('type', 'debit'),
                'total_credit' => CustomerTransaction::selectRaw('COALESCE(sum(amount), 0)')
                    ->whereColumn('customer_id', 'customers.id')
                    ->where('type', 'credit'),
            ])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Comprehensive Financial Stats
    |--------------------------------------------------------------------------
    | Get comprehensive customer financial statistics for all accounts
    */
    public function getComprehensiveFinancialStats(string $identityNumber): array
    {
        $accounts = $this->getCustomerAccountsByIdentity($identityNumber);

        $totalDebit = 0;
        $totalCredit = 0;
        $totalSettlement = 0;

        foreach ($accounts as $account) {
            $totalDebit += $account->total_debit ?? 0;
            $totalCredit += $account->total_credit ?? 0;

            $settlement = \Illuminate\Support\Facades\Schema::hasTable('customer_settlements')
                ? (CustomerSettlement::where('customer_id', $account->id)
                    ->whereNull('deleted_at')
                    ->sum('settlement_amount') ?? 0)
                : 0;
            $totalSettlement += $settlement;
        }

        return [
            'total_accounts' => $accounts->count(),
            'total_debt' => $totalDebit,
            'total_paid' => $totalCredit,
            'total_settlement' => $totalSettlement,
            'total_remaining' => ($totalDebit - $totalCredit - $totalSettlement),
            'accounts' => $accounts
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Set Primary Phone for Customer
    |--------------------------------------------------------------------------
    */
    public function setPrimaryPhone(Customer $customer, int $phoneId): bool
    {
        return DB::transaction(function () use ($customer, $phoneId) {
            $phone = CustomerPhone::where('id', $phoneId)
                ->where('customer_id', $customer->id)
                ->first();

            if (!$phone) {
                throw new \Exception('رقم الهاتف غير موجود أو لا ينتمي لهذا العميل');
            }

            CustomerPhone::where('customer_id', $customer->id)
                ->update(['is_primary' => false]);

            return $phone->update(['is_primary' => true]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Log Relationship Change
    |--------------------------------------------------------------------------
    */
    private function logRelationshipChange(Customer $customer, $oldManagerId, $newManagerId): void
    {
        if (is_null($oldManagerId) && !is_null($newManagerId)) {
            $changeType = 'assigned';
        } elseif (!is_null($oldManagerId) && is_null($newManagerId)) {
            $changeType = 'unassigned';
        } else {
            $changeType = 'transfer';
        }

        CustomerRelationshipHistory::create([
            'customer_id' => $customer->id,
            'previous_manager_id' => $oldManagerId,
            'new_manager_id' => $newManagerId,
            'changed_by' => auth()->id(),
            'change_type' => $changeType,
            'changed_at' => now(),
        ]);
    }
}
