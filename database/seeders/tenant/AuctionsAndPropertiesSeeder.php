<?php

declare(strict_types=1);

namespace Database\Seeders\tenant;

use Illuminate\Database\Seeder;

/**
 * @deprecated This seeder has been retired. Assignment, property, and auction demo data
 *             is no longer seeded automatically. Remove any references to this class.
 */
class AuctionsAndPropertiesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn(
            'AuctionsAndPropertiesSeeder has been retired and no longer seeds any data. '.
            'Remove all calls to this seeder.'
        );
    }
}
