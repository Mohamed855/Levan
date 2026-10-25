<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\AuctionTemplateSamples;

use Illuminate\Http\Resources\Json\JsonResource;

class AuctionTemplateSampleCardResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'type' => $this->type instanceof \BackedEnum ? $this->type->value : $this->type, 'thumbnail_url' => $this->preview_image_url ?: asset('static/brand/logos/logo.svg')];
    }
}
