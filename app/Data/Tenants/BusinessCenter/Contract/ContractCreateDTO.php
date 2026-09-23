<?php

namespace App\Data\Tenants\BusinessCenter\Contract;

use Illuminate\Support\Carbon;

final class ContractCreateDTO
{
    public function __construct(
        public int $company_id,
        public ?string $contract_number,
        public Carbon $start_date,
        public Carbon $end_date,
        public int $duration_months,
        public bool $auto_renewal,
        public string $commission_type,
        public string $status,
        public ?string $notes,
        /** @var CommissionInput[] */
        public array $commissions = [],
    ) {}

    public static function fromArray(array $v): self
    {
        $comms = [];
        foreach ((array)($v['commissions'] ?? []) as $row) {
            $comms[] = CommissionInput::fromArray((array)$row);
        }

        return new self(
            company_id: (int)$v['company_id'],
            contract_number: $v['contract_number'] ?? null,
            start_date: Carbon::parse($v['start_date']),
            end_date: Carbon::parse($v['end_date']),
            duration_months: (int)$v['duration_months'],
            auto_renewal: (bool)($v['auto_renewal'] ?? false),
            commission_type: (string)$v['commission_type'],
            status: (string)($v['status'] ?? 'active'),
            notes: $v['notes'] ?? null,
            commissions: $comms
        );
    }

    public function toModelArray(): array
    {
        return [
            'company_id'      => $this->company_id,
            'contract_number' => $this->contract_number,
            'start_date'      => $this->start_date,
            'duration_months' => $this->duration_months,
            'end_date'        => $this->end_date,
            'auto_renewal'    => $this->auto_renewal,
            'commission_type' => $this->commission_type,
            'status'          => $this->status,
            'notes'           => $this->notes,
        ];
    }
}
