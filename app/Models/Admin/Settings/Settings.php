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
        'force_two_factor_auth',

        'etmam_enabled',
        'etmam_base_url',
        'etmam_system_code',
        'etmam_api_token',
        'etmam_hmac_secret',

        // microsoft

        'microsoft_client_id',
        'microsoft_client_secret',
        'microsoft_redirect_uri',
        'microsoft_tenant_id',
        'main_email',

        'tickets_enabled',
    ];

    protected $casts = [
        'maintenance_mode' => 'boolean',
        'force_two_factor_auth' => 'boolean',
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
            'force_two_factor_auth' => 'فرض المصادقة الثنائية',
            'etmam_enabled' => 'تفعيل إتمام',
            'etmam_base_url' => 'رابط إتمام الأساسي',
            'etmam_system_code' => 'كود نظام إتمام',
            'microsoft_client_id' => 'معرف عميل مايكروسوفت',
            'microsoft_redirect_uri' => 'رابط إعادة التوجيه مايكروسوفت',
            'microsoft_tenant_id' => 'معرف مستأجر مايكروسوفت',
            'main_email' => 'البريد الإلكتروني الرئيسي',
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
            'etmam_enabled' => [
                0 => 'غير مفعل',
                1 => 'مفعل',
            ],
            'force_two_factor_auth' => [
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
