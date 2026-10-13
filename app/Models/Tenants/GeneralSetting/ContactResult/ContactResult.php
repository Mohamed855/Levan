<?php

namespace App\Models\Tenants\GeneralSetting\ContactResult;

use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactResult extends Model
{
    use HasFactory;

    protected $table = 'contact_results';

    protected $fillable = [
        'name',
        'category',
        'status',
        'created_by',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Categories Labels (للعرض فقط)
    |--------------------------------------------------------------------------
    */
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'positive' => 'إيجابية',
            'negative' => 'سلبية',
            'other' => 'أخرى',
            default => 'غير محدد',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status ? 'نشط' : 'غير نشط';
    }

    public function getStatusClassAttribute(): string
    {
        return $this->status ? 'bg-label-success' : 'bg-label-danger';
    }
}
