<?php

declare(strict_types=1);

namespace Database\Seeders\tenant;

use Illuminate\Database\Seeder;

/**
 * @deprecated This seeder has been retired. Auction workflow scenario data
 *             (establishment requests, closings) is no longer seeded automatically.
 *             Remove any references to this class.
 */
final class AuctionWorkflowScenariosSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn(
            'AuctionWorkflowScenariosSeeder has been retired and no longer seeds any data. '.
            'Remove all calls to this seeder.'
        );
    }
}
