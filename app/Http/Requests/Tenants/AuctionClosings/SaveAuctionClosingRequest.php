<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\AuctionClosings;

use App\Services\Tenants\Auctions\Reports\AuctionReportInputCapacity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class SaveAuctionClosingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can($this->route('auction')?->closing ? 'auction_closings.edit' : 'auction_closings.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'closing_date' => ['required', 'date'],
            'approval_number' => ['prohibited'],
            'results_narrative' => ['nullable', 'string', 'max:10000'],
            'board_removal_date' => ['nullable', 'date'],
            'board_images_after' => ['nullable', 'array'],
            'board_images_after.*' => ['nullable', 'array', 'max:50'],
            'board_images_after.*.*' => ['file', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
            'remove_board_evidence' => ['nullable', 'array'],
            'remove_board_evidence.*' => ['integer', 'distinct'],
            'results' => ['nullable', 'array'],
            'results.*.status' => ['nullable', Rule::in(['sold', 'unsold', 'pending', ''])],
            'results.*.buyer_name' => ['nullable', 'required_if:results.*.status,sold', 'string', 'max:255'],
            'results.*.sale_price' => ['nullable', 'required_if:results.*.status,sold', 'numeric', 'min:0'],
            'results.*.unsold_reason' => ['nullable', 'required_if:results.*.status,unsold', 'string', 'max:2000'],
            'results.*.result_notes' => ['nullable', 'string', 'max:2000'],
            'results.*.opening_price' => ['nullable', 'numeric', 'min:0'],
            'results.*.participants_count' => ['nullable', 'integer', 'min:0'],
            'results.*.bids_count' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'closing_date' => 'تاريخ الإقفال',
            'approval_number' => 'رقم الموافقة',
            'results_narrative' => 'تفاصيل نتائج المزاد',
            'board_removal_date' => 'تاريخ فك اللوحات',
            'board_images_after' => 'صور فك اللوحات',
            'board_images_after.*' => 'صور فك اللوحات للعقار',
            'board_images_after.*.*' => 'صورة فك اللوحة',
            'remove_board_evidence' => 'حذف صور فك اللوحات',
            'remove_board_evidence.*' => 'معرف صورة فك اللوحة',
            'results' => 'نتائج العقارات',
            'results.*.status' => 'نتيجة العقار',
            'results.*.buyer_name' => 'اسم من تمت الترسية عليه',
            'results.*.sale_price' => 'قيمة الترسية',
            'results.*.unsold_reason' => 'سبب عدم البيع',
            'results.*.result_notes' => 'ملاحظات النتيجة',
            'results.*.opening_price' => 'سعر الافتتاح',
            'results.*.participants_count' => 'عدد المشاركين',
            'results.*.bids_count' => 'عدد المزايدات',
        ];
    }

    public function messages(): array
    {
        return [
            'closing_date.required' => 'حقل تاريخ الإقفال مطلوب.',
            'closing_date.date' => 'حقل تاريخ الإقفال يجب أن يكون تاريخاً صالحاً.',
            'board_removal_date.date' => 'حقل تاريخ فك اللوحات يجب أن يكون تاريخاً صالحاً.',
            'board_images_after.*.*.mimes' => 'يجب أن تكون صورة فك اللوحة بصيغة: png, jpg, jpeg, webp.',
            'board_images_after.*.*.max' => 'حجم صورة فك اللوحة يجب ألا يتجاوز 10 ميجابايت.',
            'results.*.buyer_name.required_if' => 'اسم من تمت الترسية عليه مطلوب عندما تكون نتيجة العقار مباع.',
            'results.*.sale_price.required_if' => 'قيمة الترسية مطلوبة عندما تكون نتيجة العقار مباع.',
            'results.*.sale_price.min' => 'قيمة الترسية يجب أن تكون أكبر من أو تساوي صفر.',
            'results.*.unsold_reason.required_if' => 'سبب عدم البيع مطلوب عندما تكون نتيجة العقار غير مباع.',
            'results.*.opening_price.min' => 'سعر الافتتاح يجب أن يكون رقماً غير سالب.',
            'results.*.participants_count.min' => 'عدد المشاركين يجب أن يكون رقماً غير سالب.',
            'results.*.bids_count.min' => 'عدد المزايدات يجب أن يكون رقماً غير سالب.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $value = $this->input('results_narrative');
            if (is_string($value) && ! app(AuctionReportInputCapacity::class)->fits('results_narrative', $value)) {
                $validator->errors()->add('results_narrative', 'النص أطول من المساحة المتاحة في التقرير. اختصر النص ثم احفظ.');
            }
        }];
    }
}
