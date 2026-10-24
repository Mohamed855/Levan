<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Bills;

use App\Contracts\Tenants\BankTransferReceiptGateway;
use App\Models\Admin\Bill;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Stancl\Tenancy\Exceptions\TenancyNotInitializedException;
use Throwable;

final class SubmitBankTransferReceiptAction
{
    public function __construct(
        private readonly BankTransferReceiptGateway $billingGateway,
    ) {}

    public function execute(int|string $billId, UploadedFile $receipt, int $bankId): Bill
    {
        $tenantId = tenant('id');

        if ($tenantId === null || $tenantId === '') {
            throw new TenancyNotInitializedException();
        }

        $path = $receipt->store('receipts/'.(string) $tenantId, 'local');

        try {
            return $this->billingGateway->submitBankTransferReceipt($billId, $path, $bankId);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }
    }
}
