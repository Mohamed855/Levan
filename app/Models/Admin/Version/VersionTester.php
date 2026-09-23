<?php

namespace App\Models\Admin\Version;

use App\Enums\Admin\Version\VersionTesterType;
use App\Models\Admin\Tenants\Tenant;
use App\Traits\ActivityLogTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class VersionTester extends Model
{
    /*
    |============================================================================
    |============================================================================
    |                               Properties
    |============================================================================
    |============================================================================
    */
    use HasFactory,  ActivityLogTrait;

    protected $fillable = [
        'tenant_id',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'type'      => VersionTesterType::class,
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
        return 'إدارة فريق اختبار الاصدارات ';
    }

    public static function getModelNameArabic(): string
    {
        return 'عضو';
    }

    public static function getFieldTranslations(): array
    {
        return [
            'tenant_id' => 'العميل',
            'type'      => 'النوع',
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
            'type' => [
                'tester'    => VersionTesterType::TESTER->label(),
                'developer' => VersionTesterType::DEVELOPER->label(),
            ],
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
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
