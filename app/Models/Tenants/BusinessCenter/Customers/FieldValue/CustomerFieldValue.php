<?php

namespace App\Models\Tenants\BusinessCenter\Customers\FieldValue;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Portfolio\PortfolioField;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerFieldValue extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * اسم الجدول.
     */
    protected $table = 'customer_field_values';

    /**
     * الحقول القابلة للـ Mass Assignment.
     */
    protected $fillable = [
        'customer_id',
        'portfolio_field_id',
        'value',
    ];

    /**
     * أنواع الحقول.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * علاقة القيمة بالعميل.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * علاقة القيمة بحقل المحفظة.
     */
    public function portfolioField()
    {
        return $this->belongsTo(PortfolioField::class);
    }
}
