<?php

namespace App\Models\Tenants\BusinessCenter\Customers\History;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\Model;

class CustomerRelationshipHistory extends Model
{
    protected $table = 'customer_relationship_history';

    protected $fillable = [
        'customer_id',
        'previous_manager_id',
        'new_manager_id',
        'changed_by',
        'change_type',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function previousManager()
    {
        return $this->belongsTo(User::class, 'previous_manager_id');
    }

    public function newManager()
    {
        return $this->belongsTo(User::class, 'new_manager_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}