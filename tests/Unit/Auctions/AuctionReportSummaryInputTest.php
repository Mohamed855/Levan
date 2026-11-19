<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Http\Requests\Tenants\Auctions\StoreAuctionRequest;
use App\Http\Requests\Tenants\Auctions\UpdateAuctionRequest;
use App\Models\Tenants\Auctions\Auction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

final class AuctionReportSummaryInputTest extends TestCase
{
    public function test_edit_form_has_one_summary_after_introduction_and_restores_saved_text(): void
    {
        view()->share('errors', new ViewErrorBag);
        $auction = new Auction(['marketing_data' => ['introduction' => 'المقدمة المحفوظة', 'report_summary' => 'ملخص مستقل محفوظ']]);
        $auction->exists = true;
        $auction->setRelation('attachments', new Collection);
        $html = view('tenant.auctions.partials.steps.step4-reports-attachments', compact('auction'))->render();
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($document);
        $fields = $xpath->query('//textarea');
        self::assertSame(2, $fields->length);
        self::assertSame('marketing[introduction]', $fields->item(0)->getAttribute('name'));
        self::assertSame('marketing[report_summary]', $fields->item(1)->getAttribute('name'));
        self::assertSame('ملخص مستقل محفوظ', $fields->item(1)->textContent);
    }

    public function test_create_and_update_accept_an_independent_optional_summary_with_a_length_limit(): void
    {
        foreach ([new StoreAuctionRequest, new UpdateAuctionRequest] as $request) {
            $rules = array_intersect_key($request->rules(), array_flip(['marketing', 'marketing.introduction', 'marketing.report_summary']));
            foreach ([null, '', 'ملخص مستقل', str_repeat('م', 1000)] as $summary) {
                $validator = Validator::make(['marketing' => ['introduction' => 'المقدمة', 'report_summary' => $summary]], $rules);
                self::assertTrue($validator->passes(), json_encode($validator->errors()->toArray(), JSON_UNESCAPED_UNICODE));
                self::assertArrayHasKey('report_summary', $validator->validated()['marketing']);
            }
            self::assertTrue(Validator::make(['marketing' => ['report_summary' => str_repeat('م', 1001)]], $rules)->errors()->has('marketing.report_summary'));
        }
    }
}
