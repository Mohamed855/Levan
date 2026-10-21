<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Bills;

use App\DataTables\Tenant\Bills\TenantBillDataTable;
use App\Enums\Admin\Bills\BillStatus;
use App\Http\Controllers\Controller;
use App\Services\Tenants\Shared\CentralBillingGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class TenantBillController extends Controller
{
    public function __construct(
        private readonly CentralBillingGateway $billingGateway
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
        $settings = $this->billingGateway->platformSettings();

        $vatRate = 0.15;
        $vatAmount = round((float) $billModel->amount * $vatRate, 2);
        $priceWithoutTax = round((float) $billModel->amount - $vatAmount, 2);

        return view('tenant.bills.show', [
            'bill' => $billModel,
            'settings' => $settings,
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

    public function uploadReceipt(Request $request, int|string $bill): RedirectResponse
    {
        $request->validate([
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'bank_id' => ['required', 'integer'],
        ], [
            'receipt.required' => 'يرجى إرفاق إشعار أو إيصال التحويل.',
            'receipt.mimes' => 'يجب أن يكون الإشعار صورة (JPG, PNG) أو ملف PDF.',
            'receipt.max' => 'يجب ألا يتجاوز حجم الملف 5 ميجابايت.',
            'bank_id.required' => 'يرجى اختيار الحساب البنكي المحول إليه.',
        ]);

        $path = $request->file('receipt')->store('receipts/'.tenant('id'), 'public');

        $this->billingGateway->submitBankTransferReceipt($bill, $path, (int) $request->input('bank_id'));

        return redirect()->route('tenant.bills.show', $bill)
            ->with('success', 'تم رفع إشعار التحويل بنجاح، وسيتم تدقيقه من قبل الإدارة.');
    }
}
