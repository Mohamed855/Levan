<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Bills;

use App\Models\Admin\Bill;
use App\Enums\Admin\Bills\BillStatus;
use App\Enums\Admin\Bills\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateBillStatusRequest extends FormRequest
{
    public const ALLOWED_TRANSITIONS = [
        'pending'              => ['paid', 'overdue', 'cancelled'],
        'unpaid'               => ['paid', 'overdue', 'cancelled'],
        'partially_paid'       => ['paid', 'refunded', 'cancelled'],
        'overdue'              => ['paid', 'cancelled'],
        'paid'                 => ['refunded'],
        'pending_verification' => [],
        'rejected'             => ['unpaid', 'cancelled'],
        'cancelled'            => ['unpaid'],
        'refunded'             => [],
        'trial'                => ['unpaid', 'cancelled'],
    ];

    public static function allowedTransitions(): array
    {
        return self::ALLOWED_TRANSITIONS;
    }

    /** @return list<string> */
    public static function allowedTransitionsFor(Bill $bill): array
    {
        $currentStatus = $bill->status instanceof BillStatus ? $bill->status->value : (string) $bill->status;
        $allowed = self::ALLOWED_TRANSITIONS[$currentStatus] ?? [];

        // Payment confirmation for a checkout must run its dedicated, idempotent
        // settlement path so provisioning and coupon redemption cannot be skipped.
        if (($bill->metadata['checkout_source'] ?? null) !== null) {
            $allowed = array_values(array_diff($allowed, ['paid']));
        }

        return $allowed;
    }

    public function authorize(): bool
    {
        $user = $this->user('admin');
        return $user !== null && $user->can('bills.update');
    }

    public function rules(): array
    {
        /** @var Bill|null $bill */
        $bill = $this->route('bill');
        $allowed = $bill instanceof Bill
            ? self::allowedTransitionsFor($bill)
            : self::ALLOWED_TRANSITIONS[BillStatus::PENDING->value];

        return [
            'status'         => ['required', 'string', Rule::in($allowed)],
            'settled_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'notes'          => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'status'         => 'حالة الفاتورة',
            'settled_amount' => 'المبلغ المحصل',
            'payment_method' => 'طريقة الدفع',
            'notes'          => 'ملاحظات العملية',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'حقل حالة الفاتورة مطلوب.',
            'status.in'       => 'انتقال الحالة المحدد غير مسموح به من الحالة الحالية.',
            'settled_amount.numeric' => 'يجب أن يكون المبلغ المحصل قيمة عددية.',
            'settled_amount.min'     => 'لا يمكن أن يكون المبلغ المحصل سالباً.',
        ];
    }
}
