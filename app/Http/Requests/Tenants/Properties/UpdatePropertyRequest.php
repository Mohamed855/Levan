<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Properties;

use App\Enums\Tenants\Properties\PropertyBoundaryDirection;
use App\Enums\Tenants\Properties\PropertyBoundaryType;
use App\Enums\Tenants\Properties\PropertyOwnershipType;
use App\Enums\Tenants\Properties\PropertyType;
use App\Enums\Tenants\Properties\PropertyUnitStatus;
use App\Enums\Tenants\Properties\PropertyUnitType;
use App\Enums\Tenants\Properties\PropertyUnitUsage;
use App\Enums\Tenants\Properties\PropertyUsageClassification;
use App\Http\Requests\Concerns\WizardFormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class UpdatePropertyRequest extends WizardFormRequest
{
    public function validationData(): array
    {
        $data = parent::validationData();
        if (is_string($data['boundary_geojson'] ?? null)) {
            $data['boundary_geojson'] = json_decode($data['boundary_geojson'], true);
        }

        if (array_key_exists('is_active', $data)) {
            if ($data['is_active'] === 'نشط' || $data['is_active'] === '1' || $data['is_active'] === 1 || $data['is_active'] === true) {
                $data['is_active'] = 1;
            } elseif ($data['is_active'] === 'غير نشط' || $data['is_active'] === '0' || $data['is_active'] === 0 || $data['is_active'] === false) {
                $data['is_active'] = 0;
            }
        }

        return $data;
    }

    public function authorize(): bool
    {
        return $this->user('tenant')?->can('properties.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            // Step 1: البيانات الأساسية
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(PropertyType::class)],
            'usage_classification' => ['required', Rule::enum(PropertyUsageClassification::class)],
            'area' => ['required', 'numeric', 'min:0.01'],
            'is_active' => ['nullable', 'boolean'],
            'short_description' => ['required', 'string', 'max:500'],
            'property_number' => ['nullable', 'string', 'max:100'],

            // Step 2: الموقع والحدود
            'city' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'administrative_region' => ['required', 'string', 'max:100'],
            'plan_number' => ['required', 'string', 'max:100', 'regex:/^\d+$/'],
            'parcel_number' => ['required', 'string', 'max:100', 'regex:/^\d+$/'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'aerial_image' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value instanceof UploadedFile) {
                        if (! $value->isValid() || ! in_array($value->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'])) {
                            $fail('يجب أن تكون صورة العقار المحددة صورة صالحة (JPG, PNG, WEBP).');

                            return;
                        }

                        if ($value->getSize() > 10240 * 1024) {
                            $fail('حجم صورة العقار المحددة يجب ألا يتجاوز 10 ميجابايت.');

                            return;
                        }
                    } elseif (is_string($value)) {
                        if (! str_starts_with($value, 'data:image/') && ! str_starts_with($value, 'properties/')) {
                            $fail('صورة العقار المحددة غير صالحة.');

                            return;
                        }

                        if (str_starts_with($value, 'data:image/')) {
                            $commaPos = strpos($value, ',');
                            if ($commaPos !== false) {
                                $base64 = str_replace(["\r", "\n", ' '], '', substr($value, $commaPos + 1));
                                $decodedLength = (int) floor((strlen(rtrim($base64, '=')) * 3) / 4);
                                if ($decodedLength > 10240 * 1024) {
                                    $fail('حجم صورة العقار المحددة يجب ألا يتجاوز 10 ميجابايت.');

                                    return;
                                }
                            }
                        }
                    }
                },
            ],
            'boundary_geojson' => ['required', 'array'],
            'boundary_geojson.type' => ['required', 'in:Polygon'],
            'boundary_geojson.coordinates' => ['required', 'array', 'size:1'],
            'boundary_geojson.coordinates.0' => ['required', 'array', 'min:4'],
            'boundary_geojson.coordinates.0.*' => ['required', 'array', 'size:2'],
            'boundary_geojson.coordinates.0.*.0' => ['required', 'numeric'],
            'boundary_geojson.coordinates.0.*.1' => ['required', 'numeric'],
            'boundaries' => ['nullable', 'array'],
            'boundaries.*.direction' => ['nullable', Rule::enum(PropertyBoundaryDirection::class)],
            'boundaries.*.border_type' => ['nullable', Rule::enum(PropertyBoundaryType::class)],
            'boundaries.*.border_description' => ['nullable', 'string', 'max:255'],
            'boundaries.*.length' => ['nullable', 'numeric', 'min:0'],

            // Step 3: الملكية والتفاصيل
            'assignment_letter_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'deed_number' => ['required', 'string', 'max:100', 'regex:/^\d+$/'],
            'deed_date' => ['required', 'date'],
            'issuing_authority' => ['required', 'string', 'max:255'],
            'ownership_type' => ['required', Rule::enum(PropertyOwnershipType::class)],
            'owners' => ['nullable', 'array'],
            'owners.*.owner_name' => ['nullable', 'string', 'max:255'],
            'owners.*.national_id_or_cr' => ['nullable', 'string', 'max:100', 'regex:/^\d+$/'],
            'agent_name' => ['nullable', 'string', 'max:255'],
            'agent_national_id' => ['nullable', 'string', 'max:100', 'regex:/^\d+$/'],
            'agency_number' => ['nullable', 'string', 'max:100', 'regex:/^\d+$/'],
            'agency_date' => ['nullable', 'date'],
            'units' => ['nullable', 'array'],
            'units.*.unit_number' => ['nullable', 'string', 'max:100'],
            'units.*.unit_type' => ['nullable', Rule::enum(PropertyUnitType::class)],
            'units.*.area' => ['nullable', 'numeric', 'min:0'],
            'units.*.current_use' => ['nullable', Rule::enum(PropertyUnitUsage::class)],
            'units.*.status' => ['nullable', Rule::enum(PropertyUnitStatus::class)],

            // Step 4: المستندات والصور
            'deed_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'cadastral_plan' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'building_permit' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'coordinates_statement' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'additional_documents' => ['nullable', 'array'],
            'additional_documents.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'main_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'property_images' => ['nullable', 'array'],
            'property_images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],

            // Step 5: المراجعة والملاحظات
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم العقار مطلوب.',
            'type.required' => 'نوع العقار مطلوب.',
            'usage_classification.required' => 'تصنيف الاستخدام مطلوب.',
            'area.required' => 'المساحة مطلوبة.',
            'short_description.required' => 'الوصف المختصر للعقار مطلوب.',
            'city.required' => 'المدينة مطلوبة.',
            'district.required' => 'الحي مطلوب.',
            'administrative_region.required' => 'المنطقة الإدارية مطلوبة.',
            'plan_number.required' => 'رقم المخطط مطلوب.',
            'parcel_number.required' => 'رقم القطعة مطلوب.',
            'boundary_geojson.required' => 'برجاء تحديد حدود العقار علي صورة العقار.',
            'boundary_geojson.coordinates.0.min' => 'برجاء تحديد حدود العقار علي صورة العقار.',
            'deed_number.required' => 'رقم الصك مطلوب.',
            'deed_date.required' => 'تاريخ الصك مطلوب.',
            'issuing_authority.required' => 'جهة إصدار الصك مطلوبة.',
            'ownership_type.required' => 'نوع الملكية مطلوب.',
            'main_image.image' => 'الصورة الرئيسية يجب أن تكون صورة صالحة.',
            'main_image.max' => 'حجم الصورة الرئيسية يجب ألا يتجاوز 10 ميجابايت.',
            'assignment_letter_image.image' => 'صورة الإسناد يجب أن تكون صورة صالحة.',
            'assignment_letter_image.max' => 'حجم صورة الإسناد يجب ألا يتجاوز 10 ميجابايت.',
            'property_images.*.max' => 'حجم كل صورة إضافية يجب ألا يتجاوز 10 ميجابايت.',
            'additional_documents.*.max' => 'حجم كل مستند إضافي يجب ألا يتجاوز 10 ميجابايت.',
            'deed_document.max' => 'حجم صك الملكية يجب ألا يتجاوز 10 ميجابايت.',
            'cadastral_plan.max' => 'حجم مخطط العقار يجب ألا يتجاوز 10 ميجابايت.',
            'coordinates_statement.max' => 'حجم كشف الإحداثيات يجب ألا يتجاوز 10 ميجابايت.',
            'building_permit.max' => 'حجم رخصة البناء يجب ألا يتجاوز 10 ميجابايت.',
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
            'aerial_image' => 'صورة العقار المحددة',
            'boundary_geojson' => 'تحديد حدود العقار على الصورة',
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
