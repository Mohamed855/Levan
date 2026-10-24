<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Actions\Tenant\Bills\SubmitBankTransferReceiptAction;
use App\Contracts\Tenants\BankTransferReceiptGateway;
use App\Models\Admin\Bill;
use App\Models\Admin\Tenants\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Stancl\Tenancy\Contracts\Tenant as TenantContract;
use Tests\TestCase;

final class SubmitBankTransferReceiptActionTest extends TestCase
{
    protected function tearDown(): void
    {
        app()->forgetInstance(TenantContract::class);
        app()->forgetInstance(BankTransferReceiptGateway::class);

        parent::tearDown();
    }

    public function test_it_removes_the_private_receipt_when_central_submission_fails(): void
    {
        Storage::fake('local');
        $this->bindTenant('tenant-a');
        app()->instance(BankTransferReceiptGateway::class, new class implements BankTransferReceiptGateway {
            public function submitBankTransferReceipt(int|string $billId, string $receiptPath, int $bankId): Bill
            {
                Storage::disk('local')->assertExists($receiptPath);

                throw new RuntimeException('Simulated central persistence failure.');
            }
        });

        try {
            app(SubmitBankTransferReceiptAction::class)->execute(
                123,
                UploadedFile::fake()->create('receipt.pdf', 12, 'application/pdf'),
                99,
            );
            self::fail('The central submission should have failed.');
        } catch (RuntimeException) {
            Storage::disk('local')->assertDirectoryEmpty('receipts/tenant-a');
        }
    }

    private function bindTenant(string $id): void
    {
        $tenant = new Tenant();
        $tenant->setAttribute('id', $id);

        app()->instance(TenantContract::class, $tenant);
    }
}
