<?php
namespace App\Services\Admin\TransferRequest;

use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\Subscription\Subscription;

class TransferRequestService
{
    public function transferRequestsIndex(){
        return Subscription::with(['periods' , 'plan' , 'tenant'])->where('status' , SubscriptionStatus::SUSPENDED)->whereNotNull('transfer_bank_attachment')->get();
    }
}