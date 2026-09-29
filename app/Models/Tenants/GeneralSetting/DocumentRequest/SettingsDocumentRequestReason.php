<?php

namespace App\Models\Tenants\GeneralSetting\DocumentRequest;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\SoftDeletes;

class SettingsDocumentRequestReason extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'settings_document_request_reasons';

    protected $fillable = [
        'name',
        'status',
        'user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | for get user add document request reason
    |--------------------------------------------------------------------------
    */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }
}