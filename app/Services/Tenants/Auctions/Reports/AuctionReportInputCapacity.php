<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

use InvalidArgumentException;

final class AuctionReportInputCapacity
{
    public function fits(string $field, string $value): bool
    {
        $artwork = app(AuctionReportArtwork::class);
        $layout = app(AuctionReportTextLayout::class);
        try {
            foreach (config('auction-marketing-fields.positions.'.$field, []) as [$design, $index, $role]) {
                $definition = $artwork->page($design, $index);
                $definition['groups'] = [$role => $definition['groups'][$role]];
                $layout->flow(['values' => [$role => $value], 'tables' => [], 'images' => []], $definition);
            }
            if ($field === 'board_dimensions') {
                foreach (['haram' => 5, 'asir' => 6] as $design => $columns) {
                    $definition = $artwork->page($design, 1);
                    $definition['groups'] = [];
                    $row = array_fill(0, $columns, '');
                    $row[0] = $value;
                    $layout->flow(['values' => [], 'tables' => ['properties' => [$row]], 'images' => []], $definition);
                }
            }
        } catch (InvalidArgumentException) {
            return false;
        }

        return true;
    }
}
