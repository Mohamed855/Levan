<?php

declare(strict_types=1);

namespace App\Actions\Tenants\Billing;

use App\Services\Tenants\Shared\CentralBillingGateway;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class SubmitBankTransferReceiptAction
{
    public function __construct(private readonly CentralBillingGateway $billingGateway) {}

    public function execute(int|string $billId, UploadedFile $receipt, int $bankId): void
    {
        $path = $receipt->store('receipts', 'public');

        try {
            $this->billingGateway->submitBankTransferReceipt($billId, $path, $bankId);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }
    }
}
