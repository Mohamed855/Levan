<?php

namespace App\Models\Tenants\BusinessCenter\Customers\Comment;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerComment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'user_id',
        'comment_text',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(CustomerCommentMention::class);
    }
}