<?php

namespace App\Models\Tenants\Assignments;

use App\Enums\Tenants\Assignments\AssignmentAttachmentCategory;
use App\Enums\Tenants\Assignments\AssignmentCategory;
use App\Models\Shared\Attachment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'assignment_number',
        'name',
        'issuing_entity_id',
        'issuing_entity',
        'city',
        'district',
        'category',
        'start_date',
        'end_date',
        'notes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'issuing_entity_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function issuingEntity(): BelongsTo
    {
        return $this->belongsTo(IssuingEntity::class, 'issuing_entity_id');
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'assignment_id');
    }

    public function auctions(): HasMany
    {
        return $this->hasMany(Auction::class, 'assignment_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function assignmentImage(): MorphOne
    {
        return $this->morphOne(Attachment::class, 'attachable')
            ->ofMany(['id' => 'max'], fn (Builder $query): Builder => $query->where(
                'category',
                AssignmentAttachmentCategory::IMAGE->value,
            ));
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return list<string>
     */
    public function getCategoryAttribute($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('strval', $value)));
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map('strval', $decoded)));
            }

            return [$value];
        }

        return [];
    }

    public function setCategoryAttribute(mixed $value): void
    {
        if (is_array($value)) {
            $cleaned = array_values(array_filter(array_unique(array_map(
                static fn ($item): string => $item instanceof \BackedEnum ? (string) $item->value : (string) $item,
                $value
            ))));
            $this->attributes['category'] = json_encode($cleaned);
        } elseif ($value instanceof \BackedEnum) {
            $this->attributes['category'] = json_encode([(string) $value->value]);
        } elseif (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $this->attributes['category'] = json_encode(array_values(array_unique(array_map('strval', $decoded))));
            } else {
                $this->attributes['category'] = json_encode([$value]);
            }
        } else {
            $this->attributes['category'] = json_encode([]);
        }
    }

    /**
     * @return list<string>
     */
    public function getCategoryLabelsAttribute(): array
    {
        $categories = $this->category;
        if (empty($categories)) {
            return ['سكنية'];
        }

        $labels = array_map(function ($cat): string {
            if ($cat instanceof AssignmentCategory) {
                return $cat->label();
            }

            $enum = AssignmentCategory::tryFrom((string) $cat);
            if ($enum) {
                return $enum->label();
            }

            return match ((string) $cat) {
                'residential', 'lands', 'villas' => 'سكنية',
                'commercial', 'commercial_buildings' => 'تجارية',
                'agricultural' => 'زراعية',
                'industrial' => 'صناعية',
                'mixed_use', 'educational' => 'متعدد الأستخدامات',
                default => (string) $cat,
            };
        }, $categories);

        return array_values(array_unique($labels));
    }

    public function getCategoryLabelAttribute(): string
    {
        return implode('، ', $this->category_labels);
    }

    /**
     * @return list<string>
     */
    public function getCityAttribute($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('strval', $value)));
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map('strval', $decoded)));
            }

            return [$value];
        }

        return [];
    }

    public function setCityAttribute(mixed $value): void
    {
        if (is_array($value)) {
            $cleaned = array_values(array_filter(array_unique(array_map(
                static fn ($item): string => trim((string) $item),
                $value
            )), static fn (string $v): bool => $v !== ''));
            $this->attributes['city'] = json_encode($cleaned, JSON_UNESCAPED_UNICODE);
        } elseif (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $cleaned = array_values(array_filter(array_unique(array_map(
                    static fn ($item): string => trim((string) $item),
                    $decoded
                )), static fn (string $v): bool => $v !== ''));
                $this->attributes['city'] = json_encode($cleaned, JSON_UNESCAPED_UNICODE);
            } else {
                $trimmed = trim($value);
                $this->attributes['city'] = $trimmed !== '' ? json_encode([$trimmed], JSON_UNESCAPED_UNICODE) : json_encode([]);
            }
        } else {
            $this->attributes['city'] = json_encode([]);
        }
    }

    public function getCityLabelAttribute(): string
    {
        return implode('، ', $this->city);
    }

    /**
     * @return list<string>
     */
    public function getDistrictAttribute($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('strval', $value)));
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map('strval', $decoded)));
            }

            return [$value];
        }

        return [];
    }

    public function setDistrictAttribute(mixed $value): void
    {
        if (is_array($value)) {
            $cleaned = array_values(array_filter(array_unique(array_map(
                static fn ($item): string => trim((string) $item),
                $value
            )), static fn (string $v): bool => $v !== ''));
            $this->attributes['district'] = json_encode($cleaned, JSON_UNESCAPED_UNICODE);
        } elseif (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $cleaned = array_values(array_filter(array_unique(array_map(
                    static fn ($item): string => trim((string) $item),
                    $decoded
                )), static fn (string $v): bool => $v !== ''));
                $this->attributes['district'] = json_encode($cleaned, JSON_UNESCAPED_UNICODE);
            } else {
                $trimmed = trim($value);
                $this->attributes['district'] = $trimmed !== '' ? json_encode([$trimmed], JSON_UNESCAPED_UNICODE) : json_encode([]);
            }
        } else {
            $this->attributes['district'] = json_encode([]);
        }
    }

    public function getDistrictLabelAttribute(): string
    {
        return implode('، ', $this->district);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'نشط' : 'متوقف';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->is_active ? 'bg-success' : 'bg-danger';
    }
}
