<?php

// app/Models/Tenants/FinancialCollection/Reconciliation/PaymentReconciliationItem.php

namespace App\Models\Tenants\FinancialCollection\Reconciliation;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentReconciliationItem extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Table Name
    |--------------------------------------------------------------------------
    */
    protected $table = 'payment_reconciliation_items';

    /*
    |--------------------------------------------------------------------------
    | Fillable Fields
    |--------------------------------------------------------------------------
    */
    protected $fillable = [
        'reconciliation_id',
        'account_number',
        'file_amount',
        'file_date',
        'row_number',
        'customer_id',
        'collection_id',
        'match_status',
        'system_amount',
        'system_date',
        'amount_difference',
        'date_difference_days',
        'error_message',
        'notes',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */
    protected $casts = [
        'file_amount' => 'decimal:2',
        'system_amount' => 'decimal:2',
        'amount_difference' => 'decimal:2',
        'file_date' => 'date',
        'system_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Reconciliation Relationship
    |--------------------------------------------------------------------------
    */
    public function reconciliation()
    {
        return $this->belongsTo(PaymentReconciliation::class, 'reconciliation_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Relationship
    |--------------------------------------------------------------------------
    */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Collection Relationship
    |--------------------------------------------------------------------------
    */
    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes Matched
    |--------------------------------------------------------------------------
    */
    public function scopeMatched($query)
    {
        return $query->where('match_status', 'matched');
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Unmatched
    |--------------------------------------------------------------------------
    */
    public function scopeUnmatched($query)
    {
        return $query->where('match_status', 'unmatched');
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Discrepancy
    |--------------------------------------------------------------------------
    */
    public function scopeDiscrepancy($query)
    {
        return $query->where('match_status', 'discrepancy');
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Errors
    |--------------------------------------------------------------------------
    */
    public function scopeErrors($query)
    {
        return $query->where('match_status', 'error');
    }

    /*
    |--------------------------------------------------------------------------
    | Scope By Account Number
    |--------------------------------------------------------------------------
    */
    public function scopeByAccountNumber($query, $accountNumber)
    {
        return $query->where('account_number', $accountNumber);
    }

    /*
    |--------------------------------------------------------------------------
    | Scope By Customer
    |--------------------------------------------------------------------------
    */
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Status Label Attribute
    |--------------------------------------------------------------------------
    */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->match_status) {
            'matched' => 'متطابق',
            'unmatched' => 'غير مسجل',
            'discrepancy' => 'اختلاف',
            'error' => 'خطأ',
            default => $this->match_status
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Get Status Class Attribute
    |--------------------------------------------------------------------------
    */
    public function getStatusClassAttribute(): string
    {
        return match ($this->match_status) {
            'matched' => 'bg-label-success',
            'unmatched' => 'bg-label-warning',
            'discrepancy' => 'bg-label-info',
            'error' => 'bg-label-danger',
            default => 'bg-secondary'
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Get Status Icon Attribute
    |--------------------------------------------------------------------------
    */
    public function getStatusIconAttribute(): string
    {
        return match ($this->match_status) {
            'matched' => 'ti-check-circle',
            'unmatched' => 'ti-alert-circle',
            'discrepancy' => 'ti-info-circle',
            'error' => 'ti-x-circle',
            default => 'ti-help-circle'
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Get Formatted File Amount Attribute
    |--------------------------------------------------------------------------
    */
    public function getFormattedFileAmountAttribute(): string
    {
        return number_format($this->file_amount, 2).' ر.س';
    }

    /*
    |--------------------------------------------------------------------------
    | Get Formatted System Amount Attribute
    |--------------------------------------------------------------------------
    */
    public function getFormattedSystemAmountAttribute(): string
    {
        if ($this->system_amount === null) {
            return '---';
        }

        return number_format($this->system_amount, 2).' ر.س';
    }

    /*
    |--------------------------------------------------------------------------
    | Get Formatted Amount Difference Attribute
    |--------------------------------------------------------------------------
    */
    public function getFormattedAmountDifferenceAttribute(): string
    {
        if ($this->amount_difference === null) {
            return '---';
        }

        $sign = $this->amount_difference >= 0 ? '+' : '';

        return $sign.number_format($this->amount_difference, 2).' ر.س';
    }

    /*
    |--------------------------------------------------------------------------
    | Get Formatted File Date Attribute
    |--------------------------------------------------------------------------
    */
    public function getFormattedFileDateAttribute(): string
    {
        return $this->file_date?->locale('ar')->translatedFormat('d F Y') ?? 'غير محدد';
    }

    /*
    |--------------------------------------------------------------------------
    | Get Formatted System Date Attribute
    |--------------------------------------------------------------------------
    */
    public function getFormattedSystemDateAttribute(): string
    {
        if ($this->system_date === null) {
            return '---';
        }

        return $this->system_date->locale('ar')->translatedFormat('d F Y');
    }

    /*
    |--------------------------------------------------------------------------
    | Get Has Amount Difference Attribute
    |--------------------------------------------------------------------------
    */
    public function getHasAmountDifferenceAttribute(): bool
    {
        return $this->amount_difference !== null && abs($this->amount_difference) > 0.01;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Has Date Difference Attribute
    |--------------------------------------------------------------------------
    */
    public function getHasDateDifferenceAttribute(): bool
    {
        return $this->date_difference_days !== null && $this->date_difference_days != 0;
    }
}
