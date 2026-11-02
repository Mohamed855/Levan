<?php

declare(strict_types=1);

namespace App\Models\Tenants\PromotionalAdvertisements;

use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\UserMangment\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromotionalAdvertisement extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'promotional_advertisements';

    protected $fillable = [
        'auction_id',
        'auction_template_id',
        'template_type',
        'template_name',
        'status',
        'output_path',
        'error_message',
        'created_by',
    ];

    protected $casts = [
        'auction_id' => 'integer',
        'auction_template_id' => 'integer',
        'created_by' => 'integer',
    ];

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class, 'auction_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function isProcessing(): bool
    {
        return in_array($this->status, ['processing', 'pending'], true);
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->template_type) {
            'billboard' => 'قالب اللوحة الإعلانية',
            'social' => 'قالب منصات التواصل الاجتماعي',
            default => (string) $this->template_type,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'ready' => 'جاهز للتحميل',
            'processing' => 'قيد التجهيز',
            'pending' => 'قيد الانتظار',
            'failed' => 'فشل التجهيز',
            default => (string) $this->status,
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'ready' => 'success',
            'processing' => 'info',
            'pending' => 'warning',
            'failed' => 'danger',
            default => 'secondary',
        };
    }
}
