<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Auctions;

use App\Rules\PublishedTemplateVersionRule;

final class UpdateAuctionRequest extends StoreAuctionRequest
{
    public function rules(): array
    {
        return array_merge($this->baseRules(), [
            'description' => ['nullable', 'string', 'max:500'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'platform_name' => ['nullable', 'string', 'max:255'],
            'template_versions' => ['nullable', 'array'],
            'template_versions.brochure' => ['nullable', 'integer', new PublishedTemplateVersionRule('brochure')],
            'template_versions.billboard' => ['nullable', 'integer', new PublishedTemplateVersionRule('billboard')],
            'template_versions.social' => ['nullable', 'integer', new PublishedTemplateVersionRule('social')],
        ]);
    }
}