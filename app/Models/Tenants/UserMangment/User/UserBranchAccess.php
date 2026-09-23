<?php

namespace App\Models\Tenants\UserMangment\User;

use App\Models\Tenants\Branch\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBranchAccess extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Properties
    |--------------------------------------------------------------------------
    */    
    protected $table = 'user_branch_access';

    protected $fillable = [
        'user_id',
        'branch_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}