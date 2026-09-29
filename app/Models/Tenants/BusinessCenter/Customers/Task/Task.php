<?php

namespace App\Models\Tenants\BusinessCenter\Customers\Task;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Follow\CustomerFollow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'follow_up_id',
        'task_type',
        'due_date',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    /**
     * أنواع المهام المتاحة
     */
    const TASK_TYPES = [
        'promise' => 'وعد بالسداد',
        'callback' => 'معاودة الاتصال',
        'document_request' => 'طلب مستندات',
    ];

    /**
     * حالات المهام المتاحة
     */
    const STATUSES = [
        'pending' => 'معلقة',
        'completed' => 'مكتملة',
        'cancelled' => 'ملغاة',
    ];

    /**
     * العلاقة مع العميل
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * العلاقة مع المتابعة الأصلية
     */
    public function followUp(): BelongsTo
    {
        return $this->belongsTo(CustomerFollow::class, 'follow_up_id');
    }

    /**
     * الحصول على مسؤول العلاقة للعميل (ديناميكي)
     */
    public function getRelationshipManagerAttribute()
    {
        return $this->customer?->relationshipManager;
    }

    /**
     * الحصول على اسم مسؤول العلاقة
     */
    public function getRelationshipManagerNameAttribute()
    {
        return $this->customer?->relationshipManager?->name ?? 'غير محدد';
    }

    /**
     * الحصول على اسم نوع المهمة
     */
    public function getTaskTypeNameAttribute()
    {
        return self::TASK_TYPES[$this->task_type] ?? 'غير محدد';
    }

    /**
     * الحصول على اسم الحالة
     */
    public function getStatusNameAttribute()
    {
        return self::STATUSES[$this->status] ?? 'غير محدد';
    }

    /**
     * تحديد ما إذا كانت المهمة متأخرة
     */
    public function getIsOverdueAttribute()
    {
        return $this->status === 'pending' && $this->due_date < now()->toDateString();
    }

    /**
     * تحديد ما إذا كانت المهمة مستحقة اليوم
     */
    public function getIsDueTodayAttribute()
    {
        return $this->status === 'pending' && $this->due_date === now()->toDateString();
    }

    /**
     * تحديد ما إذا كانت المهمة مستحقة خلال 3 أيام
     */
    public function getIsDueSoonAttribute()
    {
        return $this->status === 'pending' && 
               $this->due_date >= now()->toDateString() && 
               $this->due_date <= now()->addDays(3)->toDateString();
    }

    /**
     * نطاق للمهام المعلقة
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * نطاق للمهام المكتملة
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * نطاق للمهام المستحقة اليوم
     */
    public function scopeDueToday($query)
    {
        return $query->where('due_date', now()->toDateString());
    }

    /**
     * نطاق للمهام المتأخرة
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now()->toDateString())
                    ->where('status', 'pending');
    }

    /**
     * نطاق للمهام المستحقة خلال فترة
     */
    public function scopeDueBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('due_date', [$startDate, $endDate]);
    }

    /**
     * نطاق للمهام الخاصة بموظف معين
     */
    public function scopeForRelationshipManager($query, $managerId)
    {
        return $query->whereHas('customer', function ($customerQuery) use ($managerId) {
            $customerQuery->where('relationship_manager_id', $managerId);
        });
    }

    /**
     * إكمال المهمة
     */
    public function complete()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * إلغاء المهمة
     */
    public function cancel()
    {
        $this->update([
            'status' => 'cancelled',
        ]);
    }
}