<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TenantGoldenPatternInventoryTest extends TestCase
{
    private const REQUIRED_CARD_FIELDS = [
        'Administration:',
        'Boundary: tenant',
        'Scope / aggregate root:',
        'Routes + middleware + guard:',
        'Controller:',
        'FormRequests:',
        'Policy registration + abilities:',
        'Actions / Services / Gateways:',
        'Models / Enums / Events / Jobs:',
        'DataTable / index / filters / buttons:',
        'Create UI + draft resource key + restore/purge evidence:',
        'Edit UI:',
        'Review step:',
        'Show UI + child tables + attachments:',
        'Archive controller / DataTable / views:',
        'Shared components and JavaScript contracts:',
        'Files and storage lifecycle:',
        'Tests:',
        'Verification commands and passing evidence:',
        'Not applicable items:',
        'Remaining gaps:',
        'Status:',
    ];

    public const CLOSED_FAMILIES = [
        'Assignments',
        'Properties',
        'Auctions',
    ];

    public const REMEDIATION_CARD_FILES = [
        'archive.md',
        'authentication-2fa.md',
        'communication-logs.md',
        'companies.md',
        'contracts.md',
        'customer-bulk-ops.md',
        'customer-communications.md',
        'customer-finance.md',
        'customers-core.md',
        'dashboard-charts.md',
        'domains-cpanel.md',
        'general-settings.md',
        'mass-delete.md',
        'notifications-activity.md',
        'portfolios.md',
        'reports.md',
        'settings-api-tokens.md',
        'subscription-billing-license.md',
        'tasks.md',
        'tickets.md',
        'users-teams-roles.md',
    ];

    public const VERIFIED_COMPLIANT_CARD_FILES = [
        'authentication-2fa.md',
        'archive.md',
        'companies.md',
        'contracts.md',
        'customers-core.md',
        'customer-communications.md',
        'customer-bulk-ops.md',
        'customer-finance.md',
        'dashboard-charts.md',
        'domains-cpanel.md',
        'communication-logs.md',
        'reports.md',
        'settings-api-tokens.md',
        'subscription-billing-license.md',
        'tickets.md',
        'users-teams-roles.md',
        'general-settings.md',
        'mass-delete.md',
        'notifications-activity.md',
        'portfolios.md',
        'tasks.md',
    ];

    public function test_tenant_inventory_has_exactly_24_families_with_three_closed(): void
    {
        $manifest = file_get_contents(dirname(__DIR__, 2).'/docs/baselines/golden-pattern-manifest.md');

        $this->assertSame(3, count(self::CLOSED_FAMILIES));
        $this->assertSame(21, count(self::REMEDIATION_CARD_FILES));
        $this->assertSame(24, count(self::CLOSED_FAMILIES) + count(self::REMEDIATION_CARD_FILES));

        $verifiedFiles = self::VERIFIED_COMPLIANT_CARD_FILES;
        $remediationFiles = self::REMEDIATION_CARD_FILES;
        sort($verifiedFiles);
        sort($remediationFiles);
        $this->assertSame($remediationFiles, $verifiedFiles, 'Every remaining Tenant card must be source-verified before the inventory is closed.');

        foreach (self::CLOSED_FAMILIES as $closedFamily) {
            $this->assertStringContainsString($closedFamily, $manifest);
        }
    }

    public function test_tenant_administration_cards_exist_and_follow_the_golden_pattern_schema(): void
    {
        $cardsDir = dirname(__DIR__, 2).'/docs/baselines/tenant-administration-cards';

        $this->assertDirectoryExists($cardsDir, 'Tenant administration cards directory must exist');

        $files = array_values(array_filter(
            scandir($cardsDir) ?: [],
            fn ($f) => str_ends_with($f, '.md')
        ));

        sort($files);
        $expectedFiles = self::REMEDIATION_CARD_FILES;
        sort($expectedFiles);

        $this->assertSame($expectedFiles, $files, 'Must contain exactly the 21 remediation cards');

        foreach ($files as $file) {
            $content = file_get_contents($cardsDir.'/'.$file);
            foreach (self::REQUIRED_CARD_FIELDS as $field) {
                $this->assertStringContainsString(
                    $field,
                    $content,
                    "Card {$file} is missing required field: {$field}"
                );
            }

            preg_match('/Status:\s*(Gap|Partial|Compliant)/', $content, $matches);
            $this->assertNotEmpty($matches, "Card {$file} must have a valid Status");
        }
    }

    public function test_only_source_verified_tenant_administration_cards_are_marked_compliant(): void
    {
        $cardsDir = dirname(__DIR__, 2).'/docs/baselines/tenant-administration-cards';

        foreach (self::REMEDIATION_CARD_FILES as $file) {
            $content = file_get_contents($cardsDir.'/'.$file);

            if (in_array($file, self::VERIFIED_COMPLIANT_CARD_FILES, true)) {
                $this->assertMatchesRegularExpression('/Status:\s*Compliant/', $content);
                $this->assertMatchesRegularExpression('/Remaining gaps:\s*None/', $content);

                continue;
            }

            $this->assertDoesNotMatchRegularExpression('/Status:\s*Compliant/', $content);
        }
    }
}
