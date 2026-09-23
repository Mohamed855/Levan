<?php

namespace App\Data\Tenants\BusinessCenter\Contract;

final class CommissionInput
{
    public function __construct(
        public string $commission_type,
        public ?string $procedure_type = null,
        public ?int $duration_from = null,
        public ?int $duration_to = null,
        public float $commission_rate = 0.0
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            commission_type: (string)($row['commission_type'] ?? ''),
            procedure_type: $row['procedure_type'] ?? null,
            duration_from: isset($row['duration_from']) ? (int)$row['duration_from'] : null,
            duration_to: isset($row['duration_to']) ? (int)$row['duration_to'] : null,
            commission_rate: (float)($row['commission_rate'] ?? 0)
        );
    }

    public function toModelArray(): array
    {
        return [
            'commission_type' => $this->commission_type,
            'procedure_type'  => $this->procedure_type,
            'duration_from'   => $this->duration_from,
            'duration_to'     => $this->duration_to,
            'commission_rate' => $this->commission_rate,
        ];
    }
}
