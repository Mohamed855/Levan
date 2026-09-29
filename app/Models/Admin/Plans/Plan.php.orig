<?php

namespace App\Models\Admin\Plans;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Traits\ActivityLogTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Plan extends Model
{
    use HasFactory, SoftDeletes, ActivityLogTrait;

    protected $connection = 'mysql';

    protected $fillable = [
        'name',
        'description',
        'price',
        'yearly_renewal_price',
        'included_users',
        'features',
        'is_active',

        'extra_user_license_price',

        'discount_percentage',
        'branches_limit', // عدد الفروع
        'unlimited_branches', // غير محدود
        'domain_management_enabled', // ادارة الدومينات

        'sort_order',

        'created_by',
        'updated_by',

    ];

    protected $casts = [
        'price'             => 'decimal:2',
        'included_users'    => 'integer',
        'features'          => 'array',
        'is_active'         => 'boolean',

        'discount_percentage'       => 'float',
        'unlimited_branches'        => 'boolean',
        'domain_management_enabled' => 'boolean',
    ];


    /*
    |============================================================================
    |============================================================================
    |                          Activity Log
    |============================================================================
    |============================================================================
    */
    public static function getLogName(): string
    {
        return 'إدارة الباقات ';
    }

    public static function getModelNameArabic(): string
    {
        return 'باقة';
    }

    public static function getFieldTranslations(): array
    {
        return [
            'name'              => 'اسم الخطة',
            'description'       => 'الوصف',
            'price'             => 'السعر الشهري',
            'included_users'    => 'عدد المستخدمين',
            'features'          => 'المميزات',
            'is_active'         => 'الحالة',
            'created_by'        => 'أنشئ بواسطة',
            'updated_by'        => 'تم التحديث بواسطة',
        ];
    }


    public static function getValueTranslations(): array
    {
        return [
            'is_active' => [
                0 => 'غير نشط',
                1 => 'نشط',
            ],
        ];
    }



    /*
    |============================================================================
    |============================================================================
    |                               Relations
    |============================================================================
    |============================================================================
    */

    public function creator()
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(AdminUser::class, 'updated_by');
    }




    /*
    |============================================================================
    |============================================================================
    |                              Accessor
    |============================================================================
    |============================================================================
    */
    // $plan->price_after_discount;
    public function getPriceAfterDiscountAttribute(): float
    {
        if ($this->discount_percentage <= 0) {
            return (float) $this->price;
        }

        $discount = ($this->price * $this->discount_percentage) / 100;

        return (float) ($this->price - $discount);
    }


    /*
    |============================================================================
    |============================================================================
    |                               Scope
    |============================================================================
    |============================================================================
    */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
