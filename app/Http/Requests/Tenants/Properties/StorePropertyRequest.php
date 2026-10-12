<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Properties;

use App\Enums\Tenants\Properties\PropertyBoundaryDirection;
use App\Enums\Tenants\Properties\PropertyOwnershipType;
use App\Enums\Tenants\Properties\PropertyType;
use App\Enums\Tenants\Properties\PropertyUsageClassification;
use App\Http\Requests\Concerns\WizardFormRequest;
use Illuminate\Validation\Rule;

class StorePropertyRequest extends WizardFormRequest
{
    public function validationData(): array
    {
        $data = parent::validationData();
        if (is_string($data['boundary_geojson'] ?? null)) {
            $data['boundary_geojson'] = json_decode($data['boundary_geojson'], true);
        }

        return $data;
    }

    public function authorize(): bool
    {
        return $this->user('tenant')?->can('properties.create') ?? false;
    }

    public function rules(): array
    {
        return [
            // Step 1: البيانات الأساسية
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(PropertyType::class)],
            'usage_classification' => ['required', Rule::enum(PropertyUsageClassification::class)],
            'area' => ['required', 'numeric', 'min:0.01'],
            'is_active' => ['required', 'boolean'],
            'short_description' => ['required', 'string', 'max:500'],
            'property_number' => ['nullable', 'string', 'max:100'],

            // Step 2: الموقع والحدود
            'city' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'administrative_region' => ['required', 'string', 'max:100'],
            'plan_number' => ['required', 'string', 'max:100'],
            'parcel_number' => ['required', 'string', 'max:100'],
            'google_maps_url' => ['required', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'boundary_geojson' => ['required', 'array'],
            'boundary_geojson.type' => ['required', 'in:Polygon'],
            'boundary_geojson.coordinates' => ['required', 'array', 'size:1'],
            'boundary_geojson.coordinates.0' => ['required', 'array', 'min:4'],
            'boundary_geojson.coordinates.0.*' => ['required', 'array', 'size:2'],
            'boundary_geojson.coordinates.0.*.0' => ['required', 'numeric', 'between:-180,180'],
            'boundary_geojson.coordinates.0.*.1' => ['required', 'numeric', 'between:-90,90'],
            'boundaries' => ['required', 'array', 'min:1'],
            'boundaries.*.direction' => ['required', Rule::enum(PropertyBoundaryDirection::class)],
            'boundaries.*.border_type' => ['required', 'string', 'max:100'],
            'boundaries.*.border_description' => ['required', 'string', 'max:255'],
            'boundaries.*.length' => ['required', 'numeric', 'min:0'],

            // Step 3: الملكية والتفاصيل
            'assignment_letter_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'deed_number' => ['required', 'string', 'max:100'],
            'deed_date' => ['required', 'date'],
            'issuing_authority' => ['required', 'string', 'max:255'],
            'ownership_type' => ['required', Rule::enum(PropertyOwnershipType::class)],
            'owners' => ['required', 'array', 'min:1'],
            'owners.*.owner_name' => ['required', 'string', 'max:255'],
            'owners.*.national_id_or_cr' => ['required', 'string', 'max:100'],
            'agent_name' => ['nullable', 'string', 'max:255'],
            'agent_national_id' => ['nullable', 'string', 'max:100'],
            'agency_number' => ['nullable', 'string', 'max:100'],
            'agency_date' => ['nullable', 'date'],
            'units' => ['nullable', 'array'],
            'units.*.unit_number' => ['nullable', 'string', 'max:100'],
            'units.*.unit_type' => ['nullable', 'string', 'max:100'],
            'units.*.area' => ['nullable', 'numeric', 'min:0'],
            'units.*.current_use' => ['nullable', 'string', 'max:100'],
            'units.*.status' => ['nullable', 'string', 'max:100'],

            // Step 4: المستندات والصور
            'deed_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'cadastral_plan' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'building_permit' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'coordinates_statement' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'additional_documents' => ['nullable', 'array'],
            'additional_documents.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'main_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'property_images' => ['nullable', 'array'],
            'property_images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],

            // Step 5: المراجعة والملاحظات
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            // Step 1
            'name.required' => 'اسم العقار مطلوب.',
            'type.required' => 'نوع العقار مطلوب.',
            'usage_classification.required' => 'تصنيف الاستخدام مطلوب.',
            'area.required' => 'المساحة مطلوبة.',
            'area.numeric' => 'المساحة يجب أن تكون رقماً.',
            'is_active.required' => 'حالة العقار مطلوبة.',
            'short_description.required' => 'الوصف المختصر للعقار مطلوب.',
            'short_description.max' => 'الوصف المختصر يجب ألا يتجاوز 500 حرف.',

            // Step 2
            'city.required' => 'المدينة مطلوبة.',
            'district.required' => 'الحي مطلوب.',
            'administrative_region.required' => 'المنطقة الإدارية مطلوبة.',
            'plan_number.required' => 'رقم المخطط مطلوب.',
            'parcel_number.required' => 'رقم القطعة مطلوب.',
            'google_maps_url.required' => 'رابط الموقع من Google Maps مطلوب.',
            'boundary_geojson.required' => 'يجب رسم حدود العقار على الخريطة.',
            'boundary_geojson.coordinates.0.min' => 'يجب أن تحتوي حدود العقار على ثلاث نقاط على الأقل.',
            'boundaries.required' => 'يجب إدخال حدود العقار وأطوالها.',
            'boundaries.min' => 'يجب إدخال حد واحد على الأقل للعقار.',
            'boundaries.*.direction.required' => 'جهة الحد مطلوبة.',
            'boundaries.*.border_type.required' => 'نوع الحد مطلوب.',
            'boundaries.*.border_description.required' => 'وصف الحد مطلوب.',
            'boundaries.*.length.required' => 'طول الحد مطلوب.',
            'boundaries.*.length.numeric' => 'طول الحد يجب أن يكون رقماً.',

            // Step 3
            'deed_number.required' => 'رقم الصك مطلوب.',
            'deed_date.required' => 'تاريخ الصك مطلوب.',
            'issuing_authority.required' => 'جهة إصدار الصك مطلوبة.',
            'ownership_type.required' => 'نوع الملكية مطلوب.',
            'owners.required' => 'يجب إدخال مالك واحد على الأقل.',
            'owners.min' => 'يجب إدخال مالك واحد على الأقل.',
            'owners.*.owner_name.required' => 'اسم المالك مطلوب.',
            'owners.*.national_id_or_cr.required' => 'رقم الهوية أو السجل التجاري مطلوب.',
            'assignment_letter_image.image' => 'صورة الإسناد يجب أن تكون صورة صالحة.',
            'assignment_letter_image.max' => 'حجم صورة الإسناد يجب ألا يتجاوز 4 ميجابايت.',

            // Step 4
            'deed_document.required' => 'صك الملكية مطلوب.',
            'deed_document.file' => 'صك الملكية يجب أن يكون ملفاً صالحاً (PDF أو صورة).',
            'cadastral_plan.required' => 'مخطط العقار مطلوب.',
            'coordinates_statement.required' => 'كشف الإحداثيات مطلوب.',
            'main_image.required' => 'الصورة الرئيسية للعقار مطلوبة.',
            'main_image.image' => 'الصورة الرئيسية يجب أن تكون صورة صالحة.',
            'main_image.max' => 'حجم الصورة الرئيسية يجب ألا يتجاوز 4 ميجابايت.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم العقار',
            'type' => 'نوع العقار',
            'usage_classification' => 'تصنيف الاستخدام',
            'area' => 'المساحة',
            'is_active' => 'حالة العقار',
            'short_description' => 'الوصف المختصر',
            'city' => 'المدينة',
            'district' => 'الحي',
            'administrative_region' => 'المنطقة الإدارية',
            'plan_number' => 'رقم المخطط',
            'parcel_number' => 'رقم القطعة',
            'google_maps_url' => 'رابط الموقع من Google Maps',
            'boundary_geojson' => 'الرسم الجغرافي لحدود العقار',
            'boundaries' => 'حدود العقار',
            'deed_number' => 'رقم الصك',
            'deed_date' => 'تاريخ الصك',
            'issuing_authority' => 'جهة إصدار الصك',
            'ownership_type' => 'نوع الملكية',
            'owners' => 'ملاك العقار',
            'agent_name' => 'اسم الوكيل',
            'agent_national_id' => 'رقم هوية الوكيل',
            'agency_number' => 'رقم الوكالة',
            'agency_date' => 'تاريخ الوكالة',
            'units' => 'وحدات العقار',
            'deed_document' => 'صك الملكية',
            'cadastral_plan' => 'مخطط العقار',
            'building_permit' => 'رخصة البناء',
            'coordinates_statement' => 'كشف الإحداثيات',
            'additional_documents' => 'المستندات الإضافية',
            'main_image' => 'الصورة الرئيسية للعقار',
            'property_images' => 'صور العقار',
            'notes' => 'الملاحظات',
        ];
    }
}
