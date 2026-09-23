<?php

namespace App\Models\Tenants\BusinessCenter\Portfolio;

use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Portfolio extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'company_id',
        'has_split_names',
        'assignment_date',
        'call_start_time',
        'call_end_time',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'assignment_date' => 'date',
        'call_start_time' => 'datetime:H:i',
        'call_end_time' => 'datetime:H:i',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'has_split_names' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Activity Log Configuration
    |--------------------------------------------------------------------------
    */

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('portfolios')
            ->setDescriptionForEvent(fn(string $eventName) => $this->getEventDescription($eventName))
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    private function getEventDescription(string $eventName): string
    {
        $portfolioName = $this->name ?? 'غير محدد';

        $descriptions = [
            'created' => "تم إنشاء محفظة جديدة: {$portfolioName}",
            'updated' => "تم تحديث محفظة: {$portfolioName}",
            'deleted' => "تم حذف محفظة: {$portfolioName}",
            'restored' => "تم استعادة محفظة: {$portfolioName}",
        ];

        $description = $descriptions[$eventName] ?? "عملية على محفظة: {$portfolioName}";

        if ($eventName === 'updated' && $this->isDirty()) {
            $description .= $this->getChangesSummary();
        }

        return $description;
    }

    private function getChangesSummary(): string
    {
        $changes = [];
        $fieldNames = [
            'name' => 'الاسم',
            'company_id' => 'الشركة',
            'assignment_date' => 'تاريخ التخصيص',
            'call_start_time' => 'وقت بدء الاتصال',
            'call_end_time' => 'وقت نهاية الاتصال',
            'has_split_names' => 'تقسيم الأسماء',
            'status' => 'الحالة',
        ];

        foreach ($this->getDirty() as $field => $newValue) {
            if ($field === 'updated_at') continue;

            if (!isset($fieldNames[$field])) continue;

            $fieldLabel = $fieldNames[$field];
            $oldValue = $this->getOriginal($field);

            $oldFormatted = $this->formatValue($field, $oldValue);
            $newFormatted = $this->formatValue($field, $newValue);

            if ($oldFormatted !== $newFormatted) {
                $changes[] = "{$fieldLabel}: {$oldFormatted} ← {$newFormatted}";
            }
        }

        return $changes ? ' (' . implode(' | ', $changes) . ')' : '';
    }

    /*
    |--------------------------------------------------------------------------
    | Format Value
    |--------------------------------------------------------------------------
    */
    private function formatValue(string $field, $value): string
    {
        if (is_null($value) || $value === '') {
            return '---';
        }

        switch ($field) {
            case 'status':
                return $value === 'active' ? 'نشط' : 'غير نشط';

            case 'has_split_names':
                return $value ? 'نعم' : 'لا';

            case 'assignment_date':
                return \Carbon\Carbon::parse($value)->format('Y-m-d');

            case 'call_start_time':
            case 'call_end_time':
                return \Carbon\Carbon::parse($value)->format('h:i A');

            case 'company_id':
                $company = Company::find($value);
                return $company?->name ?? "شركة #{$value}";

            default:
                return (string) $value;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the company that owns the portfolio.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }


    /**
     * Get the category that owns the customers.
     */
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the formatted assignment date.
     */
    public function getFormattedAssignmentDateAttribute(): ?string
    {
        return $this->assignment_date?->locale('ar')->translatedFormat('d F Y');
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->status === 'active' ? 'نشط' : 'غير نشط';
    }

    /**
     * Get the status class for badge.
     */
    public function getStatusClassAttribute(): string
    {
        return $this->status === 'active' ? 'bg-label-success' : 'bg-label-danger';
    }

    /**
     * Get the formatted call start time.
     */
    public function getFormattedCallStartTimeAttribute(): ?string
    {
        return $this->call_start_time ? \Carbon\Carbon::parse($this->call_start_time)->format('h:i A') : null;
    }

    /**
     * Get the formatted call end time.
     */
    public function getFormattedCallEndTimeAttribute(): ?string
    {
        return $this->call_end_time ? \Carbon\Carbon::parse($this->call_end_time)->format('h:i A') : null;
    }

    /**
     * Get the full call time range.
     */
    public function getCallTimeRangeAttribute(): ?string
    {
        if (!$this->call_start_time || !$this->call_end_time) {
            return null;
        }
        return "{$this->formatted_call_start_time} - {$this->formatted_call_end_time}";
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope a query to only include active portfolios.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include inactive portfolios.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope a query to filter by company.
     */
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Portfolio Field Relation
     */
    public function portfolioFields()
    {
        return $this->hasMany(PortfolioField::class);
    }


    public function fields()
    {
        return $this->portfolioFields();
    }
}
