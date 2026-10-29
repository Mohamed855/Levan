<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\Auctions;

use Illuminate\Foundation\Http\FormRequest;

class RetryAuctionReportsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $auction = $this->route('auction');

        return $this->user('tenant')?->can('update', $auction) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}

