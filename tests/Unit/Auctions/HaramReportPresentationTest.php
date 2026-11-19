<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AuctionReportRenderer;
use DOMDocument;
use DOMXPath;
use Tests\TestCase;

final class HaramReportPresentationTest extends TestCase
{
    private function snapshot(): array
    {
        $snapshot = require __DIR__.'/../../Fixtures/AuctionReports/haram.php';
        $snapshot['cycle_version'] = 1;
        $snapshot['closing_saved'] = true;
        $snapshot['properties'] = array_slice($snapshot['properties'], 0, 2);
        $snapshot['marketing']['contacts'] = ['phone' => '+966112345678', 'unified_phone' => '920012345', 'social' => '@company'];

        return $snapshot;
    }

    private function document(array $snapshot): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.app(AuctionReportRenderer::class)->html($snapshot, 'outputs'));

        return new DOMXPath($document);
    }

    public function test_null_company_logo_uses_platform_icon_in_outputs(): void
    {
        $snapshot = $this->snapshot();
        $snapshot['branding'] = ['company_logo' => null, 'issuing_entity_logo' => null];
        $html = app(AuctionReportRenderer::class)->html($snapshot, 'outputs');
        self::assertStringContainsString('data:image/png;base64,'.base64_encode(file_get_contents(public_path('static/brand/logos/lot/trans-icon.png'))), $html);
    }

    public function test_cover_uses_native_rtl_cards_and_separate_contact_icons(): void
    {
        $snapshot = $this->snapshot();
        $snapshot['assignment']['name'] = str_repeat('مشروع عقارات الرياض ', 8);
        $xpath = $this->document($snapshot);
        self::assertSame(4, $xpath->query('//*[@data-haram-card]')->length);
        self::assertSame('اسم المشروع', $xpath->query('//*[@data-haram-card="project"]/*[1]')->item(0)?->textContent);
        self::assertSame(2, $xpath->query('//*[@data-haram-contact]/*[local-name()="svg"]')->length);
        self::assertSame(0, $xpath->query('//*[@data-page-type="haram-cover"]//*[@data-field="f12"]')->length);
    }

    public function test_haram_tables_preserve_ltr_values_and_draw_only_existing_rows(): void
    {
        $snapshot = $this->snapshot();
        $snapshot['properties'][0]['number'] = 'PROP-01234';
        $xpath = $this->document($snapshot);
        $cell = $xpath->query('//*[@data-report-cell="properties:0:1"]')->item(0);
        self::assertSame('td', $cell?->tagName);
        self::assertSame('ltr', $cell?->firstElementChild?->getAttribute('dir'));
        self::assertSame(2, $xpath->query('//*[@data-haram-table-row="properties"]')->length);
        self::assertSame(2, $xpath->query('//*[@data-haram-table-row="sold"]')->length);
        self::assertSame(0, $xpath->query('//*[@data-result-section="unsold"]')->length);
        self::assertSame(0, $xpath->query('//*[@data-field="f48" or @data-field="f49"]')->length);
    }

    public function test_index_includes_the_system_time_with_arabic_meridiem(): void
    {
        $snapshot = $this->snapshot();
        $snapshot['auction']['start_time'] = '00:15';
        $snapshot['auction']['end_time'] = '12:30';
        $xpath = $this->document($snapshot);
        self::assertStringContainsString(' - 12:15 ص', $xpath->query('//*[@data-report-field="start_date"]')->item(0)?->textContent ?? '');
        self::assertStringContainsString(' - 12:30 م', $xpath->query('//*[@data-report-field="end_date"]')->item(0)?->textContent ?? '');
    }

    public function test_partial_marketing_keeps_zero_and_omits_missing_channels_and_board_sections(): void
    {
        $snapshot = $this->snapshot();
        $snapshot['marketing']['campaigns'] = [['id' => 1, 'platform' => 'x', 'impressions' => 0]];
        $snapshot['evidence']['board_before'] = [['id' => 1, 'uri' => 'data:image/png;base64,'.base64_encode(file_get_contents(resource_path('auction-reports/haram/background-2.png')))]];
        $xpath = $this->document($snapshot);
        self::assertSame(1, $xpath->query('//*[@data-haram-channel="x"]')->length);
        self::assertStringContainsString('0', $xpath->query('//*[@data-haram-channel="x"]')->item(0)?->textContent ?? '');
        self::assertSame(0, $xpath->query('//*[@data-haram-channel="snapchat" or @data-haram-channel="tiktok"]')->length);
        self::assertSame(0, $xpath->query('//*[@data-haram-board-section="board_after"]')->length);
    }

    public function test_results_require_a_saved_closing_and_at_least_one_known_result(): void
    {
        $snapshot = $this->snapshot();
        $snapshot['closing_saved'] = false;
        self::assertSame(0, $this->document($snapshot)->query('//*[starts-with(@data-page-type,"haram-results")]')->length);
        $snapshot['closing_saved'] = true;
        foreach ($snapshot['properties'] as &$property) {
            $property['result_status'] = 'pending';
        }
        unset($property);
        self::assertSame(0, $this->document($snapshot)->query('//*[starts-with(@data-page-type,"haram-results")]')->length);
        $snapshot['properties'][0]['result_status'] = 'sold';
        self::assertSame(1, $this->document($snapshot)->query('//*[starts-with(@data-page-type,"haram-results")]')->length);
    }

    public function test_logo_panel_opacity_does_not_reduce_the_logo_opacity(): void
    {
        $snapshot = $this->snapshot();
        $uri = 'data:image/svg+xml;base64,'.base64_encode(file_get_contents(resource_path('auction-reports/fallback.svg')));
        $snapshot['branding'] = ['company_logo' => $uri, 'issuing_entity_logo' => $uri];
        $xpath = $this->document($snapshot);
        self::assertSame(4, $xpath->query('//*[@data-page-type="haram-cover" or @data-page-type="haram-closing"]//*[@data-report-branding-mask and @fill-opacity="0.4"]')->length);
        self::assertSame(0, $xpath->query('//*[@data-report-branding and @opacity]')->length);
    }

    public function test_unsafe_marketing_links_remain_plain_data_and_are_not_loaded(): void
    {
        $snapshot = $this->snapshot();
        $snapshot['marketing']['campaigns'] = [['platform' => 'x', 'views' => 0, 'url' => 'javascript:alert(1)']];
        $xpath = $this->document($snapshot);
        self::assertSame(0, $xpath->query('//a[starts-with(@href,"javascript:")]')->length);
        self::assertSame(1, $xpath->query('//*[@data-report-field="x_views" and text()="0"]')->length);
    }

    public function test_native_index_preserves_property_badges_without_drawn_table_grids(): void
    {
        $snapshot = $this->snapshot();
        $snapshot['properties'][1]['result_status'] = 'unsold';
        $snapshot['properties'][1]['unsold_reason'] = 'لم يصل للسعر المطلوب';
        $snapshot['marketing']['campaigns'] = [['platform' => 'tiktok', 'views' => 1, 'reach' => 2]];
        $xpath = $this->document($snapshot);
        $page = $xpath->query('//*[@data-page-type="haram-index"]')->item(0);
        self::assertSame(0, $xpath->query('.//*[local-name()="svg"]', $page)->length);
        self::assertSame(2, $xpath->query('.//*[@data-haram-property-badge]', $page)->length);
    }

    public function test_an_empty_marketing_record_does_not_create_an_empty_page(): void
    {
        $snapshot = $this->snapshot();
        $snapshot['marketing']['campaigns'] = [['id' => 1, 'platform' => 'x']];
        self::assertSame(0, $this->document($snapshot)->query('//*[@data-page-type="haram-marketing"]')->length);
        $snapshot['marketing']['evidence_url'] = 'https://example.com/evidence';
        self::assertSame(1, $this->document($snapshot)->query('//*[@data-page-type="haram-marketing"]')->length);
    }

    public function test_uploaded_cover_shows_through_clean_logo_and_metadata_regions(): void
    {
        $snapshot = $this->snapshot();
        $snapshot['auction']['cover_image'] = 'data:image/png;base64,'.base64_encode(file_get_contents(resource_path('auction-regulatory-forms/logo.png')));
        $xpath = $this->document($snapshot);
        self::assertSame(1, $xpath->query('//*[@data-cover-foreground="true" and @clip-path]')->length);
    }

    public function test_narrative_continuations_do_not_repeat_empty_result_headings(): void
    {
        $snapshot = $this->snapshot();
        $snapshot['marketing']['results_narrative'] = str_repeat('نتائج المزاد والبيانات المسجلة عن عملية البيع. ', 50);
        $snapshot['properties'][1]['result_status'] = 'unsold';
        $snapshot['properties'][1]['unsold_reason'] = 'لم يبلغ السعر المطلوب';
        $xpath = $this->document($snapshot);
        self::assertSame(1, $xpath->query('//*[@data-report-field="narrative"]')->length);
        foreach ($xpath->query('//*[starts-with(@data-page-type,"haram-results")]') as $page) {
            foreach (['sold', 'unsold'] as $table) {
                if ($xpath->query('.//*[starts-with(@data-report-cell,"'.$table.':")]', $page)->length === 0) {
                    self::assertSame(0, $xpath->query('.//*[@data-report-field="'.$table.'_heading"]', $page)->length);
                }
            }
        }
    }
}
