<?php

namespace App\Http\Controllers\Tenants\Bills;

use App\DataTables\Tenants\Bills\BillsDataTable;
use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Billing\PaymentMethod;
use App\Exceptions\Payment\PaymentInitiationException;
use App\Exceptions\Payment\PaymentVerificationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Payment\BankTransferReceiptRequest;
use App\Http\Requests\Tenants\Payment\PaymentRequest;
use App\Models\Admin\Bank\Bank;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Settings\Settings;
use App\Services\shared\Bills\BillService;
use App\Services\shared\Payment\Moyasar\MoyasarCallbackHandler;
use App\Services\shared\Payment\PaymentData;
use App\Services\shared\Payment\PaymentService;
use App\Services\shared\Subscription\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Facades\Tenancy;

class BillsController extends Controller
{
    protected const VIEW_PATH  =  "tenants.bills";
    // protected const ROUTE_NAME =  "admin.subscriptionManage";


    public function __construct(
        private MoyasarCallbackHandler $moyasarCallbackHandler,
        private PaymentService         $paymentService,
        private BillService            $billService,
        private SubscriptionService    $subscriptionService,
    ) {
        $this->middleware('can:الفواتير');

        $this->middleware(function (Request $request, Closure $next) {
            $id   = $request->route('id');
            $bill = Bill::find($id);

            if (!$bill) {
                return redirect()->to(tenant_route_name('bills.index'))->with('error', 'الفاتورة غير موجودة.');
            }

            if ($bill->tenant_id !== tenant()->id) {
                return redirect()->to(tenant_route_name('bills.index'))->with('error', 'ليس لديك صلاحية الوصول لهذه الفاتورة.');
            }

            if ($bill->status !== BillStatus::UNPAID) {
                return redirect()->to(tenant_route_name('bills.show', $id))->with('error', 'هذه الفاتورة لا يمكن دفعها في حالتها الحالية.');
            }

            return $next($request);
        })->only(['checkout', 'processCheckout', 'bankTransfer', 'uploadReceipt']);
    }


