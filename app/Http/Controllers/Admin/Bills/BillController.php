<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Bills;

use App\Actions\Admin\Bills\UpdateBillStatusAction;
use App\DataTables\Admin\Bills\BillDataTable;
use App\Enums\Admin\Bills\BillStatus;
use App\Enums\Admin\Bills\BillType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Bills\UpdateBillStatusRequest;
use App\Models\Admin\Bill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class BillController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:bills.view')->only(['index', 'show']);
        $this->middleware('can:bills.update')->only('updateStatus');
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
        ];

        return $dataTable->render('admin.bills.index', compact('stats', 'filterOptions'));
    }

    public function show(Bill $bill): View
    {
        return view('admin.bills.show', [
            'bill' => $bill->load('tenant'),
        ]);
    }

    public function updateStatus(
        UpdateBillStatusRequest $request,
        Bill $bill,
        UpdateBillStatusAction $action,
    ): RedirectResponse {
        $action->execute(
            bill: $bill,
            status: $request->validated('status'),
            settledAmount: $request->validated('settled_amount') !== null
                ? (float) $request->validated('settled_amount')
                : null,
            paidAt: $request->validated('paid_at'),
            paymentMethod: $request->validated('payment_method'),
        );

        return back()->with('success', 'تم تحديث حالة الفاتورة بنجاح.');
    }
}
