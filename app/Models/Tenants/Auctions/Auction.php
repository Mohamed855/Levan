<?php

declare(strict_types=1);

namespace App\Models\Tenants\Auctions;

use App\Enums\Tenants\Auctions\AuctionStatus;
use App\Enums\Tenants\Auctions\AuctionReportContentStatus;
use App\Services\Tenants\Auctions\Reports\AuctionReportState;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\AuctionClosings\AuctionClosing;
use App\Models\Tenants\AuctionEstablishments\AuctionEstablishmentRequest;
use App\Models\Tenants\MarketingCampaigns\MarketingCampaign;
use App\Models\Tenants\PromotionalAdvertisements\PromotionalAdvertisement;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Auction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'assignment_id',
        'name',
        'description',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
        'type',
        'category',
        'city',
        'expected_properties_count',
        'supervising_entity',
        'project_manager_name',
        'platform_name',
        'platform_url',
        'notes',
        'cover_image',
        'report_image',
        'marketing_data',
        'is_active',
        'created_by',
        'published_report_generation_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'expected_properties_count' => 'integer',
        'marketing_data' => 'array',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'auction_properties', 'auction_id', 'property_id')
            ->withPivot([
                'sort_order',
                'result_status',
                'opening_price',
                'sale_price',
                'participants_count',
                'bids_count',
                'unsold_reason',
                'board_data',
                'buyer_name',
                'result_notes',
            ])
            ->orderByPivot('sort_order')
            ->withTimestamps();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(AuctionAttachment::class, 'auction_id');
    }

    public function marketingCampaigns(): HasMany
    {
        return $this->hasMany(MarketingCampaign::class)->orderBy('id');
    }

    public function closing(): HasOne
    {
        return $this->hasOne(AuctionClosing::class);
    }

    public function establishmentRequest(): HasOne
    {
        return $this->hasOne(AuctionEstablishmentRequest::class);
    }

    public function publishedReportGeneration(): BelongsTo
    {
        return $this->belongsTo(AuctionReportGeneration::class, 'published_report_generation_id');
    }

    public function reportGenerations(): HasMany
    {
        return $this->hasMany(AuctionReportGeneration::class)->latest('id');
    }

    public function latestReportGeneration()
    {
        return $this->hasOne(AuctionReportGeneration::class)->latestOfMany('id');
    }

    public function promotionalAdvertisements(): HasMany
    {
        return $this->hasMany(PromotionalAdvertisement::class, 'auction_id')->latest('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        $type = $this->type instanceof \BackedEnum ? $this->type->value : (string) ($this->type ?? '');

        return match ($type) {
            'electronic' => 'إلكتروني',
            'in_person' => 'حضوري',
            'hybrid' => 'هجين',
            default => 'إلكتروني',
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        if ($this->category instanceof \App\Enums\Tenants\Auctions\AuctionCategory) {
            return $this->category->label();
        }

        $enum = \App\Enums\Tenants\Auctions\AuctionCategory::tryFrom((string) $this->category);
        if ($enum) {
            return $enum->label();
        }

        return match ($this->category) {
            'residential', 'lands', 'villas' => 'سكنية',
            'commercial', 'commercial_buildings' => 'تجارية',
            'agricultural' => 'زراعية',
            'industrial' => 'صناعية',
            'mixed_use', 'educational' => 'متعدد الأستخدامات',
            default => (string) ($this->category ?? 'سكنية'),
        };
    }

    public function getStartDateTime(): ?Carbon
    {
        if (! $this->start_date) {
            return null;
        }

        $time = $this->start_time ?: '00:00:00';

        return Carbon::parse($this->start_date->format('Y-m-d').' '.$time);
    }

    public function getEndDateTime(): ?Carbon
    {
        if (! $this->end_date) {
            return null;
        }

        $time = $this->end_time ?: '23:59:59';

        return Carbon::parse($this->end_date->format('Y-m-d').' '.$time);
    }

    public function getStatusAttribute(): AuctionStatus
    {
        if ($this->properties->isNotEmpty() && $this->properties->every(fn ($property) => $property->pivot->result_status === 'sold')) {
            return AuctionStatus::CLOSED;
        }

        return $this->establishmentRequest?->isApproved() ? AuctionStatus::APPROVED : AuctionStatus::PENDING;
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    public function getCompletionStatusAttribute(): AuctionReportContentStatus
    {
        return app(AuctionReportState::class)->forAuction($this);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->status->badgeClass();
    }

    public function getStatusLabelBadgeClassAttribute(): string
    {
        return $this->status->labelBadgeClass();
    }

    public function isClosed(): bool
    {
        return $this->status === AuctionStatus::CLOSED;
    }

    public function isApproved(): bool
    {
        return $this->status === AuctionStatus::APPROVED;
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->open()->whereDoesntHave('establishmentRequest', fn (Builder $request) => $request->whereNotNull('approved_at')->whereRaw("TRIM(COALESCE(approval_number, '')) <> ''"));
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->has('properties')
            ->whereDoesntHave('properties', fn (Builder $properties) => $properties->where(fn (Builder $results) => $results->whereNull('auction_properties.result_status')->orWhere('auction_properties.result_status', '!=', 'sold')));
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->open()->whereHas('establishmentRequest', fn (Builder $request) => $request->whereNotNull('approved_at')->whereRaw("TRIM(COALESCE(approval_number, '')) <> ''"));
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where(fn (Builder $properties) => $properties->doesntHave('properties')
            ->orWhereHas('properties', fn (Builder $results) => $results->where(fn (Builder $status) => $status->whereNull('auction_properties.result_status')->orWhere('auction_properties.result_status', '!=', 'sold'))));
    }
}
