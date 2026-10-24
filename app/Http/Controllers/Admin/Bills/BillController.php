<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Bills;

use App\Actions\Admin\Bills\UpdateBillStatusAction;
use App\Actions\Admin\Bills\ApproveBankTransferAction;
use App\Actions\Admin\Bills\RejectBankTransferAction;
use App\DataTables\Admin\Bills\BillDataTable;
use App\Enums\Admin\Bills\BillStatus;
use App\Enums\Admin\Bills\BillType;
use App\Enums\Admin\Bills\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Bills\UpdateBillStatusRequest;
use App\Http\Requests\Admin\Bills\RejectBankTransferRequest;
use App\Models\Admin\Bill;
use App\Models\Admin\AdminUser;
use App\Models\Admin\PaymentAttempt;
use App\Models\Admin\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class BillController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:bills.view')->only(['index', 'show']);
        $this->middleware('can:bills.update')->only(['updateStatus', 'approveBankTransfer', 'rejectBankTransfer']);
    }

    public function index(BillDataTable $dataTable)
    {
        $stats = [
            'total'     => Bill::query()->count(),
            'paid'      => Bill::query()->where('status', 'paid')->count(),
            'pending'   => Bill::query()->where('status', 'pending')->count(),
            'overdue'   => Bill::query()->where('status', 'overdue')->orWhere(function ($q) {
                $q->where('status', '!=', 'paid')->whereNotNull('due_at')->where('due_at', '<', now());
            })->count(),
            'cancelled' => Bill::query()->whereIn('status', ['cancelled', 'refunded'])->count(),
        ];

        $filterOptions = [
            'statuses' => BillStatus::options(),
            'types'    => BillType::options(),
            'paymentMethods' => PaymentMethod::options(),
        ];

        return $dataTable->render('admin.bills.index', compact('stats', 'filterOptions'));
    }

    public function show(Bill $bill): View
    {
        $this->authorize('view', $bill);

        return view('admin.bills.show', [
            'bill' => $bill->load(['tenant', 'paymentAttempts']),
            'allowedTransitions' => UpdateBillStatusRequest::allowedTransitionsFor($bill),
            'hasBankTransferReceipt' => $this->bankTransferReceiptPath($bill) !== null,
        ]);
    }

    public function print(Bill $bill): View
    {
        $this->authorize('view', $bill);
        $bill->load('tenant');

        $pricing = $bill->metadata['pricing'] ?? [];
        $hasPricingSnapshot = is_array($pricing)
            && isset($pricing['subtotal_amount'], $pricing['tax_amount'], $pricing['total_amount'])
            && is_numeric($pricing['subtotal_amount'])
            && is_numeric($pricing['tax_amount'])
            && is_numeric($pricing['total_amount']);

        $taxAmount = $hasPricingSnapshot
            ? (float) $pricing['tax_amount']
            : round((float) $bill->amount * 0.15, 2);
        $subtotalAmount = $hasPricingSnapshot
            ? (float) $pricing['subtotal_amount']
            : round((float) $bill->amount - $taxAmount, 2);

        return view('admin.bills.print', [
            'bill' => $bill,
            'general' => Setting::get('general', []),
            'subtotalAmount' => $subtotalAmount,
            'taxAmount' => $taxAmount,
            'discountAmount' => $hasPricingSnapshot && is_numeric($pricing['discount_amount'] ?? null)
                ? (float) $pricing['discount_amount']
                : null,
        ]);
    }

    public function updateStatus(
        UpdateBillStatusRequest $request,
        Bill $bill,
        UpdateBillStatusAction $action,
    ): RedirectResponse {
        $this->authorize('update', $bill);
        $action->execute(
            $bill,
            $request->validated(),
            $request->user('admin'),
        );

        return back()->with('success', 'تم تحديث حالة الفاتورة بنجاح.');
    }

    public function approveBankTransfer(Bill $bill, ApproveBankTransferAction $action): RedirectResponse
    {
        $this->authorize('update', $bill);
        $admin = request()->user('admin');
        abort_unless($admin instanceof AdminUser, 403);
        $action->execute($bill, $admin);

        return back()->with('success', 'تم اعتماد التحويل البنكي واستكمال تفعيل الاشتراك عند انطباقه.');
    }

    public function rejectBankTransfer(RejectBankTransferRequest $request, Bill $bill, RejectBankTransferAction $action): RedirectResponse
    {
        $this->authorize('update', $bill);
        $admin = $request->user('admin');
        abort_unless($admin instanceof AdminUser, 403);
        $action->execute($bill, (string) $request->validated('reason'), $admin);

        return back()->with('success', 'تم رفض التحويل البنكي وتسجيل السبب.');
    }

    public function downloadBankTransferReceipt(Bill $bill)
    {
        $this->authorize('view', $bill);
        $path = $this->bankTransferReceiptPath($bill);
        abort_unless($path !== null && Storage::disk('local')->exists($path), 404);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $extension = preg_match('/^[a-z0-9]{1,10}$/', $extension) ? $extension : 'bin';

        return Storage::disk('local')->download($path, 'bank-transfer-'.$bill->bill_number.'.'.$extension);
    }

    private function bankTransferReceiptPath(Bill $bill): ?string
    {
        $metadataPath = $bill->metadata['receipt_path'] ?? null;
        $tenantPrefix = $bill->tenant_id === null ? null : 'receipts/'.$bill->tenant_id.'/';
        if (is_string($metadataPath) && $tenantPrefix !== null && str_starts_with($metadataPath, $tenantPrefix)) {
            return $metadataPath;
        }

        $attempt = $bill->relationLoaded('paymentAttempts')
            ? $bill->paymentAttempts->firstWhere('provider', 'bank_transfer')
            : $bill->paymentAttempts()->where('provider', 'bank_transfer')->latest('id')->first();
        $path = $attempt instanceof PaymentAttempt ? $attempt->receipt_path : null;
        if (is_string($path)
            && ($bill->metadata['checkout_source'] ?? null) === 'site_checkout'
            && preg_match('#^checkout-receipts/[0-9a-f-]{36}/[^/]+$#i', $path)) {
            return $path;
        }

        return null;
    }
}
