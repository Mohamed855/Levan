<?php

namespace App\Services\shared\Payment\Moyasar;

use App\Enums\Admin\Billing\BillStatus;
use App\Exceptions\Payment\PaymentVerificationException;
use App\Models\Admin\Bills\Bill;
use App\Services\shared\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MoyasarCallbackHandler
{
    public function __construct(private PaymentGatewayInterface $gateway) {}

    public function verify(Request $request): array
    {
        $paymentId  = $request->get('id');
        $billNumber = $request->get('bill');

        if (!$paymentId || !$billNumber) {
            throw new PaymentVerificationException('بيانات الطلب غير مكتملة.');
        }

        $bill = Bill::where('bill_number', $billNumber)->whereIn('status', [BillStatus::PENDING, BillStatus::UNPAID])->first();

        if (!$bill) {
            throw new PaymentVerificationException('العملية غير موجودة أو تمت معالجتها مسبقاً.');
        }

        if ($bill->due_date && now()->isAfter($bill->due_date)) {
            $bill->update(['status' => BillStatus::CANCELLED]);
            throw new PaymentVerificationException('انتهت صلاحية هذه العملية.');
        }

        $payment = $this->gateway->getPayment($paymentId);

        if (!isset($payment['status']) || $payment['status'] !== 'paid') {
            throw new PaymentVerificationException("فشل الدفع. الحالة: " . ($payment['status'] ?? 'unknown'));
        }

        $paidAmount = (float) $payment['amount'] / 100;

        if (abs($paidAmount - (float) $bill->amount) > 0.01) {
            Log::critical('مبلغ الدفع لا يطابق المتوقع', [
                'paid'        => $paidAmount,
                'expected'    => $bill->amount,
                'bill_number' => $billNumber,
            ]);
            throw new PaymentVerificationException('مبلغ الدفع غير متطابق.');
        }

        return [
            'bill'    => $bill,
            'payment' => $payment,
        ];
    }
}
