<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Forms\AuctionRegulatoryForms;
use Tests\TestCase;

final class AuctionRegulatoryFormsTest extends TestCase
{
    private function html(array $data = [], string $type = 'establishment'): string
    {
        self::assertTrue(class_exists(AuctionRegulatoryForms::class), 'Independent regulatory form renderer must exist.');

        return app(AuctionRegulatoryForms::class)->html($data, $type);
    }

    private function xpath(string $html): \DOMXPath
    {
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);

        return new \DOMXPath($document);
    }

    public function test_blank_establishment_contains_six_native_pages_with_landscape_property_statement(): void
    {
        $html = $this->html();
        $xpath = $this->xpath($html);
        self::assertSame(6, $xpath->query('//*[@data-form-page]')->length);
        self::assertSame('landscape', $xpath->query('//*[@data-form-page]')->item(3)->getAttribute('data-orientation'));
        self::assertSame(14, $xpath->query('//table[@data-table="properties"]/tbody/tr')->length);
        self::assertSame(4, $xpath->query('//table[@data-table="agents"]/tbody/tr')->length);
        self::assertStringContainsString('الإقرار والتعهد', $html);
        self::assertSame(0, $xpath->query('//canvas|//object|//embed|//iframe|//svg//text')->length);
        self::assertStringNotContainsString('data:application/pdf', $html);
    }

    public function test_closing_uses_native_cells_and_conditionally_displays_result_fields(): void
    {
        $html = $this->html(['closing' => ['approval_number' => '000187', 'date' => '2026/11/09'], 'properties' => [
            ['deed_number' => '00012345', 'type' => 'أرض', 'usage' => 'سكني', 'result' => 'awarded', 'awardee' => 'شركة الاختبار', 'award_value' => '1500000.25', 'unawarded_reason' => 'يجب ألا يظهر'],
            ['deed_number' => '00054321', 'result' => 'not_awarded', 'unawarded_reason' => 'لم يبلغ الحد الأدنى', 'awardee' => 'اسم قديم', 'award_value' => '999'],
        ]], 'closing');
        $xpath = $this->xpath($html);
        self::assertSame(1, $xpath->query('//*[@data-form-page]')->length);
        self::assertSame(17, $xpath->query('//table[@data-table="closing"]/tbody/tr')->length);
        self::assertStringContainsString('00012345', $html);
        self::assertStringContainsString('1,500,000.25', $html);
        self::assertStringContainsString('لم يبلغ الحد الأدنى', $html);
        self::assertStringNotContainsString('يجب ألا يظهر', $html);
        self::assertStringNotContainsString('اسم قديم', $html);
        self::assertStringContainsString('000187', $html);
    }

    public function test_tables_continue_without_losing_or_repeating_records(): void
    {
        $rows = [];
        for ($i = 1; $i <= 35; $i++) {
            $rows[] = ['deed_number' => 'DOC-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT)];
        }
        $closing = $this->html(['properties' => $rows], 'closing');
        $xpath = $this->xpath($closing);
        self::assertSame(3, $xpath->query('//*[@data-form-page]')->length);
        self::assertSame(3, $xpath->query('//table[@data-table="closing"]/thead')->length);
        foreach ($rows as $row) {
            self::assertSame(1, substr_count($closing, $row['deed_number']));
        }
        $establishment = $this->html(['properties' => array_slice($rows, 0, 15), 'agents' => array_fill(0, 5, ['name' => 'وكيل'])]);
        $xpath = $this->xpath($establishment);
        self::assertSame(8, $xpath->query('//*[@data-form-page]')->length);
        self::assertSame(2, $xpath->query('//table[@data-table="properties"]')->length);
        self::assertSame(2, $xpath->query('//table[@data-table="agents"]')->length);
    }

    public function test_input_remains_text_and_does_not_execute_or_load_external_content(): void
    {
        $html = $this->html(['organization' => ['name' => '<img src="https://invalid.test/x" onerror="alert(1)">'], 'auction' => ['name' => '<script>alert(2)</script>']]);
        $xpath = $this->xpath($html);
        self::assertSame(0, $xpath->query('//script[not(@data-regulatory-layout)]|//img[starts-with(@src,"http")]')->length);
        self::assertStringContainsString('&lt;img', $html);
        self::assertStringContainsString('&lt;script', $html);
    }

    public function test_unknown_document_or_result_is_rejected_instead_of_assumed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->html([], 'unknown');
    }

    public function test_unknown_result_is_not_misrepresented_as_unawarded(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->html(['properties' => [['result' => 'typo']]], 'closing');
    }

    public function test_unknown_checkbox_option_is_rejected_instead_of_printing_an_unanswered_form(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->html(['auction' => ['method' => 'electonic']]);
    }

    public function test_multiple_disposition_rights_can_be_selected_and_retained(): void
    {
        $html = $this->html(['disclosures' => ['disposition_rights' => ['preemption', 'condition_option']]]);
        $xpath = $this->xpath($html);
        self::assertSame(1, $xpath->query('//*[@aria-label="حق الشفعة" and @aria-checked="true"]')->length);
        self::assertSame(1, $xpath->query('//*[@aria-label="خيار الشرط" and @aria-checked="true"]')->length);
        self::assertSame(0, $xpath->query('//*[@aria-label="لا يوجد" and @aria-checked="true"]')->length);
    }

    public function test_monetary_values_keep_large_decimal_precision(): void
    {
        $html = $this->html(['properties' => [['result' => 'awarded', 'award_value' => '9999999999999999.99']]], 'closing');
        self::assertStringContainsString('9,999,999,999,999,999.99', $html);
    }
}
