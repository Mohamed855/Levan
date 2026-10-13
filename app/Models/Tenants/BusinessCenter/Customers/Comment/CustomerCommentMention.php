<?php

namespace App\Models\Tenants\BusinessCenter\Customers\Comment;

use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerCommentMention extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_comment_id',
        'mentioned_user_id',
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
    public function comment(): BelongsTo
    {
        return $this->belongsTo(CustomerComment::class, 'customer_comment_id');
    }

    public function mentionedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentioned_user_id');
    }
}
