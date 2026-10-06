<?php

namespace App\Http\Requests\Tenants\Properties;

use App\Http\Requests\Concerns\WizardFormRequest;

class StorePropertyRequest extends WizardFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assignment_id'   => ['required', 'exists:assignments,id'],
            'property_number' => ['required', 'string', 'max:100'],
            'name'            => ['required', 'string', 'max:255'],
            'type'            => ['required', 'string', 'in:residential_land,villa,residential_building,commercial_building,commercial_land,mixed_use'],
            'city'            => ['required', 'string', 'max:100'],
            'district'        => ['nullable', 'string', 'max:100'],
            'area'            => ['required', 'numeric', 'min:0'],
            'deed_number'     => ['nullable', 'string', 'max:100'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'data_status'     => ['nullable', 'string', 'in:completed,needs_images,incomplete'],
            'status'          => ['nullable', 'string', 'in:available,assigned_to_auction,sold'],
            'main_image'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'notes'           => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'assignment_id.required'   => 'الإسناد التابع له مطلوب.',
            'assignment_id.exists'     => 'الإسناد المختار غير موجود.',
            'property_number.required' => 'رقم العقار مطلوب.',
            'name.required'            => 'اسم العقار مطلوب.',
            'type.required'            => 'نوع العقار مطلوب.',
            'type.in'                  => 'نوع العقار غير صالح.',
            'city.required'            => 'المدينة مطلوبة.',
            'area.required'            => 'المساحة مطلوبة.',
            'area.numeric'             => 'المساحة يجب أن تكون رقماً.',
            'estimated_value.numeric'  => 'القيمة التقديرية يجب أن تكون رقماً.',
            'main_image.image'         => 'صورة العقار يجب أن تكون صورة صالحة.',
            'main_image.max'           => 'حجم الصورة يجب ألا يتجاوز 4 ميجابايت.',
        ];
    }

    public function attributes(): array
    {
        return [
            'assignment_id'   => 'الإسناد',
            'property_number' => 'رقم العقار',
            'name'            => 'اسم العقار',
            'type'            => 'نوع العقار',
            'city'            => 'المدينة',
            'district'        => 'الحي',
            'area'            => 'المساحة',
            'deed_number'     => 'رقم الصك',
            'estimated_value' => 'القيمة التقديرية',
            'data_status'     => 'حالة البيانات',
            'main_image'      => 'صورة العقار',
            'notes'           => 'الملاحظات',
        ];
    }
}
