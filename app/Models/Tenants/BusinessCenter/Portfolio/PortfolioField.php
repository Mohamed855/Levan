<?php

namespace App\Models\Tenants\BusinessCenter\Portfolio;

use App\Models\Tenants\BusinessCenter\Customers\FieldValue\CustomerFieldValue;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PortfolioField extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'portfolio_fields';

    protected $fillable = [
        'portfolio_id',
        'field_name',
        'field_type',
        'is_required',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | العلاقة مع المحفظة
    |--------------------------------------------------------------------------
    */
    public function portfolio()
    {
        return $this->belongsTo(Portfolio::class);
    }

    /*
    |--------------------------------------------------------------------------
    | العلاقة مع قيم الحقول
    |--------------------------------------------------------------------------
    */
    public function customerFieldValues()
    {
        return $this->hasMany(CustomerFieldValue::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Static Methods
    |--------------------------------------------------------------------------
    */
    public static function getTypeOptions(): array
    {
        return [
            'text' => 'نصي',
            'numeric' => 'رقمي',
            'date' => 'تاريخ',
        ];
    }
}
