<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;

final class DashboardPlatformActivityContractTest extends TestCase
{
    public function test_platform_activity_combines_pending_and_active_subscriptions_with_approved_trials(): void
    {
        $service = file_get_contents(dirname(__DIR__, 3).'/app/Services/Admin/Dashboard/DashboardService.php');

        self::assertStringContainsString('SubscriptionStatus::PENDING->value', $service);
        self::assertStringContainsString('SubscriptionStatus::ACTIVE->value', $service);
        self::assertStringContainsString('TrialRequestStatus::APPROVED->value', $service);
        self::assertStringContainsString("->table('trial_requests')", $service);
        self::assertStringContainsString('DATE_FORMAT(processed_at, ?)', $service);
    }
}
