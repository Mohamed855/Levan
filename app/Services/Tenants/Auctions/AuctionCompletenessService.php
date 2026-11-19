<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions;

use App\Models\Tenants\Auctions\Auction;
use Illuminate\Database\Eloquent\Collection;

final class AuctionCompletenessService
{
    public function isComplete(Auction $auction): bool
    {
        if ($auction->exists) {
            $auction->loadMissing(['properties:id', 'attachments']);
        }

        // Step 1: Basic Info & Assignment
        foreach ([
            'name',
            'description',
            'assignment_id',
            'supervising_entity',
            'start_date',
            'start_time',
            'end_date',
            'end_time',
            'type',
            'category',
            'city',
            'expected_properties_count',
        ] as $field) {
            if (blank($auction->{$field})) {
                return false;
            }
        }

        // Step 2: Platform & Project Management
        foreach ([
            'platform_entity_id',
            'platform_url',
            'project_manager_name',
            'notes',
        ] as $field) {
            if (blank($auction->{$field})) {
                return false;
            }
        }

        // Step 3: Linked Properties
        if ($auction->properties->isEmpty()) {
            return false;
        }

        // Step 4: Marketing (all text, regions, links, brokers & evidence)
        $marketing = is_array($auction->marketing_data) ? $auction->marketing_data : [];
        foreach ([
            'planning_methodology',
            'brochure_methodology',
            'audience_methodology',
            'messages_methodology',
            'brochure_context',
            'brochure_url',
            'board_context',
            'board_url',
            'contract_text',
            'introduction',
            'report_summary',
        ] as $key) {
            if (blank($marketing[$key] ?? null)) {
                return false;
            }
        }

        $cityContext = $marketing['city_context'] ?? null;
        if (is_array($cityContext)) {
            $cityContext = array_values(array_filter($cityContext, static fn (mixed $item): bool => is_string($item) && trim($item) !== ''));
            if ($cityContext === []) {
                return false;
            }
        } elseif (blank($cityContext)) {
            return false;
        }

        $attachments = $auction->attachments;
        if (! $attachments->contains(static fn ($file): bool => $file->report_section === 'brochure')) {
            return false;
        }
        if (! $attachments->contains(static fn ($file): bool => $file->report_section === 'platform')) {
            return false;
        }

        $brokers = is_array($marketing['brokers'] ?? null) ? $marketing['brokers'] : [];
        $validBrokers = array_values(array_filter($brokers, static fn (mixed $row): bool => is_array($row) && filled($row['name'] ?? null)));
        if ($validBrokers === []) {
            return false;
        }
        $brokerAttachments = $attachments->where('report_section', 'brokers');
        if ($brokerAttachments->isEmpty()) {
            return false;
        }
        foreach ($validBrokers as $broker) {
            $key = $broker['key'] ?? null;
            if (filled($key) && ! $brokerAttachments->contains(static fn ($file): bool => ($file->report_metadata['broker_key'] ?? null) === $key)) {
                return false;
            }
        }

        // Step 5: Reports & Attachments (cover_image, report_image, additional attachments)
        if (blank($auction->report_image) || blank($auction->cover_image)) {
            return false;
        }
        if (! $attachments->contains(static fn ($file): bool => $file->category === 'auction_doc')) {
            return false;
        }

        return true;
    }

    /** @return list<int> */
    public function completeAuctionIds(): array
    {
        $ids = [];
        Auction::query()
            ->with(['properties:id', 'attachments'])
            ->chunkById(200, function (Collection $auctions) use (&$ids): void {
                foreach ($auctions as $auction) {
                    if ($this->isComplete($auction)) {
                        $ids[] = (int) $auction->id;
                    }
                }
            });

        return $ids;
    }
}
