<?php

declare(strict_types=1);

namespace App\Actions\Admin\PublicRequests;

use App\Enums\Admin\PublicRequests\TrialRequestStatus;
use App\Models\Admin\PublicRequests\TrialRequest;

final class CreateTrialRequestAction
{
    public function execute(array $data): TrialRequest
    {
        return TrialRequest::create([
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $data['customer_phone'],
            'status' => TrialRequestStatus::PENDING,
        ]);
    }
}
