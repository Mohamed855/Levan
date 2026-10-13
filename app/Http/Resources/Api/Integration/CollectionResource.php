<?php

namespace App\Http\Resources\Api\Integration;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer?->name,
            'collected_amount' => $this->collected_amount,
            'collection_type' => $this->collection_type,
            'payment_type' => $this->payment_type,
            'payment_method' => $this->paymentMethod?->name,
            'receipt_number' => $this->receipt_number,
            'closing_code' => $this->closing_code,
            'collection_date' => $this->collection_date?->format('Y-m-d'),
            'status' => $this->status,
            'added_by' => $this->addedBy?->name,
            'approved_by' => $this->approvedBy?->name,
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'rejection_reason' => $this->rejection_reason,
            'is_verified' => (bool) $this->is_verified,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
