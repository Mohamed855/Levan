<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Auctions;

enum AuctionFilePreparationStatus: string
{
    case NOT_PREPARED = 'not_prepared';
    case FAILED = 'failed';
    case READY = 'ready';

    public function label(): string
    {
        return match ($this) {
            self::NOT_PREPARED => 'لم يتم التجهيز',
            self::FAILED => 'فشل التجهيز',
            self::READY => 'جاهز للتحميل',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NOT_PREPARED => 'secondary',
            self::FAILED => 'danger',
            self::READY => 'success',
        };
    }

    public function badgeClass(): string
    {
        return 'bg-label-'.$this->color();
    }

    public function badgeHtml(): string
    {
        return '<span class="badge '.$this->badgeClass().'">'.e($this->label()).'</span>';
    }

    /**
     * Resolve display status strictly into the three approved preparation states.
     * Queued/processing states remain NOT_PREPARED so the live progress bar reflects activity.
     * Failures surface FAILED even when an earlier published file remains downloadable.
     */
    public static function resolve(?string $latestStatus, bool $hasPublishedFile): self
    {
        if (in_array($latestStatus, ['queued', 'processing'], true)) {
            return self::NOT_PREPARED;
        }

        if ($latestStatus === 'failed') {
            return self::FAILED;
        }

        return $hasPublishedFile ? self::READY : self::NOT_PREPARED;
    }

    /**
     * Client-side badge lookup table keyed by enum value.
     *
     * @return array<string, array{label: string, class: string}>
     */
    public static function clientMap(): array
    {
        $map = [];
        foreach (self::cases() as $case) {
            $map[$case->value] = [
                'label' => $case->label(),
                'class' => 'badge '.$case->badgeClass(),
            ];
        }

        return $map;
    }
}
