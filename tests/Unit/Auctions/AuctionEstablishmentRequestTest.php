<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\DataTables\Tenants\AuctionEstablishments\AuctionEstablishmentRequestsDataTable;
use App\Http\Requests\Tenants\AuctionEstablishments\ApproveAuctionEstablishmentRequest;
use App\Http\Requests\Tenants\AuctionEstablishments\SaveAuctionEstablishmentRequest;
use App\Models\Tenants\AuctionEstablishments\AuctionEstablishmentRequest;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\UserMangment\User\User as TenantUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class AuctionEstablishmentRequestTest extends TestCase
{
    public function test_approval_requires_both_timestamp_and_nonblank_number(): void
    {
        self::assertTrue(class_exists(AuctionEstablishmentRequest::class));
        $request = new AuctionEstablishmentRequest;
        self::assertFalse($request->isApproved());
        $request->approved_at = now();
        $request->approval_number = '  ';
        self::assertFalse($request->isApproved());
        $request->approval_number = '000123';
        self::assertTrue($request->isApproved());
        self::assertSame('approved', $request->status);
    }

    public function test_save_cannot_supply_approval_metadata(): void
    {
        self::assertTrue(class_exists(SaveAuctionEstablishmentRequest::class));
        $validator = Validator::make(['approval_number' => '123', 'approved_at' => '2026-11-09', 'approved_by' => 1], (new SaveAuctionEstablishmentRequest)->rules());
        self::assertTrue($validator->fails());
        foreach (['approval_number', 'approved_at', 'approved_by'] as $field) {
            self::assertArrayHasKey($field, $validator->errors()->toArray());
        }
    }

    public function test_save_request_provides_translated_attributes_and_messages(): void
    {
        $request = new SaveAuctionEstablishmentRequest;
        $attributes = $request->attributes();

        self::assertArrayHasKey('data.organization.fal_license', $attributes);
        self::assertSame('رخصة فال للمزادات العقارية', $attributes['data.organization.fal_license']);
        self::assertSame('اسم مدير المزاد', $attributes['data.manager.name']);
        self::assertSame('اسم محرّج المزاد', $attributes['data.auctioneer.name']);
        self::assertSame('شهادة دورة مدير المزاد', $attributes['attachments.manager_certificate']);
        self::assertSame('الإقرار والتعهد', $attributes['declaration_accepted']);

        // Check validator output has Arabic attribute translation
        $validator = Validator::make(
            ['data' => ['organization' => ['fal_license' => '']]],
            ['data.organization.fal_license' => 'required'],
            $request->messages(),
            $request->attributes()
        );
        self::assertTrue($validator->fails());
        self::assertStringContainsString('رخصة فال للمزادات العقارية', $validator->errors()->first('data.organization.fal_license'));
    }

    public function test_approval_number_preserves_leading_zeroes_and_rejects_blank(): void
    {
        self::assertTrue(class_exists(ApproveAuctionEstablishmentRequest::class));
        $rules = (new ApproveAuctionEstablishmentRequest)->rules();
        self::assertTrue(Validator::make(['approval_number' => ' '], $rules)->fails());
        self::assertSame('000123', Validator::make(['approval_number' => '000123'], $rules)->validated()['approval_number']);
    }

    public function test_establishment_requests_datatable_has_checkbox_and_model_columns(): void
    {
        $dataTable = app(AuctionEstablishmentRequestsDataTable::class);
        $columns = $dataTable->html()->getColumns();

        self::assertNotEmpty($columns);
        self::assertSame('checkbox', $columns[0]['data']);
        self::assertStringContainsString('id="select-all"', (string) $columns[0]['title']);

        $approvalColumn = collect($columns)->firstWhere('data', 'approval_number');
        self::assertNotNull($approvalColumn);
        self::assertSame('establishmentRequest.approval_number', $approvalColumn['name']);

        $modelColumn = collect($columns)->firstWhere('data', 'establishment_model');
        self::assertNotNull($modelColumn);
        self::assertSame('نموذج الطلب', $modelColumn['title']);

        $reflector = new \ReflectionClass($dataTable);
        $rawColumnsMethod = $reflector->getMethod('rawColumns');
        $rawColumnsMethod->setAccessible(true);
        $rawColumns = $rawColumnsMethod->invoke($dataTable);
        self::assertContains('approval_number', $rawColumns);
        self::assertContains('establishment_model', $rawColumns);

        $orderMethod = $reflector->getMethod('getCustomOrder');
        $orderMethod->setAccessible(true);
        $order = $orderMethod->invoke($dataTable);
        self::assertSame([10, 'desc'], $order);
    }

    public function test_establishment_requests_datatable_model_callback(): void
    {
        $dataTable = app(AuctionEstablishmentRequestsDataTable::class);
        $fakeDataTable = new class
        {
            public array $addedColumns = [];

            public function addColumn(string $name, $callback): self
            {
                $this->addedColumns[$name] = $callback;

                return $this;
            }

            public function editColumn(string $name, $callback): self
            {
                return $this;
            }

            public function orderColumn(string $name, $callback): self
            {
                return $this;
            }
        };

        $reflector = new \ReflectionClass($dataTable);
        $addCustomColumns = $reflector->getMethod('addCustomColumns');
        $addCustomColumns->setAccessible(true);
        $addCustomColumns->invoke($dataTable, $fakeDataTable);

        self::assertArrayHasKey('establishment_model', $fakeDataTable->addedColumns);

        // When establishment_form_id is missing or user cannot view
        $dummyRow = new \stdClass;
        $dummyRow->id = 1;
        $dummyRow->establishment_form_id = null;
        $html = ($fakeDataTable->addedColumns['establishment_model'])($dummyRow);
        self::assertStringContainsString('text-muted', $html);
        self::assertStringContainsString('—', $html);
    }

    public function test_establishment_request_validates_saudi_mobile_and_identity_and_numbers(): void
    {
        $request = new SaveAuctionEstablishmentRequest;
        $rules = $request->rules();
        $messages = $request->messages();
        $attributes = $request->attributes();

        // 1. Mobile number validation
        $vMobile = Validator::make(['data' => ['manager' => ['mobile' => '0612345678']]], ['data.manager.mobile' => $rules['data.manager.mobile']], $messages, $attributes);
        self::assertTrue($vMobile->fails());
        self::assertStringContainsString('05', $vMobile->errors()->first('data.manager.mobile'));

        $vMobileValid = Validator::make(['data' => ['manager' => ['mobile' => '0512345678']]], ['data.manager.mobile' => $rules['data.manager.mobile']], $messages, $attributes);
        self::assertFalse($vMobileValid->fails());

        // 2. Identity number validation
        $vId = Validator::make(['data' => ['manager' => ['identity_number' => '3000000000']]], ['data.manager.identity_number' => $rules['data.manager.identity_number']], $messages, $attributes);
        self::assertTrue($vId->fails());
        self::assertStringContainsString('1 أو 2', $vId->errors()->first('data.manager.identity_number'));

        $vIdValid = Validator::make(['data' => ['manager' => ['identity_number' => '1000000001']]], ['data.manager.identity_number' => $rules['data.manager.identity_number']], $messages, $attributes);
        self::assertFalse($vIdValid->fails());

        // 3. Unified number validation
        $vUnified = Validator::make(['data' => ['organization' => ['unified_number' => '1234567890']]], ['data.organization.unified_number' => $rules['data.organization.unified_number']], $messages, $attributes);
        self::assertTrue($vUnified->fails());
        self::assertStringContainsString('700', $vUnified->errors()->first('data.organization.unified_number'));

        $vUnifiedValid = Validator::make(['data' => ['organization' => ['unified_number' => '7001234567']]], ['data.organization.unified_number' => $rules['data.organization.unified_number']], $messages, $attributes);
        self::assertFalse($vUnifiedValid->fails());

        // 4. Refund days max 3
        $vRefund = Validator::make(['data' => ['funds' => ['refund_days' => 5]]], ['data.funds.refund_days' => $rules['data.funds.refund_days']], $messages, $attributes);
        self::assertTrue($vRefund->fails());

        $vRefundValid = Validator::make(['data' => ['funds' => ['refund_days' => 3]]], ['data.funds.refund_days' => $rules['data.funds.refund_days']], $messages, $attributes);
        self::assertFalse($vRefundValid->fails());
    }

    public function test_establishment_request_partial_renders_approval_number_as_copyable(): void
    {
        $user = (new TenantUser)->forceFill(['id' => 10]);
        $this->actingAs($user, 'tenant');
        Gate::before(fn () => true);

        $auction = new Auction;
        $auction->id = 1;
        $request = new AuctionEstablishmentRequest;
        $request->approval_number = 'APP-12345';
        $request->approved_at = now();
        $auction->setRelation('establishmentRequest', $request);

        $html = view('tenant.auctions.partials.establishment-request', ['auction' => $auction])->render();

        self::assertStringContainsString('copyable-code-wrapper', $html);
        self::assertStringContainsString('data-code="APP-12345"', $html);
        self::assertStringContainsString('APP-12345', $html);
        self::assertStringContainsString('copyable-code', $html);
        self::assertStringContainsString('ti ti-copy', $html);
    }
}
