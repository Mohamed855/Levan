<?php

namespace App\Http\Controllers\Admin\Bills;

use App\DataTables\Admin\Bill\AdminBillsDataTable;
use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Billing\BillType;
use App\Exceptions\Subscription\SubscriptionCreationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Http\Requests\Admin\Bills\RejectBillRequest;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Settings\Settings;
use App\Services\shared\Bills\BillService;
use App\Services\shared\Subscription\SubscriptionService;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class AdminBillsController extends Controller
{
    protected const VIEW_PATH  = "admin.bills";

    public function __construct(
        private BillService $billService,
        private SubscriptionService $subscriptionService
    ) {
        $this->middleware('auth:admin');
        $this->authorizeResource(Bill::class, 'bill');
        $this->middleware('can:financial_transactions.view')->only(['index', 'show', 'print']);
        $this->middleware('can:subscriptions.pending.approve')->only(['verify', 'reactivate']);
        $this->middleware('can:subscriptions.pending.reject')->only(['reject']);
        $this->middleware('can:verify,bill')->only(['verify']);
        $this->middleware('can:reject,bill')->only(['reject']);
        $this->middleware('can:reactivate,bill')->only(['reactivate']);
    }

    public function index(AdminBillsDataTable $dataTable)
    {
        try {
            $stats = Bill::toBase()->selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status');

            $filterOptions = [
                'operationTypes' => [
                    ['value' => 'new_subscription', 'label' => 'دفع الكتروني'],
                    ['value' => 'renewal', 'label' => 'تجديد اشتراك'],
                    ['value' => 'license_purchase', 'label' => 'شراء ترخيص'],
                    ['value' => 'billing_cycle_change', 'label' => 'تغيير دورة الفاتورة'],
                ],
                'statuses' => [
                    ['value' => 'paid', 'label' => 'مدفوع'],
                    ['value' => 'unpaid', 'label' => 'غير مدفوع'],
                    ['value' => 'partially_paid', 'label' => 'مدفوع جزئياً'],
                    ['value' => 'overdue', 'label' => 'متأخر'],
                    ['value' => 'refunded', 'label' => 'مسترجع'],
                    ['value' => 'pending', 'label' => 'قيد الانتظار'],
                    ['value' => 'pending_verification', 'label' => 'بانتظار التحقق'],
                    ['value' => 'rejected', 'label' => 'مرفوض'],
                ],
                'paymentTypes' => [
                    ['value' => 'online', 'label' => 'دفع الكتروني'],
                    ['value' => 'cash', 'label' => 'يدوي'],
                    ['value' => 'bank_transfer', 'label' => 'تحويل بنكي'],
                ],
            ];

            return $dataTable->render(self::VIEW_PATH . '.index', [
                'billcounts'    => $stats->sum(),
                'paidbills'     => $stats->get(BillStatus::PAID->value, 0),
                'unpaidbills'   => $stats->get(BillStatus::UNPAID->value, 0),
                'pendingbills'  => $stats->get(BillStatus::PENDING->value, 0),
                'filterOptions' => $filterOptions,
            ]);
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    public function show(Bill $bill)
    {
        $settings               = Settings::firstOrFail();
        $vatRate                = 0.15; // قيمة الضريبة
        $vatAmount              = round($bill->original_amount * $vatRate, 2); // استخراج سعر الضريبة
        $priceWithoutTax        = round($bill->original_amount - $vatAmount, 2); // قبل الضريبة
        $discount               = max(0.0, (float) $bill->discount_amount); // الخصم

        return view(self::VIEW_PATH . '.show', [
            'settings'        => $settings,
            'bill'            => $bill,

            'vatRate'         => $vatRate,
            'priceWithoutTax' => $priceWithoutTax,
            'vatAmount'       => $vatAmount,

            'discount'        => $discount,
        ]);
    }

    public function print(Bill $bill)
    {
        return $this->renderPrint(self::VIEW_PATH . '.print', $this->billService->getPrintData($bill));
    }


    public function verify(AdminAuthenticatedRequest $request, Bill $bill)
    {
        try {
            if ($bill->status !== BillStatus::PENDING_VERIFICATION) {
                return redirect()->back()->with('error', 'هذه الفاتورة لا يمكن التحقق منها في حالتها الحالية.');
            }

            match ($bill->type) {
                BillType::NEW_SUBSCRIPTION => $bill->tenant_id
                    ? $this->subscriptionService->completeUnpaidBill($bill, [])
                    : $this->subscriptionService->completeBankTransferSubscription($bill),

                // تجديد عبر تحويل بنكي
                // BillType::RENEWAL => $this->subscriptionService->completeRenewalByBankTransfer($bill),

                // شراء تراخيص عبر تحويل بنكي
                // BillType::LICENSE_PURCHASE => $this->subscriptionService->completeLicensePurchaseByBankTransfer($bill),
            };


            return redirect()->route('admin.bills.index')->with('success', "تم التحقق من الفاتورة رقم {$bill->bill_number} بنجاح.");
            //
        } catch (SubscriptionCreationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->with('error', 'حدث خطأ أثناء التحقق من الفاتورة.');
        }
    }

    public function reject(RejectBillRequest $request, Bill $bill)
    {
        try {
            if ($bill->status !== BillStatus::PENDING_VERIFICATION) {
                return redirect()->back()->with('error', 'هذه الفاتورة لا يمكن رفضها في حالتها الحالية.');
            }

            match ($bill->type) {
                BillType::NEW_SUBSCRIPTION => !$bill->tenant_id
                    ? $this->billService->cancelBill($bill)
                    : $this->billService->rejectBill($bill, $request->validated('reject_reason')),

                // BillType::RENEWAL,
                // BillType::LICENSE_PURCHASE => $this->billService->rejectBill($bill, $request->reject_reason),
            };

            return redirect()->route('admin.bills.index')->with('success', "تم رفض الفاتورة رقم {$bill->bill_number}.");
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->with('error', 'حدث خطأ أثناء رفض الفاتورة.');
        }
    }


    public function reactivate(AdminAuthenticatedRequest $request, Bill $bill)
    {
        try {
            if ($bill->status !== BillStatus::CANCELLED) {
                return redirect()->back()->with('error', 'يمكن إعادة التفعيل للفواتير الملغاة فقط.');
            }

            $bill->update([
                'status'    => BillStatus::UNPAID,
                'due_date'  => now()->addDays(7),
            ]);

            return redirect()->route('admin.bills.show', $bill->id)->with('success', 'تم إعادة تفعيل الفاتورة بنجاح.');
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->with('error', 'حدث خطأ أثناء إعادة التفعيل.');
        }
    }
}
