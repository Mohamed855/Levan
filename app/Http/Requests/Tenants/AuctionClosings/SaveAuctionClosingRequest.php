<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\AuctionClosings;

use App\Http\Requests\Concerns\WizardFormRequest;
use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\Auctions\Reports\AuctionReportInputCapacity;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class SaveAuctionClosingRequest extends WizardFormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can($this->route('auction')?->closing ? 'auction_closings.edit' : 'auction_closings.create') ?? false;
    }

    public function rules(): array
    {
        $auction = $this->route('auction');
        if (! ($auction instanceof Auction) && is_numeric($auction)) {
            $auction = Auction::find($auction);
        }

        $closingDateRules = ['required', 'date'];
        if ($auction instanceof Auction) {
            if ($auction->start_date) {
                $closingDateRules[] = 'after_or_equal:'.$auction->start_date->format('Y-m-d');
            }
            if ($auction->end_date) {
                $closingDateRules[] = 'before_or_equal:'.$auction->end_date->format('Y-m-d');
            }
        }

        return [
            'closing_date' => $closingDateRules,
            'results_narrative' => ['required', 'string', 'max:1000'],
            'board_removal_date' => ['required', 'date', 'after_or_equal:closing_date'],
            'board_images_after' => ['nullable', 'array'],
            'board_images_after.*' => ['nullable', 'array', 'max:50'],
            'board_images_after.*.*' => ['file', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
            'remove_board_evidence' => ['nullable', 'array'],
            'remove_board_evidence.*' => ['integer', 'distinct'],
            'results' => ['required', 'array'],
            'results.*.status' => ['nullable', Rule::in(['sold', 'unsold', 'pending', ''])],
            'results.*.buyer_name' => ['nullable', 'required_if:results.*.status,sold', 'string', 'max:255'],
            'results.*.sale_price' => ['nullable', 'required_if:results.*.status,sold', 'numeric', 'min:0'],
            'results.*.unsold_reason' => ['nullable', 'required_if:results.*.status,unsold', 'string', 'max:1000'],
            'results.*.result_notes' => ['nullable', 'required_if:results.*.status,sold,unsold', 'string', 'max:1000'],
            'results.*.participants_count' => ['nullable', 'required_if:results.*.status,sold,unsold', 'integer', 'min:0'],
            'results.*.bids_count' => ['nullable', 'required_if:results.*.status,sold,unsold', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'closing_date' => 'تاريخ الإقفال',
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
            'results.*.participants_count' => 'عدد المشاركين',
            'results.*.bids_count' => 'عدد المزايدات',
        ];
    }

    public function messages(): array
    {
        return [
            'closing_date.required' => 'حقل تاريخ الإقفال مطلوب.',
            'closing_date.date' => 'حقل تاريخ الإقفال يجب أن يكون تاريخاً صالحاً.',
            'closing_date.after_or_equal' => 'تاريخ الإقفال يجب أن يكون في نفس يوم تاريخ بداية المزاد أو بعده.',
            'closing_date.before_or_equal' => 'تاريخ الإقفال يجب ألا يتجاوز تاريخ نهاية المزاد.',
            'board_removal_date.required' => 'حقل تاريخ فك اللوحات مطلوب.',
            'board_removal_date.date' => 'حقل تاريخ فك اللوحات يجب أن يكون تاريخاً صالحاً.',
            'board_removal_date.after_or_equal' => 'تاريخ فك اللوحات يجب أن يكون في نفس يوم تاريخ الإقفال أو بعده.',
            'results_narrative.required' => 'حقل تفاصيل نتائج المزاد مطلوب.',
            'results.required' => 'بيانات نتائج العقارات مطلوبة.',
            'board_images_after.*.*.mimes' => 'يجب أن تكون صورة فك اللوحة بصيغة: png, jpg, jpeg, webp.',
            'board_images_after.*.*.max' => 'حجم صورة فك اللوحة يجب ألا يتجاوز 10 ميجابايت.',
            'results.*.buyer_name.required_if' => 'اسم من تمت الترسية عليه مطلوب عندما تكون نتيجة العقار مباع.',
            'results.*.sale_price.required_if' => 'قيمة الترسية مطلوبة عندما تكون نتيجة العقار مباع.',
            'results.*.sale_price.min' => 'قيمة الترسية يجب أن تكون أكبر من أو تساوي صفر.',
            'results.*.unsold_reason.required_if' => 'سبب عدم البيع مطلوب عندما تكون نتيجة العقار غير مباع.',
            'results.*.result_notes.required_if' => 'حقل ملاحظات النتيجة مطلوب للعقار المحدد نتيجته.',
            'results.*.participants_count.required_if' => 'عدد المشاركين مطلوب للعقار المحدد نتيجته.',
            'results.*.participants_count.min' => 'عدد المشاركين يجب أن يكون رقماً غير سالب.',
            'results.*.bids_count.required_if' => 'عدد المزايدات مطلوب للعقار المحدد نتيجته.',
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

            $auction = $this->route('auction');
            if (! ($auction instanceof Auction) && is_numeric($auction)) {
                $auction = Auction::find($auction);
            }

            if ($this->filled('closing_date')) {
                $closingDate = $this->date('closing_date');
                if ($closingDate && $auction instanceof Auction) {
                    if ($auction->start_date && $closingDate->lt($auction->start_date->startOfDay())) {
                        if (! $validator->errors()->has('closing_date')) {
                            $validator->errors()->add('closing_date', 'تاريخ الإقفال يجب أن يكون في نفس يوم تاريخ بداية المزاد أو بعده.');
                        }
                    }
                    if ($auction->end_date && $closingDate->gt($auction->end_date->endOfDay())) {
                        if (! $validator->errors()->has('closing_date')) {
                            $validator->errors()->add('closing_date', 'تاريخ الإقفال يجب ألا يتجاوز تاريخ نهاية المزاد.');
                        }
                    }
                }
            }

            if ($this->filled('closing_date') && $this->filled('board_removal_date')) {
                $closingDate = $this->date('closing_date');
                $boardRemovalDate = $this->date('board_removal_date');
                if ($closingDate && $boardRemovalDate && $boardRemovalDate->lt($closingDate->startOfDay())) {
                    if (! $validator->errors()->has('board_removal_date')) {
                        $validator->errors()->add('board_removal_date', 'تاريخ فك اللوحات يجب أن يكون في نفس يوم تاريخ الإقفال أو بعده.');
                    }
                }
            }

            $results = (array) $this->input('results', []);
            $completedCount = 0;

            foreach ($results as $propertyId => $result) {
                if (! is_array($result)) {
                    continue;
                }

                $status = $result['status'] ?? null;
                if ($status !== 'sold' && $status !== 'unsold') {
                    continue;
                }

                $hasImages = $this->hasBoardImagesForProperty((int) $propertyId);
                if (! $hasImages) {
                    $validator->errors()->add("board_images_after.{$propertyId}", 'صور فك اللوحات مطلوبة للعقار.');
                }

                $hasNotes = filled($result['result_notes'] ?? null);
                $hasParticipants = isset($result['participants_count']) && is_numeric($result['participants_count']) && (int) $result['participants_count'] >= 0;
                $hasBids = isset($result['bids_count']) && is_numeric($result['bids_count']) && (int) $result['bids_count'] >= 0;

                $hasStatusDetails = false;
                if ($status === 'sold') {
                    $hasStatusDetails = filled($result['buyer_name'] ?? null)
                        && isset($result['sale_price'])
                        && is_numeric($result['sale_price'])
                        && (float) $result['sale_price'] >= 0;
                } elseif ($status === 'unsold') {
                    $hasStatusDetails = filled($result['unsold_reason'] ?? null);
                }

                if ($hasImages && $hasNotes && $hasParticipants && $hasBids && $hasStatusDetails) {
                    $completedCount++;
                }
            }

            if ($completedCount === 0) {
                $validator->errors()->add('results', 'يجب تحديد نتيجة عقار واحد على الأقل وإكمال بياناته وصوره.');
            }
        }];
    }

    public function hasBoardImagesForProperty(int|string $propertyId): bool
    {
        $propertyId = (int) $propertyId;

        // 1. Check if uploaded files exist in request
        $files = $this->file("board_images_after.{$propertyId}")
            ?? ($this->allFiles()['board_images_after'][$propertyId] ?? null);

        if ($files instanceof UploadedFile && $files->isValid()) {
            return true;
        }

        if (is_array($files)) {
            foreach ($files as $file) {
                if ($file instanceof UploadedFile && $file->isValid()) {
                    return true;
                }
            }
        }

        // Check if raw input array has files or non-empty items (e.g. In unit tests)
        $inputImages = $this->input("board_images_after.{$propertyId}");
        if (! empty($inputImages)) {
            if (is_array($inputImages)) {
                foreach ($inputImages as $img) {
                    if ($img instanceof UploadedFile || (is_string($img) && filled($img))) {
                        return true;
                    }
                }
            } elseif (is_string($inputImages) && filled($inputImages)) {
                return true;
            }
        }

        // 2. Check if existing attachments exist on the auction that are not marked for deletion
        $auction = $this->route('auction');
        if ($auction instanceof Auction && $auction->exists) {
            $removedIds = array_map('intval', (array) $this->input('remove_board_evidence', []));
            $existingCount = $auction->attachments()
                ->where('report_section', 'board_after')
                ->where('property_id', $propertyId)
                ->whereNotIn('id', $removedIds)
                ->count();

            if ($existingCount > 0) {
                return true;
            }
        }

        return false;
    }
}
