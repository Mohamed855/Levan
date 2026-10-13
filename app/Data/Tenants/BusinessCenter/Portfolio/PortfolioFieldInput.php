<?php

namespace App\Data\Tenants\BusinessCenter\Portfolio;

final class PortfolioFieldInput
{
    public ?int $id = null;

    public string $name;

    public string $type;

    public bool $is_required;

    /*
    |--------------------------------------------------------------------------
    | Construct
    |--------------------------------------------------------------------------
    | Create a new field input DTO.
    */
    private function __construct(?int $id, string $name, string $type, bool $is_required)
    {
        $this->id = $id;
        $this->name = $name;
        $this->type = $type;
        $this->is_required = $is_required;
    }

    /*
    |--------------------------------------------------------------------------
    | From Arrays
    |--------------------------------------------------------------------------
    | Build a field DTO from normalized parallel arrays.
    */
    public static function fromArraysAt(int $i, array $names, array $types, array $requiredFlags, ?array $ids = null): ?self
    {
        $name = trim((string) ($names[$i] ?? ''));
        if ($name === '') {
            return null;
        }

        $id = isset($ids[$i]) && $ids[$i] !== null && $ids[$i] !== '' ? (int) $ids[$i] : null;
        $type = (string) ($types[$i] ?? 'text');
        $isRequired = isset($requiredFlags[$i]) && (string) $requiredFlags[$i] === '1';

        return new self($id, $name, $type, $isRequired);
    }
}
