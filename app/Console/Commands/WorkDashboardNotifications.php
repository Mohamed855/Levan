<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class WorkDashboardNotifications extends Command
{
    protected $signature = 'dashboard-notifications:work {--once}';

    protected $description = 'Run the local dashboard notification deadline scanner every minute';

    public function handle(): int
    {
        do {
            try {
                $this->call('dashboard-notifications:scan');
            } catch (\Throwable $exception) {
                report($exception);
                $this->error('Notification scan failed; retrying next minute.');
            }
            if ($this->option('once')) {
                break;
            }
            sleep(60);
        } while (true);

        return self::SUCCESS;
    }
}