    public function index(BillsDataTable $dataTable)
    {
        try {
            // $statistics = $this->couponService->getCouponStatistics();

            return $dataTable->render(self::VIEW_PATH . '.index', [
                // 'totalPlans'       => $statistics['total'],
                // 'activePlans'      => $statistics['active'],
                // 'inactivePlans'    => $statistics['inactive'],
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    public function show($id)
    {
        $bill      = Bill::findOrFail($id);
        $settings  = Settings::firstOrFail();


        $vatRate                = 0.15;
        $vatAmount              = round($bill->original_amount * $vatRate, 2);
        $priceWithoutTax        = round($bill->original_amount - $vatAmount, 2);
        $discount               = max(0.0, (float) $bill->discount_amount);

        return view(self::VIEW_PATH . '.show', compact(
            'settings',
            'bill',

            'vatRate',
            'vatAmount',
            'priceWithoutTax',
            'discount'
        ));
    }

    public function checkout($id)
    {
        try {
            $bill  = Bill::findOrFail($id);

            if ($bill->status != BillStatus::UNPAID) {
                return redirect()->to(tenant_route_name('bills.index'))->with('error', "لا يمكن دفع هذه الفاتورة  ");
            }

            $vatRate                = 0.15;
            $vatAmount              = round($bill->original_amount * $vatRate, 2);
            $priceWithoutTax        = round($bill->original_amount - $vatAmount, 2);

            return view(self::VIEW_PATH . '.checkout', compact(
                'bill',
                'vatRate',
                'vatAmount',
                'priceWithoutTax',
            ));
            //
        } catch (\Exception $e) {
            Log::error($e);
            return redirect()->back()->with('error', 'حدث خطأ أثناء تحميل الصفحة ');
        }
    }

    public function processCheckout(PaymentRequest $request, $id)
    {
        try {
            $bill  = Bill::findOrFail($id);

            if ($bill->status !== BillStatus::UNPAID) {
                return redirect()->to(tenant_route_name('bills.index'))->with('error', 'هذه الفاتورة غير متاحة للدفع.');
            }

            $url = $this->paymentService->initiatePayment(
                PaymentData::fromRequest($request, $bill->amount, 'دفع فاتورة'),
                $bill,
                'billPayment'
            );

            return redirect($url);
            //
        } catch (PaymentInitiationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error($e);
            return redirect()->back()->with('error', 'حدث خطأ أثناء معالجة الدفع');
        }
    }

    public function callback(Request $request)
    {
        try {
            $result = $this->moyasarCallbackHandler->verify($request);
            $this->subscriptionService->completeUnpaidBill($result['bill'], $result['payment']);

            return redirect()->to(tenant_route_name('subscriptions.index'))->with('success', 'تم الدفع وتفعيل الاشتراك بنجاح');
            //
        } catch (PaymentVerificationException $e) {
            //
            return redirect()->to(tenant_route_name('bills.index'))->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error($e);
            return redirect()->to(tenant_route_name('bills.index'))->with('error', 'حدث خطأ أثناء معالجة نتيجة الدفع');
        }
    }

    public function bankTransfer($id)
    {
        try {
            $bill  = Bill::findOrFail($id);
            $banks = Bank::where('is_active', 1)->get();

            $amount = $bill->amount;

            return view(self::VIEW_PATH . '.bank_transfer', compact('bill', 'banks', 'amount'));
        } catch (\Exception $e) {
            Log::error($e);
            return redirect()->back()->with('error', 'حدث خطأ أثناء تحميل صفحة التحويل البنكي');
        }
    }

    public function uploadReceipt(BankTransferReceiptRequest $request, $id)
    {
        try {
            $receiptPath = $request->file('receipt')->store('receipts', 'public');

            Tenancy::central(function () use ($id, $receiptPath, $request) {
                Bill::findOrFail($id)->update([
                    'invoice_url'    => $receiptPath,
                    'bank_id'        => $request->bank_id,
                    'payment_method' => PaymentMethod::BANK_TRANSFER,
                    'status'         => BillStatus::PENDING_VERIFICATION,
                ]);
            });

            return redirect(tenant_route_name('bills.show', $id))->with('success', 'تم رفع الإيصال بنجاح. سيتم التحقق منه قريباً.');
        } catch (\Exception $e) {
            Log::error($e);
            return redirect()->back()->with('error', 'حدث خطأ أثناء رفع الإيصال');
        }
    }


    public function __bankTransfer__($id)
    {
        try {

            [$bill, $amount, $banks] = Tenancy::central(function () use ($id) {
                $bill    = Bill::where('id', $id)->firstOrFail();
                $amount  = $bill->amount - $bill->settlement_amount;
                $banks   = Bank::where('is_active', 1)->get();

                return [$bill, $amount, $banks];
            });

            return view(self::VIEW_PATH . '.bank_transfer', compact('bill', 'banks', 'amount'));
            //
        } catch (\Exception $e) {
            Log::error($e);
            return redirect()->back()->with('error', 'حدث خطأ أثناء تحميل صفحة التحويل البنكي');
        }
    }

    public function __uploadReceipt__(BankTransferReceiptRequest $request, $id)
    {
        try {
            if (!$request->hasFile('receipt')) {
                return redirect()->back()->with('error', 'لم يتم رفع الإيصال، يرجى اختيار ملف.');
            }

            $receiptPath = $request->file('receipt')->store('receipts', 'tenant_public');

            Tenancy::central(function () use ($id, $receiptPath, $request) {
                $bill = Bill::findOrFail($id);

                $bill->update([
                    'invoice_url'    => $receiptPath,
                    'bank_id'        => $request->bank_id,
                    'transfer_date'  => $request->transfer_date,
                    'transfer_notes' => $request->notes,
                    'payment_method' => PaymentMethod::BANK_TRANSFER,
                    'status'         => BillStatus::PENDING_VERIFICATION,
                ]);
            });

            return redirect(tenant_route_name('bills.show', $id))->with('success', 'تم رفع إيصال التحويل بنجاح. سيتم التحقق منه قريباً.');
        } catch (\Exception $e) {
            Log::error($e);
            return redirect()->back()->with('error', 'حدث خطأ أثناء رفع الإيصال. يرجى المحاولة مرة أخرى.');
        }
    }
}
