<?php

declare(strict_types=1);

namespace App\Actions\Tenants\FinancialCollection;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Phone\CustomerPhone;
use Illuminate\Support\Facades\DB;

class ManageCollectionCustomerPhoneAction
{
    public function add(Customer $customer, array $data): CustomerPhone
    {
        return DB::transaction(fn (): CustomerPhone => CustomerPhone::create([
            'customer_id' => $customer->id,
            'phone' => $data['phone'],
            'type' => $data['type'] ?? 'mobile',
            'is_primary' => false,
            'notes' => $data['notes'] ?? null,
        ]));
    }

    public function makePrimary(Customer $customer, int $phoneId): ?CustomerPhone
    {
        return DB::transaction(function () use ($customer, $phoneId): ?CustomerPhone {
            $phone = CustomerPhone::query()
                ->whereKey($phoneId)
                ->where('customer_id', $customer->id)
                ->first();

            if ($phone === null || $phone->is_primary) {
                return $phone;
            }

            CustomerPhone::query()->where('customer_id', $customer->id)->update(['is_primary' => false]);
            $phone->update(['is_primary' => true]);

            return $phone->refresh();
        });
    }
}
