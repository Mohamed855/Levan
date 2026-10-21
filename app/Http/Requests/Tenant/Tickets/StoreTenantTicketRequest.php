<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Tickets;

use Illuminate\Foundation\Http\FormRequest;

final class StoreTenantTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('tickets.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'in:technical,billing,general,auctions'],
            'description' => ['required', 'string', 'min:10', 'max:10000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'subject' => 'عنوان التذكرة',
            'department' => 'القسم المعني',
            'description' => 'تفاصيل المشكلة / الطلب',
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'يرجى كتابة عنوان للتذكرة.',
            'department.required' => 'يرجى اختيار القسم المعني.',
            'description.required' => 'يرجى توضيح تفاصيل المشكلة أو الاستفسار.',
            'description.min' => 'يجب أن تحتوي تفاصيل التذكرة على 10 أحرف على الأقل.',
        ];
    }
}
