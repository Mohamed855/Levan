<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\AuctionTemplateSamples;

use Illuminate\Http\Resources\Json\JsonResource;

class AuctionTemplateSampleDesignResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'type' => $this->type instanceof \BackedEnum ? $this->type->value : $this->type, 'schema' => $this->schema, 'asset_urls' => []];
    }
}
