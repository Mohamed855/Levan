<?php

namespace App\Services\shared\Payment;

use App\Exceptions\Payment\PaymentInitiationException;
use App\Models\Admin\Bills\Bill;
use App\Services\shared\Bills\BillService;
use App\Services\shared\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    private const SUPPORTED_PAYMENT_METHODS  = [
        'newSubscriptionPayment',
        'renewSubscriptionPayment',
        'licensePayment',
        'billPayment', // دفع فاتورة
    ];

    public function __construct(private PaymentGatewayInterface $gateway, private BillService $billService) {}

    public function initiatePayment(PaymentData $data, Bill $bill, string $paymentMethod): string
    {
        $data = $data->withBillNumber($bill->bill_number);

        try {
            if (!in_array($paymentMethod, self::SUPPORTED_PAYMENT_METHODS)) {
                throw new PaymentInitiationException("طريقة الدفع غير مدعومة: {$paymentMethod} ");
            }

            $payment = $this->$paymentMethod($data);

            if (isset($payment['source']['transaction_url'])) {
                return $payment['source']['transaction_url'];
            }

            throw new PaymentInitiationException('لم يتم الحصول على رابط الدفع.');
            //
        } catch (PaymentInitiationException $e) {
            //
            $this->handleBillCancellation($bill);
            throw $e;
        } catch (\Throwable $e) {
            //
            $this->handleBillCancellation($bill);

            Log::error('PaymentService::initiate failed', [
                'bill_number'    => $bill->bill_number,
                'payment_method' => $paymentMethod,
                'error'          => $e->getMessage(),
            ]);
            throw new PaymentInitiationException('حدث خطأ أثناء إنشاء عملية الدفع.');
        }
    }

    /*
    |============================================================================
    |============================================================================
    |                              Private Functions
    |============================================================================
    |============================================================================
    */
    private function handleBillCancellation(Bill $bill): void
    {
        try {
            $this->billService->cancelBill($bill);
        } catch (\Throwable $e) {
            Log::error('فشل إلغاء الفاتورة', ['bill_number' => $bill->bill_number]);
        }
    }

    /*
    |============================================================================
    |============================================================================
    |                          كل انواع الدفع
    |============================================================================
    |============================================================================
    */
    private function newSubscriptionPayment(PaymentData $data): array // الاشتراك اول مرة من الموقع الرئيسي
    {
        return $this->gateway->createPayment($data, route('site.plans.checkout.callback'));
    }

    private function renewSubscriptionPayment(PaymentData $data): array
    {
        return $this->gateway->createPayment($data, tenant_route_name('subscriptions.renew.callback'));
    }

    private function licensePayment(PaymentData $data): array
    {
        return $this->gateway->createPayment($data, tenant_route_name('subscriptions.licenses.callback'));
    }

    private function billPayment(PaymentData $data): array
    {
        return $this->gateway->createPayment($data, tenant_route_name('bills.callback'));
    }
}
