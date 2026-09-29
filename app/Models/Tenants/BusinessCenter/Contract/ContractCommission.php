<?php

namespace App\Models\Tenants\BusinessCenter\Contract;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'commission_type',
        'procedure_type',
        'duration_from',
        'duration_to',
        'commission_rate',
        'is_active',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'duration_from' => 'integer',
        'duration_to' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */
    public function getCommissionTypeLabelAttribute(): string
    {
        $labels = [
            'procedure' => 'حسب الإجراء',
            'duration' => 'حسب المدة',
            'delinquency' => 'ححسب مدة التعثر',

        ];

        return $labels[$this->commission_type] ?? 'غير محدد';
    }

    public function getProcedureTypeLabelAttribute(): ?string
    {
        if (!$this->procedure_type) return null;

        $labels = [
            'friendly' => 'ودي',
            'legal' => 'قانوني',
        ];

        return $labels[$this->procedure_type] ?? null;
    }

    public function getDurationRangeAttribute(): ?string
    {
        if ($this->commission_type !== 'duration') return null;

        return "{$this->duration_from} - {$this->duration_to} يوم";
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('commission_type', $type);
    }

    public function scopeForProcedure($query, $procedureType)
    {
        return $query->where('commission_type', 'procedure')
            ->where('procedure_type', $procedureType);
    }

    public function scopeForDuration($query, $days)
    {
        return $query->where('commission_type', 'duration')
            ->where('duration_from', '<=', $days)
            ->where('duration_to', '>=', $days);
    }
}
