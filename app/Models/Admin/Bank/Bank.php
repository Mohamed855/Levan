<?php

namespace App\Models\Admin\Bank;

use App\Traits\ActivityLogTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use ActivityLogTrait, HasFactory;

    protected $connection = 'mysql';

    protected $fillable = [
        'bank_name',
        'icon',
        'account_name',
        'iban',
        'account_number',
        'is_active',
    ];

    public function getIconUrlAttribute(): ?string
    {
        if (empty($this->icon)) {
            return null;
        }

        if (preg_match('#^(https?:)?//#', (string) $this->icon) || str_starts_with((string) $this->icon, 'data:')) {
            return $this->icon;
        }

        if (preg_match('#^[a-zA-Z]:\\\\#', (string) $this->icon) || str_contains((string) $this->icon, 'Temp\\') || str_contains((string) $this->icon, '.tmp')) {
            return null;
        }

        $trimmed = ltrim((string) $this->icon, '/');
        if (str_starts_with($trimmed, 'storage/')) {
            return asset($trimmed);
        }

        return asset('storage/'.$trimmed);
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
        return 'إعدادات البنوك';
    }

    public static function getModelNameArabic(): string
    {
        return 'بنك';
    }

    public static function getFieldTranslations(): array
    {
        return [
            'bank_name' => 'اسم البنك',
            'icon' => 'الأيقونة',
            'account_name' => 'اسم الحساب',
            'iban' => 'رقم الآيبان',
            'account_number' => 'رقم الحساب',
            'is_active' => 'الحالة',
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
