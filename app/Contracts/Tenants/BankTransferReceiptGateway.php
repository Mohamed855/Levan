<?php

declare(strict_types=1);

namespace App\Contracts\Tenants;

use App\Models\Admin\Bill;

interface BankTransferReceiptGateway
{
    public function submitBankTransferReceipt(int|string $billId, string $receiptPath, int $bankId): Bill;
}
