<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Http\Requests\Tenants\Settings\MainSettingsRequest;
use App\Services\Tenants\Auctions\Reports\AuctionReportRenderer;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class AsirCoverContentTest extends TestCase
{
    public function test_wide_numeric_and_multiline_contact_cases_are_accepted_by_current_settings_validation(): void
    {
        $fixture = require __DIR__.'/../../Fixtures/AuctionReports/asir-covers.php';
        $rules = (new MainSettingsRequest)->rules();
        foreach (['contacts-unicode', 'social-multiline'] as $scenario) {
            foreach ($fixture($scenario)['marketing']['contacts'] as $role => $value) {
                $field = 'report_'.$role;
                self::assertFalse(Validator::make([$field => $value], [$field => $rules[$field]])->fails(), $field);
            }
        }
    }

    public function test_accepted_long_cover_values_do_not_use_the_rejecting_svg_text_layout(): void
    {
        $fixture = require __DIR__.'/../../Fixtures/AuctionReports/asir-covers.php';
        foreach (['name-99', 'name-255-arabic', 'name-255-latin', 'contacts-long'] as $scenario) {
            $snapshot = $fixture($scenario);
            $html = app(AuctionReportRenderer::class)->html($snapshot, 'report');
            self::assertStringContainsString('data-asir-cover="front"', $html);
            self::assertStringContainsString('data-asir-cover="closing"', $html);
            self::assertStringContainsString(e($snapshot['auction']['name']), $html);
            self::assertSame(2, substr_count($html, 'data-cover-decoration="true"'));
        }
    }

    public function test_contacts_are_conditional_and_escaped_with_existing_safe_links(): void
    {
        $fixture = require __DIR__.'/../../Fixtures/AuctionReports/asir-covers.php';
        $snapshot = $fixture('contacts-empty');
        $html = app(AuctionReportRenderer::class)->html($snapshot, 'report');
        self::assertStringNotContainsString('data-asir-contact=', $html);
        self::assertStringNotContainsString('data-asir-contact-icon', $html);

        $snapshot['marketing']['contacts'] = ['phone' => '+966 55 123 4567', 'social' => '<script>alert(1)</script>'];
        $html = app(AuctionReportRenderer::class)->html($snapshot, 'report');
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new DOMXPath($document);
        self::assertSame(0, $xpath->query('//*[@data-asir-cover="closing"]//script')->length);
        self::assertSame('<script>alert(1)</script>', $xpath->query('//*[@data-report-field="social"]')->item(0)->textContent);
        self::assertSame(0, $xpath->query('//*[@data-link-role="social"]')->length);
        self::assertSame('tel:+966551234567', $xpath->query('//*[@data-link-role="phone"]')->item(0)->getAttribute('href'));
    }

    public function test_missing_current_logos_do_not_restore_legacy_company_branding(): void
    {
        $snapshot = (require __DIR__.'/../../Fixtures/AuctionReports/asir-covers.php')('logos-empty');
        $html = app(AuctionReportRenderer::class)->html($snapshot, 'report');
        self::assertStringNotContainsString('data-asir-logo=', $html);
        self::assertSame(2, substr_count($html, 'data-cover-decoration="true"'));
    }
}
