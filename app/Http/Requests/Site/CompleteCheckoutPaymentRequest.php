<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class CompleteCheckoutPaymentRequest extends FormRequest
{
    public const SESSION_RECEIPT_KEY = 'checkout_preserved_receipt';

    public function authorize(): bool
    {
        return $this->session()->has('checkout_verification');
    }

    protected function prepareForValidation(): void
    {
        if (! $this->hasSession()) {
            return;
        }

        $preserved = $this->session()->get(self::SESSION_RECEIPT_KEY);
        if (! is_array($preserved) || empty($preserved['path'])) {
            return;
        }

        if ($this->hasFile('transfer_receipt')) {
            return;
        }

        if ($this->boolean('remove_transfer_receipt')) {
            Storage::disk('local')->delete((string) $preserved['path']);
            $this->session()->forget(self::SESSION_RECEIPT_KEY);

            return;
        }

        $path = (string) $preserved['path'];
        if (str_starts_with($path, 'checkout-draft-receipts/') && Storage::disk('local')->exists($path)) {
            $restored = new UploadedFile(
                Storage::disk('local')->path($path),
                (string) ($preserved['name'] ?? basename($path)),
                $preserved['mime'] ?? null,
                null,
                true,
            );
            $this->files->set('transfer_receipt', $restored);
            $this->convertedFiles = null;
            if (app()->bound('request') && app('request') !== $this) {
                app('request')->files->set('transfer_receipt', $restored);
            }
        }
    }

    protected function failedValidation(Validator $validator): void
    {
        if (! $this->expectsJson() && ! $this->ajax() && $this->hasSession()) {
            $file = $this->file('transfer_receipt');
            if ($file instanceof UploadedFile && $file->isValid() && ! $validator->errors()->has('transfer_receipt')) {
                $previous = $this->session()->get(self::SESSION_RECEIPT_KEY);
                $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
                $stored = $file->storeAs('checkout-draft-receipts', Str::uuid()->toString().'.'.$ext, 'local');
                if (is_string($stored) && $stored !== '') {
                    if (is_array($previous) && ! empty($previous['path']) && $previous['path'] !== $stored) {
                        Storage::disk('local')->delete((string) $previous['path']);
                    }
                    $this->session()->put(self::SESSION_RECEIPT_KEY, [
                        'path' => $stored,
                        'name' => $file->getClientOriginalName(),
                        'mime' => $file->getClientMimeType(),
                    ]);
                }
            }
        }

        parent::failedValidation($validator);
    }

    public function rules(): array
    {
        $bankTransfer = $this->input('payment_method') === 'bank_transfer';

        return [
            'payment_method' => ['required', 'in:card,bank_transfer'],
            'coupon_code' => ['nullable', 'string', 'max:255'],
            'bank_id' => $bankTransfer ? ['required', 'exists:banks,id,is_active,1'] : ['nullable'],
            'transfer_receipt' => $bankTransfer ? ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'] : ['nullable'],
            'remove_transfer_receipt' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'payment_method' => 'طريقة الدفع',
            'coupon_code' => 'كود الخصم',
            'bank_id' => 'اختر البنك للتحويل',
            'transfer_receipt' => 'إيصال التحويل البنكي',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'يرجى اختيار طريقة الدفع للمتابعة.',
            'payment_method.in' => 'طريقة الدفع المحددة غير صالحة.',
            'bank_id.required' => 'يرجى اختيار الحساب البنكي المراد التحويل إليه.',
            'bank_id.exists' => 'الحساب البنكي المختار غير متاح حالياً.',
            'transfer_receipt.required' => 'يرجى إرفاق إيصال التحويل البنكي.',
            'transfer_receipt.file' => 'يجب رفع ملف إيصال صالح.',
            'transfer_receipt.mimes' => 'صيغة الملف غير مدعومة، يرجى رفع ملف بصيغة JPG أو PNG أو PDF.',
            'transfer_receipt.max' => 'حجم إيصال التحويل يجب ألا يتجاوز 10 ميجابايت.',
        ];
    }
}
