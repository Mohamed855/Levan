<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\ContentManagement;

use App\Http\Requests\Concerns\WizardFormRequest;

class StorePortfolioRequest extends WizardFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم المحفظة مطلوب',
            'name.string' => 'اسم المحفظة يجب أن يكون نص',
            'name.max' => 'اسم المحفظة لا يمكن أن يتجاوز 255 حرف',
            'logo.image' => 'الشعار يجب أن يكون صورة',
            'logo.mimes' => 'الشعار يجب أن يكون من نوع: jpeg, png, jpg, gif',
            'status.required' => 'حالة المحفظة مطلوبة',
        ];
    }
}
