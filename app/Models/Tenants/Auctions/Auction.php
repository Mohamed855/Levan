<?php

declare(strict_types=1);

namespace App\Models\Tenants\Auctions;

use App\Enums\Tenants\Auctions\AuctionStatus;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\AuctionClosings\AuctionClosing;
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
        return match ($this->category) {
            'lands' => 'أراضي',
            'villas' => 'فلل سكنية',
            'commercial_buildings' => 'عمائر تجارية',
            'educational' => 'مباني تعليمية',
            'industrial' => 'أراضي صناعية',
            'residential' => 'سكني',
            'commercial' => 'تجاري',
            'investment' => 'استثماري',
            'mixed_use' => 'متعدد الاستخدام',
            default => $this->category ?? 'أراضي',
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
        if (! $this->is_active) {
            return AuctionStatus::SUSPENDED;
        }

        $now = Carbon::now();

        $endDateTime = $this->getEndDateTime();
        if ($endDateTime && $now->greaterThanOrEqualTo($endDateTime)) {
            return AuctionStatus::CLOSED;
        }

        $startDateTime = $this->getStartDateTime();
        if ($startDateTime && $now->lessThan($startDateTime)) {
            return AuctionStatus::UPCOMING;
        }

        return AuctionStatus::ACTIVE;
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
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

    public function isSuspended(): bool
    {
        return $this->status === AuctionStatus::SUSPENDED;
    }

    public function isUpcoming(): bool
    {
        return $this->status === AuctionStatus::UPCOMING;
    }

    public function isActive(): bool
    {
        return $this->status === AuctionStatus::ACTIVE;
    }

    public function scopeSuspended(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function scopeClosed(Builder $query): Builder
    {
        $today = Carbon::today()->toDateString();
        $nowTime = Carbon::now()->format('H:i:s');

        return $query->where('is_active', true)
            ->where(function (Builder $q) use ($today, $nowTime): void {
                $q->whereDate('end_date', '<', $today)
                    ->orWhere(function (Builder $sub) use ($today, $nowTime): void {
                        $sub->whereDate('end_date', '=', $today)
                            ->whereNotNull('end_time')
                            ->where('end_time', '<=', $nowTime);
                    });
            });
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        $today = Carbon::today()->toDateString();
        $nowTime = Carbon::now()->format('H:i:s');

        return $query->where('is_active', true)
            ->where(function (Builder $q) use ($today, $nowTime): void {
                $q->whereDate('start_date', '>', $today)
                    ->orWhere(function (Builder $sub) use ($today, $nowTime): void {
                        $sub->whereDate('start_date', '=', $today)
                            ->whereNotNull('start_time')
                            ->where('start_time', '>', $nowTime);
                    });
            });
    }

    public function scopeActiveStatus(Builder $query): Builder
    {
        $today = Carbon::today()->toDateString();
        $nowTime = Carbon::now()->format('H:i:s');

        return $query->where('is_active', true)
            ->where(function (Builder $q) use ($today, $nowTime): void {
                $q->whereDate('end_date', '>', $today)
                    ->orWhere(function (Builder $sub) use ($today, $nowTime): void {
                        $sub->whereDate('end_date', '=', $today)
                            ->where(function (Builder $t) use ($nowTime): void {
                                $t->whereNull('end_time')
                                    ->orWhere('end_time', '>', $nowTime);
                            });
                    })
                    ->orWhereNull('end_date');
            })
            ->where(function (Builder $q) use ($today, $nowTime): void {
                $q->whereDate('start_date', '<', $today)
                    ->orWhere(function (Builder $sub) use ($today, $nowTime): void {
                        $sub->whereDate('start_date', '=', $today)
                            ->where(function (Builder $t) use ($nowTime): void {
                                $t->whereNull('start_time')
                                    ->orWhere('start_time', '<=', $nowTime);
                            });
                    })
                    ->orWhereNull('start_date');
            });
    }
}
