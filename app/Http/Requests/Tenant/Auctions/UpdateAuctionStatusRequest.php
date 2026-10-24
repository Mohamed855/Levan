<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Auctions;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAuctionStatusRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('tenant')?->hasPermissionTo('auctions.edit') ?? false; }
    public function rules(): array { return ['is_active' => ['required', 'boolean']]; }
}
