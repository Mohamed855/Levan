<?php

namespace App\Models\Tenants\BusinessCenter\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Company extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    | Basic model configuration including fillable fields and casts.
    */
    protected $fillable = [
        'name',
        'logo_path',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Activity Log Configuration
    |--------------------------------------------------------------------------
    | Configuration for activity logging with Arabic descriptions and detailed tracking.
    */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('companies')
            ->setDescriptionForEvent(fn (string $eventName) => $this->getEventDescription($eventName))
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    /*
    |--------------------------------------------------------------------------
    | Activity Log Event Descriptions
    |--------------------------------------------------------------------------
    | Generate Arabic descriptions for activity log events with error handling.
    */
    private function getEventDescription(string $eventName): string
    {
        $companyName = $this->name ?? 'غير محدد';

        $descriptions = [
            'created' => "تم إنشاء شركة جديدة: {$companyName}",
            'updated' => "تم تحديث شركة: {$companyName}",
            'deleted' => "تم حذف شركة: {$companyName}",
            'restored' => "تم استعادة شركة: {$companyName}",
        ];

        $description = $descriptions[$eventName] ?? "عملية على شركة: {$companyName}";

        if ($eventName === 'updated' && $this->isDirty()) {
            $description .= $this->getChangesSummary();
        }

        return $description;
    }

    /*
    |--------------------------------------------------------------------------
    | Activity Log Change Details
    |--------------------------------------------------------------------------
    | Format and display detailed changes in Arabic for activity logs with error handling.
    */
    private function getChangesSummary(): string
    {
        $changes = [];
        $fieldNames = [
            'name' => 'الاسم',
            'logo_path' => 'الشعار',
            'status' => 'الحالة',
        ];

        foreach ($this->getDirty() as $field => $newValue) {
            if ($field === 'updated_at') {
                continue;
            }

            if (! isset($fieldNames[$field])) {
                continue;
            }

            $fieldLabel = $fieldNames[$field];
            $oldValue = $this->getOriginal($field);

            $oldFormatted = $this->formatValue($field, $oldValue);
            $newFormatted = $this->formatValue($field, $newValue);

            if ($oldFormatted !== $newFormatted) {
                $changes[] = "{$fieldLabel}: {$oldFormatted} ← {$newFormatted}";
            }
        }

        return $changes ? ' ('.implode(' | ', $changes).')' : '';
    }

    /*
    |--------------------------------------------------------------------------
    | Field Value Formatting
    |--------------------------------------------------------------------------
    | Format field values for display in activity logs.
    */
    private function formatValue(string $field, $value): string
    {
        if (is_null($value) || $value === '') {
            return '---';
        }

        switch ($field) {
            case 'status':
                return $value === 'active' ? 'نشط' : 'غير نشط';
            case 'logo_path':
                return basename($value);
            default:
                return (string) $value;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    | Define accessors and mutators for model attributes.
    */
    public function getStatusLabelAttribute(): string
    {
        return $this->status === 'active' ? 'نشط' : 'غير نشط';
    }

    public function getStatusClassAttribute(): string
    {
        return $this->status === 'active' ? 'bg-label-success' : 'bg-label-danger';
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    | Define query scopes for filtering and searching companies.
    */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%");
        })->orWhereHas('authorizedPeople', function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('position', 'like', "%{$term}%");
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    | Define model relationships with other entities.
    */


    public function authorizedPeople()
    {
        return $this->hasMany(CompanyAuthorizedPerson::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path
            ? Storage::disk('tenant_public')->url($this->logo_path)
            : null;
    }

    // اسم الملف فقط (اختياري لعرضه كنص)
    public function getLogoFilenameAttribute(): ?string
    {
        return $this->logo_path ? basename($this->logo_path) : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?? $this->getRouteKeyName();
        $query = static::query();

        return $query->where($field, $value)->firstOrFail();
    }

    /*
    |--------------------------------------------------------------------------
    | Company Targets Relationship
    |--------------------------------------------------------------------------
    */
    public function targets()
    {
        return $this->hasMany(CompanyTarget::class)->orderBy('effective_from', 'desc');
    }

    public function currentTarget()
    {
        return $this->hasOne(CompanyTarget::class)
            ->whereNull('effective_to')
            ->latest('effective_from');
    }

    public function getCurrentTargetAttribute()
    {
        if (! Schema::hasTable('company_targets')) {
            return null;
        }

        return $this->getRelationValue('currentTarget');
    }

    public function getTargetsAttribute()
    {
        if (! Schema::hasTable('company_targets')) {
            return collect();
        }

        return $this->getRelationValue('targets');
    }

    public function getTargetForDate($date)
    {
        if (! Schema::hasTable('company_targets')) {
            return null;
        }

        return $this->targets()->forDate($date)->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Target Accessors
    |--------------------------------------------------------------------------
    */
    public function getCurrentTargetPercentageAttribute(): ?float
    {
        if (! Schema::hasTable('company_targets')) {
            return 0.0;
        }

        return $this->currentTarget?->target_percentage;
    }

    public function getTargetPercentageForDate($date): ?float
    {
        if (! Schema::hasTable('company_targets')) {
            return 0.0;
        }

        return $this->getTargetForDate($date)?->target_percentage;
    }
}
