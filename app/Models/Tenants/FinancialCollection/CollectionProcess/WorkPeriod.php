<?php

namespace App\Models\Tenants\FinancialCollection\CollectionProcess;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkPeriod extends Model
{
    protected $table = 'work_periods';

    protected $connection = 'tenant';

    protected $fillable = [
        'session_id',
        'entry_time',
        'exit_time',
        'exit_type',
        'duration_minutes',
        'activities_count',
        'follows_count',
        'full_payments_count',
        'partial_payments_count',
    ];

    protected $casts = [
        'entry_time'             => 'datetime',
        'exit_time'              => 'datetime',
        'duration_minutes'       => 'integer',
        'activities_count'       => 'integer',
        'follows_count'          => 'integer',
        'full_payments_count'    => 'integer',
        'partial_payments_count' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | العلاقات
    |--------------------------------------------------------------------------
    */

    public function session(): BelongsTo
    {
        return $this->belongsTo(CollectionProcessSession::class, 'session_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    // public function scopeOpen($query)
    // {
    //     return $query->whereNull('exit_time');
    // }

    // public function scopeClosed($query)
    // {
    //     return $query->whereNotNull('exit_time');
    // }

    // public function scopeForSession($query, int $sessionId)
    // {
    //     return $query->where('session_id', $sessionId);
    // }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    // public function getFormattedDurationAttribute(): string
    // {
    //     $hours = floor($this->duration_minutes / 60);
    //     $minutes = $this->duration_minutes % 60;
    //     return sprintf('%02d:%02d', $hours, $minutes);
    // }

    // public function getCurrentDurationAttribute(): int
    // {
    //     if ($this->exit_time) {
    //         return $this->duration_minutes;
    //     }

    //     return $this->entry_time->diffInMinutes(now());
    // }

    /*
    |============================================================================
    |============================================================================
    |                             Accessors
    |============================================================================
    |============================================================================
    */
    public function incrementActivity(string $type): void
    {
        $this->increment('activities_count');

        if ($type === 'follow') {
            $this->increment('follows_count');
        }
    }

    public function incrementPaymentType(string $type): void
    {
        if ($type === 'full_payment') {
            $this->increment('full_payments_count');
        } elseif ($type === 'partial_payment') {
            $this->increment('partial_payments_count');
        }
    }

    // إغلاق فترة العمل
    public function close(string $exitType, ?\DateTime $exitTime = null): void
    {
        $exitTime = $exitTime ?? now();
        $duration = $this->entry_time->diffInMinutes($exitTime);

        $this->update([
            'exit_time'        => $exitTime,
            'exit_type'        => $exitType,
            'duration_minutes' => $duration,
        ]);
    }
}