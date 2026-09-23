<?php

namespace App\Data\Tenants\BusinessCenter\Portfolio;

use Illuminate\Support\Carbon;

final class PortfolioUpdateDTO
{
    public string $name;
    public int $company_id;
    public ?Carbon $assignment_date = null;
    public ?string $call_start_time = null;    // ✅ إضافة
    public ?string $call_end_time = null;      // ✅ إضافة
    public ?bool $has_split_names = null;
    public ?string $status = null;
    public int $user_id;
    /** @var PortfolioFieldInput[] */
    public array $fields = [];

    /*
    |--------------------------------------------------------------------------
    | From Array
    |--------------------------------------------------------------------------
    */
    public static function fromArray(array $v): self
    {
        $self = new self();
        $self->name = (string)$v['name'];
        $self->company_id = (int)$v['company_id'];
        $self->assignment_date = !empty($v['assignment_date']) ? Carbon::parse($v['assignment_date']) : null;
        
        // ✅ إضافة معالجة أوقات الاتصال
        $self->call_start_time = !empty($v['call_start_time']) ? (string)$v['call_start_time'] : null;
        $self->call_end_time = !empty($v['call_end_time']) ? (string)$v['call_end_time'] : null;
        
        $self->has_split_names = isset($v['has_split_names']) ? (bool)$v['has_split_names'] : null;
        $self->status = $v['status'] ?? null;
        $self->user_id = (int)($v['user_id'] ?? auth()->id());

        $names = (array)($v['field_names'] ?? []);
        $types = (array)($v['field_types'] ?? []);
        $reqs  = (array)($v['field_required_flags'] ?? []);
        $ids   = (array)($v['field_ids'] ?? []);

        $max = count($names);
        for ($i = 0; $i < $max; $i++) {
            $dto = PortfolioFieldInput::fromArraysAt($i, $names, $types, $reqs, $ids);
            if ($dto) $self->fields[] = $dto;
        }

        return $self;
    }
}