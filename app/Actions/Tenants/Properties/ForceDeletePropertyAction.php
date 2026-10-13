<?php

declare(strict_types=1);

namespace App\Actions\Tenants\Properties;

use App\Models\Tenants\Properties\Property;
use App\Services\Tenants\Properties\PropertyService;

final class ForceDeletePropertyAction
{
    public function __construct(private readonly PropertyService $propertyService) {}

    public function execute(Property $property): bool
    {
        return $this->propertyService->forceDeleteProperty($property);
    }
}
