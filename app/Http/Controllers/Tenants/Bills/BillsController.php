<?php

namespace App\Http\Controllers\Tenants\Bills;

use App\Actions\Tenants\Billing\SubmitBankTransferReceiptAction;
use App\DataTables\Tenants\Bills\BillsDataTable;
use App\Enums\Admin\Billing\BillStatus;
use App\Exceptions\Payment\PaymentInitiationException;
use App\Exceptions\Payment\PaymentVerificationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Payment\BankTransferReceiptRequest;
use App\Http\Requests\Tenants\Payment\PaymentRequest;
use App\Services\shared\Payment\Moyasar\MoyasarCallbackHandler;
use App\Services\shared\Payment\Moyasar\MoyasarFormConfiguration;
use App\Services\shared\Subscription\SubscriptionService;
use App\Services\Tenants\Shared\CentralBillingGateway;
use Closure;
use Illuminate\Http\Request;

class BillsController extends Controller
{
    protected const VIEW_PATH = 'tenants.bills';
    // protected const ROUTE_NAME =  "admin.subscriptionManage";

    public function __construct(
        private MoyasarCallbackHandler $moyasarCallbackHandler,
        private MoyasarFormConfiguration $moyasarFormConfiguration,
        private SubscriptionService $subscriptionService,
        private CentralBillingGateway $billingGateway,
        private SubmitBankTransferReceiptAction $submitReceipt,
    ) {
        $this->middleware('can:bills.view');

        $this->middleware(function (Request $request, Closure $next) {
            $id = $request->route('id');
            $bill = rescue(fn () => $this->billingGateway->billForCurrentTenant($id), report: false);

            if (! $bill) {
                return redirect()->to(tenant_route_name('bills.index'))->with('error', 'الفاتورة غير موجودة.');
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

            return $dataTable->render(self::VIEW_PATH.'.index', [
                // 'totalPlans'       => $statistics['total'],
                // 'activePlans'      => $statistics['active'],
                // 'inactivePlans'    => $statistics['inactive'],
            ]);
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    public function show($id)
    {
        $bill = $this->billingGateway->billForCurrentTenant($id);
        $settings = $this->billingGateway->platformSettings();

        $vatRate = 0.15;
        $vatAmount = round($bill->original_amount * $vatRate, 2);
        $priceWithoutTax = round($bill->original_amount - $vatAmount, 2);
        $discount = max(0.0, (float) $bill->discount_amount);

        return view(self::VIEW_PATH.'.show', compact(
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
            $bill = $this->billingGateway->billForCurrentTenant($id);

            if ($bill->status != BillStatus::UNPAID) {
                return redirect()->to(tenant_route_name('bills.index'))->with('error', 'لا يمكن دفع هذه الفاتورة  ');
            }

            $vatRate = 0.15;
            $vatAmount = round($bill->original_amount * $vatRate, 2);
            $priceWithoutTax = round($bill->original_amount - $vatAmount, 2);

            return view(self::VIEW_PATH.'.checkout', compact(
                'bill',
                'vatRate',
                'vatAmount',
                'priceWithoutTax',
            ));
            //
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->with('error', 'حدث خطأ أثناء تحميل الصفحة ');
        }
    }

    public function processCheckout(PaymentRequest $request, $id)
    {
        try {
            $bill = $this->billingGateway->billForCurrentTenant($id);

            if ($bill->status !== BillStatus::UNPAID) {
                return redirect()->to(tenant_route_name('bills.index'))->with('error', 'هذه الفاتورة غير متاحة للدفع.');
            }

            return view('tenants.payment.moyasar-checkout', [
                'amountInHalala' => $this->moyasarFormConfiguration->amountInHalala((float) $bill->amount),
                'backUrl' => tenant_route_name('bills.show', $bill->id),
                'bill' => $bill,
                'callbackUrl' => tenant_route_name('bills.callback', ['bill' => $bill->bill_number]),
                'description' => 'دفع فاتورة '.$bill->bill_number,
                'mode' => $this->moyasarFormConfiguration->getMode(),
                'publishableKey' => $this->moyasarFormConfiguration->getPublishableKey(),
            ]);
        } catch (PaymentInitiationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()->with('error', 'حدث خطأ أثناء معالجة الدفع');
        }
    }

    public function callback(Request $request)
    {
        try {
            $result = $this->moyasarCallbackHandler->verify($request);
            abort_unless($result['bill']->tenant_id === tenant()->id, 403);
            $this->subscriptionService->completeUnpaidBill($result['bill'], $result['payment']);

            return redirect()->to(tenant_route_name('subscriptions.index'))->with('success', 'تم الدفع وتفعيل الاشتراك بنجاح');
            //
        } catch (PaymentVerificationException $e) {
            //
            return redirect()->to(tenant_route_name('bills.index'))->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return redirect()->to(tenant_route_name('bills.index'))->with('error', 'حدث خطأ أثناء معالجة نتيجة الدفع');
        }
    }

    public function bankTransfer($id)
    {
        try {
            $bill = $this->billingGateway->billForCurrentTenant($id);
            $banks = $this->billingGateway->activeBanks();

            $amount = $bill->amount;

            return view(self::VIEW_PATH.'.bank_transfer', compact('bill', 'banks', 'amount'));
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->with('error', 'حدث خطأ أثناء تحميل صفحة التحويل البنكي');
        }
    }

    public function uploadReceipt(BankTransferReceiptRequest $request, $id)
    {
        try {
            $this->submitReceipt->execute(
                $id,
                $request->file('receipt'),
                (int) $request->validated('bank_id'),
            );

            return redirect(tenant_route_name('bills.show', $id))->with('success', 'تم رفع الإيصال بنجاح. سيتم التحقق منه قريباً.');
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->with('error', 'حدث خطأ أثناء رفع الإيصال');
        }
    }
}
