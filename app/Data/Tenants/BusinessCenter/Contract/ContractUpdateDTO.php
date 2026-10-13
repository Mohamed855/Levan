<?php

namespace App\Data\Tenants\BusinessCenter\Contract;

use Illuminate\Support\Carbon;

final class ContractUpdateDTO
{
    public ?int $company_id = null;

    public ?string $contract_number = null;

    public ?Carbon $start_date = null;

    public ?Carbon $end_date = null;

    public ?int $duration_months = null;

    public ?bool $auto_renewal = null;

    public ?string $commission_type = null;

    public ?string $status = null;

    public ?string $notes = null;

    /** @var CommissionInput[]|null */
    public ?array $commissions = null;

    private array $provided = [];

    private function __construct() {}

    public static function fromArray(array $v): self
    {
        $self = new self;
        $set = function (string $key, $value) use ($self) {
            $self->provided[$key] = true;
            $self->$key = $value;
        };

        if (array_key_exists('company_id', $v)) {
            $set('company_id', (int) $v['company_id']);
        }
        if (array_key_exists('contract_number', $v)) {
            $set('contract_number', (string) $v['contract_number']);
        }
        if (array_key_exists('start_date', $v)) {
            $set('start_date', Carbon::parse($v['start_date']));
        }
        if (array_key_exists('end_date', $v)) {
            $set('end_date', Carbon::parse($v['end_date']));
        }
        if (array_key_exists('duration_months', $v)) {
            $set('duration_months', (int) $v['duration_months']);
        }
        if (array_key_exists('auto_renewal', $v)) {
            $set('auto_renewal', (bool) $v['auto_renewal']);
        }
        if (array_key_exists('commission_type', $v)) {
            $set('commission_type', (string) $v['commission_type']);
        }
        if (array_key_exists('status', $v)) {
            $set('status', (string) $v['status']);
        }
        if (array_key_exists('notes', $v)) {
            $set('notes', $v['notes']);
        }

        if (array_key_exists('commissions', $v)) {
            $self->provided['commissions'] = true;
            $rows = [];
            foreach ((array) $v['commissions'] as $row) {
                $rows[] = CommissionInput::fromArray((array) $row);
            }
            $self->commissions = $rows;
        }

        return $self;
    }

    public function wasProvided(string $key): bool
    {
        return (bool) ($this->provided[$key] ?? false);
    }

    public function toModelArray(): array
    {
        $out = [];
        foreach (['company_id', 'contract_number', 'start_date', 'end_date', 'duration_months', 'auto_renewal', 'commission_type', 'status', 'notes'] as $k) {
            if ($this->wasProvided($k)) {
                $out[$k] = $this->$k;
            }
        }

        return $out;
    }
}
