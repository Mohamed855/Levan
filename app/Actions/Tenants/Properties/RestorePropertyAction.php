<?php

declare(strict_types=1);

namespace App\Actions\Tenants\Properties;

use App\Models\Tenants\Properties\Property;

final class RestorePropertyAction
{
    public function execute(Property $property): Property
    {
        $property->restore();

        return $property->refresh();
    }
}
