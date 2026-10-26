<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Shared\Notifications\NotificationDeadlineScanner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ScanDashboardNotifications extends Command
{
    protected $signature = 'dashboard-notifications:scan';

    protected $description = 'Deliver due subscription/coupon alerts and retry pending notification deliveries';

    public function handle(NotificationDeadlineScanner $scanner): int
    {
        Cache::lock('dashboard-notifications:scan', 300)->get(fn () => $scanner->scan());

        return self::SUCCESS;
    }
}
