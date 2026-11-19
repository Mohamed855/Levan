<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\AuctionEstablishments;

use Illuminate\Foundation\Http\FormRequest;

final class ApproveAuctionEstablishmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('tenant');

        return $user?->can('pending_auctions.view') && $user->can('auction_establishment_requests.approve');
    }

    public function rules(): array
    {
        return ['approval_number' => ['required', 'string', 'max:255', 'regex:/\S/u']];
    }

    public function attributes(): array
    {
        return ['approval_number' => 'رقم الموافقة على إقامة المزاد'];
    }
}
