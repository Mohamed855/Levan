<?php

namespace App\Models\Tenants\BusinessCenter\Customers\Follow;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Letter\CompanyLetter;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use App\Models\Tenants\GeneralSetting\ContactMethod\SettingsContactMethod;
use App\Models\Tenants\GeneralSetting\ContactResult\ContactResult;
use App\Models\Tenants\GeneralSetting\CustomerStatus\SettingsCustomerStatus;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class CustomerFollow extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'customer_follows';

    protected $fillable = [
        'customer_id',
        'contact_method_id',
        'contact_location',
        'contacted_person',
        'contacted_person_name',
        'next_contact_datetime',
        'customer_status_id',
        'contact_result_id',
        'collection_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'next_contact_datetime' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Activity Log Options
    |--------------------------------------------------------------------------
    */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'contact_method_id',
                'contact_result_id',
                'customer_status_id',
                'next_contact_datetime',
                'contacted_person',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('customer_follow')
            ->setDescriptionForEvent(fn (string $eventName) => $this->getEventDescription($eventName));
    }

    /*
    |--------------------------------------------------------------------------
    | Get Event Description
    |--------------------------------------------------------------------------
    */
    private function getEventDescription(string $eventName): string
    {
        $customerName = $this->customer->name ?? "عميل #{$this->customer_id}";

        $method = $this->contactMethod?->name ?? '-';
        $result = $this->contactResult?->name ?? '-';
        $status = $this->customerStatus?->name ?? '-';
        $nextDate = $this->next_contact_datetime ? $this->next_contact_datetime->format('Y-m-d') : '-';

        $person = match ($this->contacted_person) {
            'customer' => 'العميل نفسه',
            'relative' => 'أحد أقارب العميل',
            default => 'غير محدد'
        };

        $notes = $this->notes ?: 'لا توجد';

        $details = " (طريقة التواصل: {$method} – النتيجة: {$result} – الحالة: {$status} – التاريخ القادم: {$nextDate} – الشخص: {$person} – الملاحظات: {$notes})";

        return match ($eventName) {
            'created' => "تم إنشاء متابعة جديدة للعميل {$customerName}{$details}",
            'updated' => "تم تعديل متابعة العميل {$customerName}{$details}",
            'deleted' => "تم حذف متابعة العميل {$customerName}",
            default => "نشاط على متابعة العميل {$customerName}{$details}",
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Get Changes Summary
    |--------------------------------------------------------------------------
    */
    private function getChangesSummary(): string
    {
        if (! $this->isDirty()) {
            return '';
        }

        $changes = [];

        if ($this->isDirty('contact_method_id')) {
            $oldName = SettingsContactMethod::find($this->getOriginal('contact_method_id'))?->name ?? '---';
            $newName = $this->contactMethod?->name ?? '---';
            $changes[] = "طريقة التواصل: {$newName} ← {$oldName}";
        }

        if ($this->isDirty('contact_result_id')) {
            $oldName = ContactResult::find($this->getOriginal('contact_result_id'))?->name ?? '---';
            $newName = $this->contactResult?->name ?? '---';
            $changes[] = "نتيجة الاتصال: {$newName} ← {$oldName}";
        }

        if ($this->isDirty('customer_status_id')) {
            $oldName = SettingsCustomerStatus::find($this->getOriginal('customer_status_id'))?->name ?? '---';
            $newName = $this->customerStatus?->name ?? '---';
            $changes[] = "حالة العميل: {$newName} ← {$oldName}";
        }

        if ($this->isDirty('next_contact_datetime')) {
            $oldDate = $this->getOriginal('next_contact_datetime')
                ? date('Y-m-d', strtotime($this->getOriginal('next_contact_datetime')))
                : '---';
            $newDate = $this->next_contact_datetime
                ? $this->next_contact_datetime->format('Y-m-d')
                : '---';
            $changes[] = "موعد الاتصال القادم: {$newDate} ← {$oldDate}";
        }

        return empty($changes) ? '' : ' ('.implode(' | ', $changes).')';
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function contactMethod()
    {
        return $this->belongsTo(SettingsContactMethod::class, 'contact_method_id');
    }

    public function customerStatus()
    {
        return $this->belongsTo(SettingsCustomerStatus::class, 'customer_status_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contactResult()
    {
        return $this->belongsTo(ContactResult::class);
    }

    public function fullPaymentDetails()
    {
        return $this->hasOne(FullPaymentDetails::class, 'contact_result_id');
    }

    public function partialPaymentDetails()
    {
        return $this->hasOne(PartialPaymentDetails::class, 'contact_result_id');
    }

    public function promisePaymentDetails()
    {
        return $this->hasOne(PromisePaymentDetails::class, 'contact_result_id');
    }

    public function callbackDetails()
    {
        return $this->hasOne(CallbackDetails::class, 'contact_result_id');
    }

    public function documentRequestDetails()
    {
        return $this->hasOne(DocumentRequestDetails::class, 'contact_result_id');
    }

    public function objectionDetails()
    {
        return $this->hasOne(ObjectionDetails::class, 'contact_result_id');
    }

    public function recoveryDetails()
    {
        return $this->hasOne(RecoveryDetails::class, 'contact_result_id');
    }

    public function getFullPaymentDetailsAttribute()
    {
        if (! Schema::hasTable('full_payment_details')) {
            return null;
        }

        return $this->getRelationValue('fullPaymentDetails');
    }

    public function getPartialPaymentDetailsAttribute()
    {
        if (! Schema::hasTable('partial_payment_details')) {
            return null;
        }

        return $this->getRelationValue('partialPaymentDetails');
    }

    public function getPromisePaymentDetailsAttribute()
    {
        if (! Schema::hasTable('promise_payment_details')) {
            return null;
        }

        return $this->getRelationValue('promisePaymentDetails');
    }

    public function getCallbackDetailsAttribute()
    {
        if (! Schema::hasTable('callback_details')) {
            return null;
        }

        return $this->getRelationValue('callbackDetails');
    }

    public function getDocumentRequestDetailsAttribute()
    {
        if (! Schema::hasTable('document_request_details')) {
            return null;
        }

        return $this->getRelationValue('documentRequestDetails');
    }

    public function getObjectionDetailsAttribute()
    {
        if (! Schema::hasTable('objection_details')) {
            return null;
        }

        return $this->getRelationValue('objectionDetails');
    }

    public function getRecoveryDetailsAttribute()
    {
        if (! Schema::hasTable('recovery_details')) {
            return null;
        }

        return $this->getRelationValue('recoveryDetails');
    }

    public function getContactedPersonLabelAttribute(): string
    {
        return match ($this->contacted_person) {
            'customer' => 'العميل نفسه',
            'relative' => 'أحد أقارب العميل',
            default => 'غير محدد'
        };
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function collection()
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function temporaryContactLossDetails()
    {
        return $this->hasOne(TemporaryContactLossDetails::class, 'contact_result_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function scopeScheduledForToday($query)
    {
        return $query->where(function ($q) {
            // وعد بالسداد
            $q->where('contact_result_id', 3)
                ->whereHas('promisePaymentDetails', function ($promiseQuery) {
                    $promiseQuery->whereDate('promise_date', today());
                })
                // معاودة الاتصال
                ->orWhere(function ($callbackQ) {
                    $callbackQ->where('contact_result_id', 4)
                        ->whereHas('callbackDetails', function ($callbackQuery) {
                            $callbackQuery->whereDate('callback_date', today());
                        });
                })
                // طلب مستندات
                ->orWhere(function ($docQ) {
                    $docQ->where('contact_result_id', 9)
                        ->whereHas('documentRequestDetails', function ($docQuery) {
                            $docQuery->whereDate('documents_date', today());
                        });
                });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | علاقة خطاب الشركة المرتبط بالمتابعة
    |--------------------------------------------------------------------------
    */
    public function companyLetter()
    {
        return $this->hasOne(CompanyLetter::class, 'customer_follow_id');
    }
}
