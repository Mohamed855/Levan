<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use LogicException;
use PHPUnit\Framework\TestCase;
use Tests\Support\AuctionReportDatabaseGuard;

final class AuctionReportDatabaseGuardTest extends TestCase
{
    public function test_it_refuses_shared_databases_before_any_connection_is_opened(): void
    {
        foreach (['levan_test', 'lot_legacy', 'etmam_levan', ''] as $database) {
            try {
                AuctionReportDatabaseGuard::assertSafe([
                    'central' => $database,
                    'tenant' => 'levan_report_ci_tenant',
                    'tenant_id' => 'auction_report_ci',
                    'url' => '',
                ]);
                self::fail('A shared or unspecified database was accepted: '.$database);
            } catch (LogicException $exception) {
                self::assertStringContainsString('isolated', $exception->getMessage());
            }
        }
    }

    public function test_it_requires_explicit_isolated_tenant_identity_and_rejects_dsn_overrides(): void
    {
        foreach ([['tenant_id' => 'test'], ['tenant' => 'levan_test'], ['url' => 'mysql://localhost/levan_test']] as $override) {
            try {
                AuctionReportDatabaseGuard::assertSafe(array_replace($this->safeSettings(), $override));
                self::fail('Unsafe report test configuration was accepted.');
            } catch (LogicException $exception) {
                self::assertStringContainsString('isolated', $exception->getMessage());
            }
        }
    }

    public function test_it_accepts_only_the_dedicated_report_databases(): void
    {
        AuctionReportDatabaseGuard::assertSafe($this->safeSettings());
        $this->addToAssertionCount(1);
    }

    private function safeSettings(): array
    {
        return ['central' => 'levan_report_ci_central', 'tenant' => 'levan_report_ci_tenant', 'tenant_id' => 'auction_report_ci', 'url' => ''];
    }
}
