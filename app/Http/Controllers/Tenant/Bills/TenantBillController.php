<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Bills;

use App\Actions\Tenant\Bills\SubmitBankTransferReceiptAction;
use App\DataTables\Tenant\Bills\TenantBillDataTable;
use App\Enums\Admin\Bills\BillStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Bills\SubmitBankTransferReceiptRequest;
use App\Services\Tenants\Shared\CentralBillingGateway;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class TenantBillController extends Controller
{
    public function __construct(
        private readonly CentralBillingGateway $billingGateway,
        private readonly SubmitBankTransferReceiptAction $submitReceipt,
    ) {
        $this->middleware('can:bills.view');
    }

    public function index(TenantBillDataTable $dataTable)
    {
        return $dataTable->render('tenant.bills.index', [
            'stats' => $dataTable->getStats(),
        ]);
    }

    public function show(int|string $bill): View
    {
        $billModel = $this->billingGateway->billForCurrentTenant($bill);
        $platformGeneral = $this->billingGateway->platformGeneralSettings();

        $vatRate = 0.15;
        $vatAmount = round((float) $billModel->amount * $vatRate, 2);
        $priceWithoutTax = round((float) $billModel->amount - $vatAmount, 2);

        return view('tenant.bills.show', [
            'bill' => $billModel,
            'platformGeneral' => $platformGeneral,
            'vatRate' => $vatRate,
            'vatAmount' => $vatAmount,
            'priceWithoutTax' => $priceWithoutTax,
        ]);
    }

    public function bankTransfer(int|string $bill): View|RedirectResponse
    {
        $billModel = $this->billingGateway->billForCurrentTenant($bill);

        if ($billModel->status !== BillStatus::UNPAID) {
            return redirect()->route('tenant.bills.show', $billModel->id)
                ->with('error', 'هذه الفاتورة لا يمكن سدادها في حالتها الحالية.');
        }

        $banks = $this->billingGateway->activeBanks();

        return view('tenant.bills.bank_transfer', [
            'bill' => $billModel,
            'banks' => $banks,
        ]);
    }

    public function uploadReceipt(SubmitBankTransferReceiptRequest $request, int|string $bill): RedirectResponse
    {
        $receipt = $request->file('receipt');
        abort_unless($receipt instanceof UploadedFile, 422, 'إشعار التحويل غير صالح.');

        $this->submitReceipt->execute(
            $bill,
            $receipt,
            (int) $request->validated('bank_id'),
        );

        return redirect()->route('tenant.bills.show', $bill)
            ->with('success', 'تم رفع إشعار التحويل بنجاح، وسيتم تدقيقه من قبل الإدارة.');
    }

    public function receipt(int|string $bill)
    {
        $billModel = $this->billingGateway->billForCurrentTenant($bill);
        $path = $this->billingGateway->receiptPathForCurrentTenant($billModel);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, 'receipt-'.$billModel->bill_number.'.'.pathinfo($path, PATHINFO_EXTENSION));
    }
}
