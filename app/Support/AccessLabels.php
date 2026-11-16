<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

final class AccessLabels
{
    public static function role(mixed $roleOrName): string
    {
        if (is_object($roleOrName)) {
            if (! empty($roleOrName->label)) {
                return (string) $roleOrName->label;
            }

            return self::label('roles', $roleOrName->name ?? null);
        }

        if ($roleOrName === null || $roleOrName === '') {
            return '—';
        }

        return self::label('roles', (string) $roleOrName);
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
            $options[$role->{$key}] = self::role($role);
        }

        return $options;
    }

    public static function slugify(string $value): string
    {
        $slug = Str::slug($value, '_');
        if ($slug === '' || ! preg_match('/^[A-Za-z]/', $slug)) {
            $slug = 'role_'.($slug !== '' ? $slug : bin2hex(random_bytes(4)));
        }

        return $slug;
    }

    /**
     * Resolves ['name' => $slugOrKey, 'label' => $displayLabel] from form input.
     *
     * @return array{name: string, label: ?string}
     */
    public static function resolveRoleInput(mixed $value, ?Role $currentRole = null, string $guardName = 'admin'): array
    {
        if (! is_string($value)) {
            return ['name' => (string) $value, 'label' => null];
        }

        $trimmed = trim($value);

        // 1. Check if the value is unchanged from the current role
        if ($currentRole !== null) {
            $currentDisplay = self::role($currentRole);
            if ($trimmed === $currentDisplay || $trimmed === $currentRole->name) {
                return [
                    'name' => $currentRole->name,
                    'label' => $currentRole->label ?: ($currentRole->name !== $trimmed ? $trimmed : null),
                ];
            }
        }

        // 2. Check predefined translations in roles.php
        foreach (['ar', 'en'] as $locale) {
            foreach ((array) trans('roles', [], $locale) as $key => $label) {
                if (is_string($label) && $label === $trimmed) {
                    return [
                        'name' => $key,
                        'label' => $locale === 'ar' ? $label : (string) trans("roles.{$key}", [], 'ar'),
                    ];
                }
            }
        }

        // 3. If it contains non-ASCII characters (e.g. Arabic) or spaces, generate a unique slug
        if (preg_match('/[^\x20-\x7E]/u', $trimmed) || str_contains($trimmed, ' ')) {
            $baseSlug = self::slugify($trimmed);
            $slug = $baseSlug;
            $counter = 1;
            $ignoreId = $currentRole?->id;

            while (Role::query()
                ->where('guard_name', $guardName)
                ->where('name', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()) {
                $counter++;
                $slug = "{$baseSlug}_{$counter}";
            }

            return [
                'name' => $slug,
                'label' => $trimmed,
            ];
        }

        // 4. Otherwise it's already an ASCII identifier (e.g. CustomRole)
        return [
            'name' => $trimmed,
            'label' => $trimmed,
        ];
    }

    // Normalize the translated form label before validation/persistence.
    // Existing role names are never changed by a display translation.
    public static function roleKey(mixed $value, ?string $current = null): mixed
    {
        if (! is_string($value)) {
            return $value;
        }
        $value = trim($value);
        if ($current !== null && ($value === self::role($current) || $value === $current)) {
            return $current;
        }
        foreach (['ar', 'en'] as $locale) {
            foreach ((array) trans('roles', [], $locale) as $key => $label) {
                if (is_string($label) && $label === $value) {
                    return $key;
                }
            }
        }

        if (preg_match('/[^\x20-\x7E]/u', $value) || str_contains($value, ' ')) {
            return self::slugify($value);
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
