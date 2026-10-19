<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\AuctionTemplates;

use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;

final class UpdateAuctionTemplateStatusRequest extends AdminAuthenticatedRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('auction_templates.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'status'    => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
