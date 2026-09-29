<?php

namespace App\Services\Tenants\BusinessCenter\Customer\PaymentPlan;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\PaymentPlan\CustomerPaymentPlan;
use Illuminate\Support\Facades\Auth;

class CustomerPaymentPlanService
{

    /*
    |--------------------------------------------------------------------------
    | Create Multiple Payment Plans for a Customer
    |--------------------------------------------------------------------------
    */
    public function createMultiplePaymentPlans(Customer $customer, array $plansData): array
    {
        $createdPlans = [];

        foreach ($plansData as $planData) {
            if (!empty($planData['due_date']) && !empty($planData['due_amount'])) {
                $createdPlans[] = $this->createSinglePaymentPlan($customer, $planData);
            }
        }

        return $createdPlans;
    }

    /*
    |--------------------------------------------------------------------------
    | Create Single Payment Plan for a Customer
    |--------------------------------------------------------------------------
    */
    public function createSinglePaymentPlan(Customer $customer, array $data): CustomerPaymentPlan
    {
        return CustomerPaymentPlan::create([
            'customer_id'        => $customer->id,
            'created_by'         => Auth::id(),
            'due_date'           => $data['due_date'],
            'due_amount'         => $data['due_amount'],
            'customer_follow_id' => $data['customer_follow_id'] ?? null, 
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Payment Plan
    |--------------------------------------------------------------------------
    */
    public function updatePaymentPlan(CustomerPaymentPlan $plan, array $data): CustomerPaymentPlan
    {
        $plan->update([
            'due_date' => $data['due_date'],
            'due_amount' => $data['due_amount'],
        ]);

        return $plan;
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Payment Plan
    |--------------------------------------------------------------------------
    */
    public function deletePaymentPlan(CustomerPaymentPlan $plan): bool
    {
        return $plan->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Get All Payment Plans for a Customer
    |--------------------------------------------------------------------------
    */
    public function getCustomerPaymentPlans(int $customerId): \Illuminate\Database\Eloquent\Collection
    {
        return CustomerPaymentPlan::where('customer_id', $customerId)
            ->with(['createdBy:id,name'])
            ->orderBy('due_date')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Simple Statistics for Customer Payment Plans
    |--------------------------------------------------------------------------
    */
    public function getSimpleStatistics(int $customerId): array
    {
        $plans = CustomerPaymentPlan::where('customer_id', $customerId);

        return [
            'total_count' => $plans->count(),
            'active_count' => $plans->whereNull('deleted_at')->count(),
            'overdue_count' => $plans->whereNull('deleted_at')->where('due_date', '<', now()->toDateString())->count(),
            'total_amount' => $plans->whereNull('deleted_at')->sum('due_amount'),
        ];
    }
}
