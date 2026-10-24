<?php

declare(strict_types=1);

namespace App\Listeners;

use Stancl\JobPipeline\JobPipeline;
use Stancl\Tenancy\Events\TenantDeleted;
use Stancl\Tenancy\Jobs\DeleteDatabase;

final class DeleteTenantDatabaseAfterForceDelete
{
    public function handle(TenantDeleted $event): void
    {
        $tenant = $event->tenant;
        if (! method_exists($tenant, 'isForceDeleting') || ! $tenant->isForceDeleting()) {
            return;
        }

        JobPipeline::make([DeleteDatabase::class])
            ->send(static fn (TenantDeleted $event) => $event->tenant)
            ->shouldBeQueued(false)
            ->toListener()($event);
    }
}
