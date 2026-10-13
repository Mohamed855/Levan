<?php

namespace App\Models\Admin\Version;

use App\Enums\Admin\Version\VersionStage;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Tenants\Tenant;
use App\Traits\ActivityLogTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Version extends Model
{
    /*
    |============================================================================
    |============================================================================
    |                               Properties
    |============================================================================
    |============================================================================
    */
    use ActivityLogTrait,  HasFactory;

    protected $connection = 'mysql';

    protected $fillable = [
        'version',
        'changelog',
        //
        'file_path',
        'file_size',
        'stage',
        'is_active',
        'released_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'file_size' => 'integer',
        'released_at' => 'datetime',
        'stage' => VersionStage::class,
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
        return 'إدارة الإصدارات';
    }

    public static function getModelNameArabic(): string
    {
        return 'إصدار';
    }

    public static function getFieldTranslations(): array
    {
        return [
            'version' => 'رقم الإصدار',
            'changelog' => 'سجل التغييرات',
            'file_path' => 'مسار الملف',
            'stage' => 'المرحلة',
            'is_active' => 'الحالة',
            'released_at' => 'تاريخ الإصدار',
            'created_by' => 'أنشئ بواسطة',
            'updated_by' => 'تم التحديث بواسطة',
        ];
    }

    public static function getIgnoredAttributes(): array
    {
        return [];
    }

    public static function getValueTranslations(): array
    {
        return [
            'stage' => [
                'alpha' => VersionStage::ALPHA->label(),
                'beta' => VersionStage::BETA->label(),
                'stable' => VersionStage::STABLE->label(),
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
    public function creator()
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(AdminUser::class, 'updated_by');
    }

    public function tenants()
    {
        return $this->hasMany(Tenant::class, 'version_id');
    }

    // public function tenantVersions()
    // {
    //     return $this->hasMany(TenantVersion::class);
    // }

    // public function tenants()
    // {
    //     return $this->belongsToMany(Tenant::class, 'tenant_versions')->withTimestamps();
    // }

}
