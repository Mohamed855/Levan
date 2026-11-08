<?php

namespace App\Models\Admin\Settings;

use App\Traits\ActivityLogTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    use ActivityLogTrait,  HasFactory;

    protected $connection = 'mysql';

    protected $fillable = [
        'image',
        'office_name',
        'logo_text',

        'office_address',
        'commercial_register', // السجل التجاري
        'tax_number', // الرقم الضريبي
        'phone_number', // رقم الهاتف
        'email', // البريد الإلكتروني
        'facebook_url', 'x_url', 'linkedin_url', 'google_maps_embed_url',

        'maintenance_mode',
        'trial_days_mode',

        'tickets_enabled',
    ];

    protected $casts = [
        'maintenance_mode' => 'boolean',
        'tickets_enabled' => 'boolean',
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
        return 'إدارة الإعدادات';
    }

    public static function getModelNameArabic(): string
    {
        return 'إعداد';
    }

    public static function getFieldTranslations(): array
    {
        return [
            'image' => 'الصورة',
            'office_name' => 'اسم المكتب',
            'logo_text' => 'نص الشعار',
            'maintenance_mode' => 'وضع الصيانة',
            'trial_days_mode' => 'تفعيل الفترة التجريبية',
            'tickets_enabled' => 'تفعيل خدمة التذاكر',
            'office_address' => 'عنوان المكتب',
            'commercial_register' => 'السجل التجاري',
            'tax_number' => 'الرقم الضريبي',
            'phone_number' => 'رقم الهاتف',
            'email' => 'البريد الإلكتروني',
        ];
    }

    public static function getValueTranslations(): array
    {
        return [
            'maintenance_mode' => [
                0 => 'غير مفعل',
                1 => 'مفعل',
            ],
            'trial_days_mode' => [
                0 => 'غير مفعل',
                1 => 'مفعل',
            ],
            'tickets_enabled' => [
                0 => 'غير مفعل',
                1 => 'مفعل',
            ],
        ];
    }
}
