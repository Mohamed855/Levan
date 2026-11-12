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
            'board_removal_date' => ['required', 'date'],
            'board_images_after' => ['nullable', 'array'],
            'board_images_after.*' => ['array', 'max:50'],
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
