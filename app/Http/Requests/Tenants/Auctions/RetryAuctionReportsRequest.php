<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Auctions;

use Illuminate\Foundation\Http\FormRequest;

class RetryAuctionReportsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $auction = $this->route('auction');

        return tenancy()->initialized && ($this->user('tenant')?->can('auctions.edit') ?? false);
    }

    public function rules(): array
    {
        return [];
    }
}
