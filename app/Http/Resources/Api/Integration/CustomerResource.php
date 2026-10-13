<?php

namespace App\Http\Resources\Api\Integration;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'customer_type' => $this->customer_type,
            'identity_number' => $this->identity_number,
            'nationality' => $this->nationalityCountry?->name,
            'phone' => $this->phone,
            'account_number' => $this->account_number,
            'debt_amount' => $this->debt_amount,
            'company' => $this->company?->name,
            'portfolio' => $this->portfolio?->name,
            'relationship_manager' => $this->relationshipManager?->name,
            'status' => $this->customerStatus?->name,
            'contract_date' => $this->contract_date?->format('Y-m-d'),
            'default_date' => $this->default_date?->format('Y-m-d'),
            'commission_rate' => $this->commission_rate,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
