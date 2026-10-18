<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SuperAdminGoldenPatternInventoryTest extends TestCase
{
    private const REQUIRED_CARD_FIELDS = [
        'Administration:',
        'Boundary: central',
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

    public const SUPERADMIN_CARD_FILES = [
        'dashboard.md',
        'authentication-2fa.md',
        'admin-users-roles.md',
        'plans.md',
        'companies-tenants.md',
        'subscriptions-approvals.md',
        'billing.md',
        'coupons.md',
        'support-tickets.md',
        'content-clients.md',
        'content-portfolios.md',
        'platform-settings.md',
        'bank-settings.md',
        'domain-settings.md',
        'versions-testers.md',
        'activity-log.md',
        'central-archive.md',
        'central-mass-delete.md',
        'api-integrations.md',
        'public-contact-requests.md',
        'public-trial-requests.md',
    ];

    public function test_superadmin_inventory_has_exactly_21_families(): void
    {
        $manifest = file_get_contents(dirname(__DIR__, 2).'/docs/baselines/golden-pattern-manifest.md');

        $this->assertCount(21, self::SUPERADMIN_CARD_FILES);
        $this->assertStringContainsString('Central / Super Admin', $manifest);
    }

    public function test_superadmin_administration_cards_exist_and_follow_the_golden_pattern_schema(): void
    {
        $cardsDir = dirname(__DIR__, 2).'/docs/baselines/superadmin-administration-cards';

        $this->assertDirectoryExists($cardsDir, 'SuperAdmin administration cards directory must exist');

        $files = array_values(array_filter(
            scandir($cardsDir) ?: [],
            fn ($f) => str_ends_with($f, '.md')
        ));

        sort($files);
        $expectedFiles = self::SUPERADMIN_CARD_FILES;
        sort($expectedFiles);

        $this->assertSame($expectedFiles, $files, 'Must contain exactly the 21 superadmin cards');

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

    public function test_all_superadmin_administration_cards_are_certified_compliant_with_complete_evidence(): void
    {
        $cardsDir = dirname(__DIR__, 2).'/docs/baselines/superadmin-administration-cards';

        foreach (self::SUPERADMIN_CARD_FILES as $file) {
            $content = file_get_contents($cardsDir.'/'.$file);

            $this->assertMatchesRegularExpression(
                '/Status:\s*Compliant/',
                $content,
                "Card {$file} must be certified Compliant"
            );

            $this->assertMatchesRegularExpression(
                '/Remaining gaps:\s*None/',
                $content,
                "Card {$file} must have Remaining gaps: None"
            );

            $this->assertMatchesRegularExpression(
                '/Verification commands and passing evidence:\s*php artisan test/',
                $content,
                "Card {$file} must have passing verification commands and evidence"
            );
        }
    }

    public function test_all_classes_and_views_referenced_in_superadmin_cards_exist_on_disk(): void
    {
        $cardsDir = dirname(__DIR__, 2).'/docs/baselines/superadmin-administration-cards';
        $root = dirname(__DIR__, 2);

        foreach (self::SUPERADMIN_CARD_FILES as $file) {
            $content = file_get_contents($cardsDir.'/'.$file);

            // Extract all fully-qualified class names starting with App\
            preg_match_all('/App\\\\[A-Za-z0-9_\\\\]+/', $content, $classMatches);
            foreach ($classMatches[0] as $class) {
                $this->assertTrue(
                    class_exists($class) || interface_exists($class) || trait_exists($class),
                    "Card {$file} references non-existent class: {$class}"
                );
            }

            // Extract all explicit blade view paths
            preg_match_all('/resources\/views\/[A-Za-z0-9_\-\/\.]+\.blade\.php/', $content, $viewMatches);
            foreach ($viewMatches[0] as $view) {
                $this->assertFileExists(
                    $root.'/'.$view,
                    "Card {$file} references non-existent view file: {$view}"
                );
            }
        }
    }
}

