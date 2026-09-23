<?php

namespace App\Models\Tenants\FinancialCollection\CollectionProcess;

use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollectionProcessSession extends Model
{
    protected $table = 'collection_process_sessions';

    protected $fillable = [
        'user_id',
        'date',
        'first_entry_time',
        'last_exit_time',
        'total_work_minutes',
        'total_activities',
        'total_follows',
        'total_full_payments',
        'total_partial_payments',
        'is_active',
    ];

    protected $casts = [
        'date'                   => 'date',
        'first_entry_time'       => 'datetime',
        'last_exit_time'         => 'datetime',
        'total_work_minutes'     => 'integer',
        'total_activities'       => 'integer',
        'total_follows'          => 'integer',
        'total_full_payments'    => 'integer',
        'total_partial_payments' => 'integer',
        'is_active'              => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | العلاقات
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workPeriods(): HasMany
    {
        return $this->hasMany(WorkPeriod::class, 'session_id');
    }


    /*
    |============================================================================
    |============================================================================
    |                             Accessors
    |============================================================================
    |============================================================================
    */
    public function incrementActivity(string $type): void
    {
        $this->increment('total_activities');

        if ($type === 'follow') {
            $this->increment('total_follows');
        }
    }

    public function incrementPaymentType(string $type): void
    {
        if ($type === 'full_payment') {
            $this->increment('total_full_payments');
        } elseif ($type === 'partial_payment') {
            $this->increment('total_partial_payments');
        }
    }
}
