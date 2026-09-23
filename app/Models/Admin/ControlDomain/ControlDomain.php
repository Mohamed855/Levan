<?php

namespace App\Models\Admin\ControlDomain;

use App\Traits\ActivityLogTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class ControlDomain extends Model
{
    use HasFactory, ActivityLogTrait;

    protected $guarded = []; 

    /*
    |============================================================================
    |============================================================================
    |                          Activity Log
    |============================================================================
    |============================================================================
    */
    public static function getLogName(): string
    {
        return 'إعدادات اسعار الامتدادات';
    }

    public static function getModelNameArabic(): string
    {
        return 'نطاق';
    }

    public static function getFieldTranslations(): array
    {
        return [
            'domain_extension' => 'امتداد النطاق',
            'price'            => 'السعر',
            'is_active'        => 'الحالة',
        ];
    }

    public static function getIgnoredAttributes(): array
    {
        return [];
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
}
