<?php

namespace App\Data\Tenants\BusinessCenter\Company;

final class AuthorizedPersonInput
{
    public function __construct(
        public ?string $name,
        public ?string $phone,
        public ?string $email,
        public ?string $position,
        public bool $is_default = false
    ) {}

    public static function fromArray(array $input): self
    {
        $n = fn(?string $v) => isset($v) ? (trim($v) === '' ? null : trim($v)) : null;

        return new self(
            name: $n($input['name'] ?? null),
            phone: $n($input['phone'] ?? null),
            email: $n($input['email'] ?? null),
            position: $n($input['position'] ?? null),
            is_default: (bool)($input['is_default'] ?? false)
        );
    }

    public function isMeaningful(): bool
    {
        return ($this->name ?? $this->phone ?? $this->email ?? $this->position) !== null;
    }

    public function toModelArray(): array
    {
        return [
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'position' => $this->position,
            'is_default' => $this->is_default,
        ];
    }
}
