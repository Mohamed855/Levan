<?php

namespace App\Models\Admin\Settings;

use App\Traits\ActivityLogTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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

    public function getLogoUrlAttribute(): string
    {
        $image = $this->image;
        if (! $image || in_array($image, ['default-office.png', 'image/demo-office.jpg'], true)) {
            return asset('static/brand/logos/lot/trans-profile.png');
        }

        if (preg_match('#^(https?:)?//#', $image) || str_starts_with($image, 'data:')) {
            return $image;
        }

        $normalized = ltrim($image, '/');
        if (str_starts_with($normalized, 'storage/')) {
            $normalized = substr($normalized, 8);
        }

        if (Storage::disk('public')->exists($normalized) || file_exists(public_path('storage/'.$normalized))) {
            return asset('storage/'.$normalized);
        }

        return asset('static/brand/logos/lot/trans-profile.png');
    }

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
