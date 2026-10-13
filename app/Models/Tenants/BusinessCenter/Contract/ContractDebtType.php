<?php

namespace App\Models\Tenants\BusinessCenter\Contract;

use App\Models\Tenants\general_setting\SettingsDebtType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractDebtType extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'debt_type_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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

    public function debtType()
    {
        return $this->belongsTo(SettingsDebtType::class, 'debt_type_id');
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

    public function scopeForContract($query, $contractId)
    {
        return $query->where('contract_id', $contractId);
    }
}
