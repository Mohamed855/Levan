<?php

declare(strict_types=1);

namespace App\Actions\Admin\PublicRequests;

use App\Enums\Admin\PublicRequests\ContactRequestStatus;
use App\Models\Admin\PublicRequests\ContactRequest;

final class CreateContactRequestAction
{
    public function execute(array $data): ContactRequest
    {
        return ContactRequest::create([
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'message' => $data['message'],
            'status' => ContactRequestStatus::PENDING,
        ]);
    }
}
