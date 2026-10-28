<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Models\Tenants\Auctions\Auction;
use Illuminate\Validation\Rule;

trait AuctionReportEvidenceRules
{
    protected function reportEvidenceRules(): array
    {
        $auction = $this->route('auction');
        $owned = $auction instanceof Auction ? array_map('strval', $auction->attachments()->pluck('id')->all()) : [];
        $rules = ['marketing_evidence_meta' => ['nullable', 'array', 'max:500'], 'report_attachments' => ['nullable', Rule::array($owned), 'max:500']];
        foreach (['marketing_evidence_meta', 'report_attachments'] as $prefix) {
            $rules[$prefix.'.*'] = ['array:section,channel,kind,display_name,link_role,fit,focal_x,focal_y,display_order,property_id'];
            $rules[$prefix.'.*.section'] = ['nullable', Rule::in(config('auction-report-inputs.sections'))];
            $rules[$prefix.'.*.channel'] = ['nullable', Rule::in(config('auction-report-inputs.channels'))];
            $rules[$prefix.'.*.kind'] = ['nullable', Rule::in(config('auction-report-inputs.kinds'))];
            $rules[$prefix.'.*.display_name'] = ['nullable', 'string', 'max:255'];
            $rules[$prefix.'.*.link_role'] = ['nullable', Rule::in(config('auction-report-inputs.link_roles'))];
            $rules[$prefix.'.*.fit'] = ['nullable', Rule::in(['contain', 'cover'])];
            $rules[$prefix.'.*.display_order'] = ['nullable', 'integer', 'min:0', 'max:100000'];
            $rules[$prefix.'.*.property_id'] = ['nullable', Rule::in($this->selectedPropertyKeys())];
            foreach (['focal_x', 'focal_y'] as $axis) {
                $rules[$prefix.'.*.'.$axis] = ['nullable', 'numeric', 'between:0,100'];
            }
        }

        return $rules;
    }
}
