<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Bills;

use App\Models\Admin\Bill;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateBillStatusRequest extends FormRequest
{
    public const ALLOWED_TRANSITIONS = [
        'pending'   => ['paid', 'overdue', 'cancelled'],
        'overdue'   => ['paid', 'cancelled'],
        'paid'      => ['refunded'],
        'cancelled' => ['pending'],
        'refunded'  => [],
    ];

    public static function allowedTransitions(): array
    {
        return self::ALLOWED_TRANSITIONS;
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
        $currentStatus = $bill instanceof Bill ? $bill->status : 'pending';
        $allowed = self::ALLOWED_TRANSITIONS[$currentStatus] ?? [];

        return [
            'status'         => ['required', 'string', Rule::in($allowed)],
            'settled_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
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
