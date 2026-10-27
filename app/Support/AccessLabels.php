<?php

declare(strict_types=1);

namespace App\Support;

final class AccessLabels
{
    public static function role(?string $name): string
    {
        return self::label('roles', $name);
    }

    public static function permission(?string $name): string
    {
        return self::label('permissions', $name);
    }

    public static function section(?string $name): string
    {
        return self::label('permissions.sections', $name);
    }

    public static function guard(?string $name): string
    {
        return self::label('roles.guards', $name);
    }

    public static function roleOptions(iterable $roles, string $key = 'id'): array
    {
        $options = [];
        foreach ($roles as $role) {
            $options[$role->{$key}] = self::role($role->name);
        }

        return $options;
    }

    // Normalize the translated form label before validation/persistence.
    // Existing role names are never changed by a display translation.
    public static function roleKey(mixed $value, ?string $current = null): mixed
    {
        if (! is_string($value)) {
            return $value;
        }
        $value = trim($value);
        if ($current !== null && $value === self::role($current)) {
            return $current;
        }
        foreach (['ar', 'en'] as $locale) {
            foreach ((array) trans('roles', [], $locale) as $key => $label) {
                if (is_string($label) && $label === $value) {
                    return $key;
                }
            }
        }

        return $value;
    }

    private static function label(string $group, ?string $name): string
    {
        if ($name === null || $name === '') {
            return '—';
        }
        $key = $group.'.'.$name;
        $label = __($key, [], 'ar');

        return is_string($label) && $label !== $key ? $label : $name;
    }
}
