<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\UpdateRequest;

class BulkRejectCustomerUpdateRequestsRequest extends BulkCustomerUpdateRequestDecisionRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('customer_update_requests.reject') ?? false;
    }
}
