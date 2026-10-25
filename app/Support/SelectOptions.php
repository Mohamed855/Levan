<?php

declare(strict_types=1);

namespace App\Support;

use BackedEnum;
use InvalidArgumentException;
use UnitEnum;

final class SelectOptions
{
    /**
     * @param class-string<UnitEnum> $enumClass
     * @return array<string|int, string>
     */
    public static function fromEnum(string $enumClass): array
    {
        if (! enum_exists($enumClass) || ! is_subclass_of($enumClass, BackedEnum::class)) {
            throw new InvalidArgumentException("{$enumClass} must be a backed enum.");
        }

        $options = [];

        foreach ($enumClass::cases() as $case) {
            $options[$case->value] = method_exists($case, 'label')
                ? $case->label()
                : str($case->name)->headline()->toString();
        }

        return $options;
    }
}
