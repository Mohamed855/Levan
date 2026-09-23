<?php

namespace App\Models\Admin\Bank;

use App\Traits\ActivityLogTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Bank extends Model
{
    use HasFactory, ActivityLogTrait;

    protected $connection = 'mysql';

    protected $fillable = [
        'bank_name',
        'icon',
        'account_name',
        'iban',
        'account_number',
        'is_active',
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
        return 'إعدادات البنوك';
    }

    public static function getModelNameArabic(): string
    {
        return 'بنك';
    }

    public static function getFieldTranslations(): array
    {
        return [
            'bank_name'      => 'اسم البنك',
            'icon'           => 'الأيقونة',
            'account_name'   => 'اسم الحساب',
            'iban'           => 'رقم الآيبان',
            'account_number' => 'رقم الحساب',
            'is_active'      => 'الحالة',
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
