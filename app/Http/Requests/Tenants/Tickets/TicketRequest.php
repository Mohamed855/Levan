<?php

namespace App\Http\Requests\Tenants\Tickets;

use App\Http\Requests\Concerns\WizardFormRequest;

class TicketRequest extends WizardFormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('tickets.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'title'                 => 'required|string|max:255',
            'description'           => 'required|string',
            'ticketing_sections_id' => 'required|integer',
            'attachments'           => 'nullable|array',
            'attachments.*'         => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'                    => 'عنوان التذكرة مطلوب',
            'description.required'              => 'وصف المشكلة مطلوب',
            'ticketing_sections_id.required'    => 'القسم مطلوب',
            'attachments.*.max'                 => 'حجم الملف يجب ألا يتجاوز 10 ميجابايت',
            'attachments.*.mimes'               => 'نوع الملف غير مدعوم',
        ];
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user('tenant');

        if (! $user) {
            return;
        }

        $this->merge([
            'customer_name'     => $user->name,
            'customer_email'    => $user->email,
            'customer_phone'    => $user->phone,
            'tenant_user_id'    => $user->id,
        ]);
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        return array_merge($validated, [
            'customer_name'     => $this->customer_name,
            'customer_email'    => $this->customer_email,
            'customer_phone'    => $this->customer_phone,
            'tenant_user_id'    => $this->tenant_user_id,
        ]);
    }
}
