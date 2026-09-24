<?php

namespace App\Models\Admin\Tenants;

use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Licenses\LicenseSeat;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Version\Version;
use App\Models\Tenants\Settings\TransferDomain;
use App\Models\Tenants\Ticket\Ticket;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\TenantCollection;

class TenantOld16022025 extends BaseTenant implements TenantWithDatabase
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

    /*
    |--------------------------------------------------------------------------
    | Fillable: لتحديد الأعمدة التي يمكن تعيينها بشكل جماعي
    |--------------------------------------------------------------------------
    */
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
        'company_name',
        'commercial_number',
        'access_type',
        'custom_domain_active',
        'domain_activated_at',
    ];

    /*
    |--------------------------------------------------------------------------
    | Custom Columns: لتحديد الأعمدة المخصصة التي سيتم تضمينها في عمليات الاستعلام
    |--------------------------------------------------------------------------
    */
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
            'cpanel_username',
            'company_name',
            'commercial_number',
            'access_type',
            'custom_domain_active',
            'domain_activated_at',
            'deleted_at'
        ];
    }

    protected $casts = [
        'data' => 'array',
    ];

    protected $hidden = [
        'api_token',
    ];

    /*
    |--------------------------------------------------------------------------
    | Boot method: لإضافة Global Scope لاستبعاد المحذوفات تلقائياً
    |--------------------------------------------------------------------------
    */
    protected static function boot()
    {
        parent::boot();

        // ✅ إضافة Global Scope لاستبعاد المحذوفات تلقائياً
        static::addGlobalScope('active_tenants', function ($query) {
            $query->whereNull('deleted_at');
        });

        static::creating(function ($tenant) {
            if (empty($tenant->api_token)) {
                $tenant->api_token = Str::random(60);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Override: إجبار all() على جلب النشطة فقط
    |--------------------------------------------------------------------------
    | هذه الـ method تُستخدم بواسطة أوامر tenancy
    */
    public static function all($columns = ['*'])
    {
        return static::whereNull('deleted_at')->get($columns);
    }

    /*
    |--------------------------------------------------------------------------
    | Override: إجبار cursor() على جلب النشطة فقطph
    |--------------------------------------------------------------------------
    | بعض الأوامر تستخدم cursor بدلاً من all
    */
    public static function cursor()
    {
        return static::whereNull('deleted_at')->cursor();
    }

    /*
    |--------------------------------------------------------------------------
    | Override: newCollection لضمان استخدام TenantCollection
    |--------------------------------------------------------------------------
    */
    public function newCollection(array $models = []): TenantCollection
    {
        return new TenantCollection($models);
    }

    /*
    |--------------------------------------------------------------------------
    | Scope: للحصول على جميع الـ tenants بما فيها المحذوفة
    |--------------------------------------------------------------------------
    | استخدمه عند الحاجة: Tenant::withTrashed()->get()
    */
    public function scopeWithTrashed($query)
    {
        return $query->withoutGlobalScope('active_tenants')->whereNotNull('id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scope: للحصول على المحذوفة فقط
    |--------------------------------------------------------------------------
    | استخدمه: Tenant::onlyTrashed()->get()
    */
    public function scopeOnlyTrashed($query)
    {
        return $query->withoutGlobalScope('active_tenants')->whereNotNull('deleted_at');
    }

    /*
    |--------------------------------------------------------------------------
    | Scope: للحصول على النشطة فقط (صريح)
    |--------------------------------------------------------------------------
    | استخدمه: Tenant::active()->get()
    */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /*
    |--------------------------------------------------------------------------
    | Method: forceDelete لإزالة السجل نهائياً من قاعدة البيانات
    |--------------------------------------------------------------------------
    */
    public function lastSubscription()
    {
        return $this->hasOne(Subscription::class, 'tenant_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Relations: تعريف العلاقات مع باقي النماذج
    |--------------------------------------------------------------------------
    */
    public function seats()
    {
        return $this->hasMany(LicenseSeat::class, 'tenant_id', 'id');
    }

    /*
    |--------------------------------------------------------------------------
    | Relations: تعريف العلاقات مع باقي النماذج
    |--------------------------------------------------------------------------
    */
    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Relations: تعريف العلاقات مع باقي النماذج
    |--------------------------------------------------------------------------
    */
    public function transferdomains()
    {
        return $this->hasMany(TransferDomain::class, 'tenant_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Relations: تعريف العلاقات مع باقي النماذج
    |--------------------------------------------------------------------------
    */
    public function version()
    {
        return $this->belongsTo(Version::class, 'version_id');
    }
    
    /*
    |--------------------------------------------------------------------------
    | Relations: تعريف العلاقات مع باقي النماذج
    |--------------------------------------------------------------------------
    */
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'tenant_id', 'id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessor: للحصول على أول مستخدم في الـ tenant
    |--------------------------------------------------------------------------
    */
    public function getFirstUserAttribute()
    {
        if (!tenancy()->initialized || tenancy()->tenant->id !== $this->id) {
            tenancy()->initialize($this);
            $user = User::where('id', '1')->first();
            tenancy()->end();
            return $user;
        }

        return User::where('id', '1')->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Method: لتجديد الـ API token الخاص بالـ tenant
    |--------------------------------------------------------------------------
    */
    public function regenerateApiToken()
    {
        $this->api_token = Str::random(60);
        $this->save();

        return $this->api_token;
    }
}