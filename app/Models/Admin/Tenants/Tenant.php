<?php

namespace App\Models\Admin\Tenants;

use App\Models\Admin\Bills\Bill;
use App\Models\Admin\City;
use App\Models\Admin\Licenses\LicenseSeat;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Version\Version;
use App\Models\Tenants\Settings\TransferDomain;
use App\Models\Tenants\Ticket\Ticket;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    /*
    |============================================================================
    |============================================================================
    |                               Properties
    |============================================================================
    |============================================================================
    */
    use HasDatabase, HasDomains, SoftDeletes;

    protected $connection = 'mysql';

    protected $fillable = [
        'id',
        'version_id',
        'folderName',
        'db_database',
        'db_username',
        'db_password',
        'db_host',
        'db_port',
        'cpanel_username',
        'api_token',
        'api_token_regenerated_at',

        'company_name',
        'commercial_number',
        'primary_contact_name',
        'primary_contact_email',
        'primary_contact_phone',
        'primary_user_global_uid',

        'access_type',
        'tax_number',
        'city_id',
        'address',
        'status',
        'custom_domain_active',
        'domain_activated_at',
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'version_id',
            'folderName',
            'db_database',
            'db_username',
            'db_password',
            'db_host',
            'db_port',
            'api_token',
            'api_token_regenerated_at',
            'cpanel_username',
            'company_name',
            'commercial_number',
            'primary_contact_name',
            'primary_contact_email',
            'primary_contact_phone',
            'primary_user_global_uid',

            'tax_number',
            'city_id',
            'address',
            'status',
            'access_type',
            'custom_domain_active',
            'domain_activated_at',
            'deleted_at',
        ];
    }

    protected $casts = [
        'data' => 'array',
        'api_token_regenerated_at' => 'datetime',
    ];

    protected $hidden = [
        'api_token',
    ];

    /*
    |============================================================================
    |============================================================================
    |                              Boot
    |============================================================================
    |============================================================================
    */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tenant) {
            $reservedIdentifiers = ['admin', 'superadmin'];
            $tenantId = strtolower(trim((string) ($tenant->id ?? '')));
            $folderName = strtolower(trim((string) ($tenant->folderName ?? '')));

            if (in_array($tenantId, $reservedIdentifiers, true) || in_array($folderName, $reservedIdentifiers, true)) {
                throw new \InvalidArgumentException("لا يمكن استخدام المعرف 'admin' كـ tenant لأنه محجوز للنظام.");
            }
        });
    }

    /*
    |============================================================================
    |============================================================================
    |                          Activity Log
    |============================================================================
    |============================================================================
    */

    // public static function getLogName(): string
    // {
    //     return 'إدارة العملاء';
    // }

    // public static function getModelNameArabic(): string
    // {
    //     return 'عميل';
    // }

    // public static function getFieldTranslations(): array
    // {
    //     return [
    //         'company_name'         => 'اسم الشركة',
    //         'commercial_number'    => 'السجل التجاري',
    //         'db_database'          => 'اسم قاعدة البيانات',
    //         'db_username'          => 'اسم مستخدم قاعدة البيانات',
    //         'db_host'              => 'مضيف قاعدة البيانات',
    //         'db_port'              => 'منفذ قاعدة البيانات',
    //         'folderName'           => 'اسم المجلد',
    //         'version_id'           => 'الإصدار',
    //         'access_type'          => 'نوع الوصول',
    //         'custom_domain_active' => 'تفعيل النطاق المخصص',
    //         'domain_activated_at'  => 'تاريخ تفعيل النطاق',
    //     ];
    // }

    // public static function getIgnoredAttributes(): array
    // {
    //     return [
    //         'db_password',
    //         'api_token',
    //         'data',
    //     ];
    // }

    // public static function getValueTranslations(): array
    // {
    //     return [
    //         'access_type' => [
    //             'path'   => 'مسار',
    //             'domain' => 'نطاق',
    //         ],
    //         'custom_domain_active' => [
    //             0 => 'غير مفعل',
    //             1 => 'مفعل',
    //         ],
    //     ];
    // }

    /*
    |============================================================================
    |============================================================================
    |                               Relations
    |============================================================================
    |============================================================================
    */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'tenant_id');
    }

    public function lastSubscription()
    {
        return $this->hasOne(Subscription::class, 'tenant_id')->latestOfMany('id');
    }

    public function subscription()
    {
        return $this->lastSubscription();
    }

    public function companySettings()
    {
        return $this->hasOne(CompanySetting::class, 'tenant_id');
    }

    public function seats()
    {
        return $this->hasMany(LicenseSeat::class, 'tenant_id', 'id');
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function transferdomains()
    {
        return $this->hasMany(TransferDomain::class, 'tenant_id');
    }

    public function version()
    {
        return $this->belongsTo(Version::class, 'version_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'tenant_id', 'id');
    }

    /*
    |============================================================================
    |============================================================================
    |                            Other function
    |============================================================================
    |============================================================================
    */
    public function getFirstUserAttribute()
    {
        if (! tenancy()->initialized || tenancy()->tenant->id !== $this->id) {
            tenancy()->initialize($this);
            $user = User::where('id', '1')->first();
            tenancy()->end();

            return $user;
        }

        return User::where('id', '1')->first();
    }

    /**
     * يولّد توكن API جديد، يخزّن فقط بصمة (hash) منه في قاعدة البيانات،
     * ويُعيد القيمة الصريحة (plain text) لعرضها مرة واحدة فقط للمستخدم.
     */
    public function regenerateApiToken(): string
    {
        $plainTextToken = Str::random(60);

        $this->api_token = hash('sha256', $plainTextToken);
        $this->api_token_regenerated_at = now();
        $this->save();

        return $plainTextToken;
    }
}
