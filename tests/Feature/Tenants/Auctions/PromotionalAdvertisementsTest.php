<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Actions\Tenants\PromotionalAdvertisements\CreatePromotionalAdvertisementAction;
use App\Http\Requests\Tenants\PromotionalAdvertisements\StorePromotionalAdvertisementRequest;
use App\Models\Admin\AuctionTemplates\AuctionTemplate;
use App\Models\Admin\AuctionTemplates\AuctionTemplateType;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\PromotionalAdvertisements\PromotionalAdvertisement;
use App\Services\Tenants\Shared\CentralAuctionTemplatesGateway;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PromotionalAdvertisementsTest extends TestCase
{
    public function test_promotional_advertisement_model_helpers_and_status_labels(): void
    {
        $ad = new PromotionalAdvertisement([
            'template_type' => 'billboard',
            'status' => 'processing',
        ]);

        $this->assertTrue($ad->isProcessing());
        $this->assertFalse($ad->isReady());
        $this->assertFalse($ad->isFailed());
        $this->assertSame('قالب اللوحة الإعلانية', $ad->type_label);
        $this->assertSame('قيد التجهيز', $ad->status_label);
        $this->assertSame('info', $ad->status_badge);

        $ad->status = 'ready';
        $ad->template_type = 'social';
        $this->assertTrue($ad->isReady());
        $this->assertSame('قالب منصات التواصل الاجتماعي', $ad->type_label);
        $this->assertSame('جاهز للتحميل', $ad->status_label);
        $this->assertSame('success', $ad->status_badge);

        $ad->status = 'failed';
        $this->assertTrue($ad->isFailed());
        $this->assertSame('فشل التجهيز', $ad->status_label);
        $this->assertSame('danger', $ad->status_badge);
    }

    public function test_store_request_rules_validate_fields_and_reject_brochure(): void
    {
        $request = new StorePromotionalAdvertisementRequest();
        $rules = $request->rules();

        // 1. Missing required fields
        $v1 = Validator::make([], $rules);
        $this->assertTrue($v1->fails());
        $this->assertTrue($v1->errors()->has('template_type'));
        $this->assertTrue($v1->errors()->has('auction_template_id'));
        $this->assertTrue($v1->errors()->has('auction_id'));

        // 2. Reject brochure
        $v2 = Validator::make([
            'template_type' => 'brochure',
            'auction_template_id' => 1,
            'auction_id' => 1,
        ], $rules);
        $this->assertTrue($v2->fails());
        $this->assertTrue($v2->errors()->has('template_type'));

        // 3. Accept billboard and social
        $v3 = Validator::make([
            'template_type' => 'billboard',
            'auction_template_id' => 10,
            'auction_id' => 99999,
        ], $rules);
        $this->assertFalse($v3->errors()->has('template_type'));

        $v4 = Validator::make([
            'template_type' => 'social',
            'auction_template_id' => 10,
            'auction_id' => 99999,
        ], $rules);
        $this->assertFalse($v4->errors()->has('template_type'));
    }

    public function test_action_rejects_invalid_type_or_missing_template(): void
    {
        $gateway = app(CentralAuctionTemplatesGateway::class);
        $action = new CreatePromotionalAdvertisementAction($gateway);

        $this->expectException(ValidationException::class);
        $action->execute([
            'template_type' => 'brochure',
            'auction_template_id' => 1,
            'auction_id' => 1,
        ]);
    }

    public function test_action_rejects_non_existent_central_template(): void
    {
        $mockGateway = $this->createMock(CentralAuctionTemplatesGateway::class);
        $mockGateway->method('findActive')->willReturn(null);

        $action = new CreatePromotionalAdvertisementAction($mockGateway);

        $this->expectException(ValidationException::class);
        $action->execute([
            'template_type' => 'billboard',
            'auction_template_id' => 99999999,
            'auction_id' => 1,
        ]);
    }

    public function test_create_and_form_views_compile_cleanly(): void
    {
        View::share('errors', new ViewErrorBag());

        $renderedForm = view('tenant.promotional-advertisements.partials._form', [
            'auctionOptions' => [
                ['value' => '1', 'label' => 'مزاد النرجس'],
            ],
            'selectedAuctionId' => '1',
            'templatesGrouped' => [
                'billboard' => [['value' => 10, 'label' => 'قالب عريض']],
                'social' => [['value' => 11, 'label' => 'قالب مربع']],
            ],
        ])->render();

        $this->assertNotEmpty($renderedForm);
        $this->assertStringContainsString('template_type', $renderedForm);
        $this->assertStringContainsString('auction_template_id', $renderedForm);
        $this->assertStringContainsString('auction_id', $renderedForm);
        $this->assertStringContainsString('قالب اللوحة الإعلانية', $renderedForm);
        $this->assertStringContainsString('قالب منصات التواصل الاجتماعي', $renderedForm);
    }

    public function test_sidebar_includes_promotional_advertisements_link(): void
    {
        $menuContent = file_get_contents(resource_path('views/tenant/layouts/sections/menu/verticalMenu.blade.php'));

        $this->assertStringContainsString('promotional-advertisements.create', $menuContent);
        $this->assertStringContainsString('الإعلانات الترويجية', $menuContent);
        $this->assertStringContainsString('ti-speakerphone', $menuContent);
    }

    public function test_auction_show_view_contains_promotional_advertisements_table_card(): void
    {
        $showContent = file_get_contents(resource_path('views/tenant/auctions/show.blade.php'));

        $this->assertStringContainsString('promotional-advertisements-card', $showContent);
        $this->assertStringContainsString('promotional-advertisements-table', $showContent);
        $this->assertStringContainsString('الإعلانات الترويجية للمزاد', $showContent);
        $this->assertStringContainsString('لا توجد إعلانات ترويجية لهذا المزاد بعد.', $showContent);
        $this->assertStringContainsString('جارٍ التجهيز...', $showContent);
        $this->assertStringContainsString('تحميل الصورة', $showContent);
        $this->assertStringContainsString('auctions.promotional-advertisements.status', $showContent);
    }
}
