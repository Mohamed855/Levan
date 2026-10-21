<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Properties;

enum PropertyBoundaryDirection: string
{
    case NORTH = 'north';
    case SOUTH = 'south';
    case EAST = 'east';
    case WEST = 'west';
    case NORTHEAST = 'northeast';
    case NORTHWEST = 'northwest';
    case SOUTHEAST = 'southeast';
    case SOUTHWEST = 'southwest';

    public function label(): string
    {
        return match ($this) {
            self::NORTH => 'الشمال',
            self::SOUTH => 'الجنوب',
            self::EAST => 'الشرق',
            self::WEST => 'الغرب',
            self::NORTHEAST => 'الشمال الشرقي',
            self::NORTHWEST => 'الشمال الغربي',
            self::SOUTHEAST => 'الجنوب الشرقي',
            self::SOUTHWEST => 'الجنوب الغربي',
        };
    }
}
