<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use App\Actions\Admin\Settings\UpdatePlatformSettingsAction;
use App\Actions\Tenants\Assignments\UpdateAssignmentAction;
use App\Actions\Tenants\Auctions\UpdateAuctionAction;
use App\Actions\Tenants\Properties\UpdatePropertyAction;
use App\Actions\Tenants\Settings\UpdateTenantSettingsAction;
use App\Enums\Tenants\Assignments\AssignmentAttachmentCategory;
use App\Enums\Tenants\Properties\PropertyAttachmentCategory;
use App\Http\Requests\Admin\AdminMangment\Users\AdminUserUpdateRequest;
use App\Http\Requests\Admin\AdminMangment\Users\UpdateAdminUserProfileImageRequest;
use App\Http\Requests\Admin\Settings\AdminMainSettingsRequest;
use App\Http\Requests\Admin\Settings\Bank\UpdateBankRequest;
use App\Http\Requests\Admin\Tickets\ReplyTicketRequest;
use App\Http\Requests\Concerns\WizardFormRequest;
use App\Http\Requests\Site\CompleteCheckoutPaymentRequest;
use App\Http\Requests\Tenants\Assignments\UpdateAssignmentRequest;
use App\Http\Requests\Tenants\AuctionClosings\SaveAuctionClosingRequest;
use App\Http\Requests\Tenants\IssuingEntities\StoreIssuingEntityRequest;
use App\Http\Requests\Tenants\MarketingCampaigns\SaveMarketingCampaignRequest;
use App\Http\Requests\Tenants\Payment\BankTransferReceiptRequest;
use App\Http\Requests\Tenants\Properties\UpdatePropertyRequest;
use App\Http\Requests\Tenants\Settings\MainSettingsRequest;
use App\Http\Requests\Tenants\Tickets\TicketReplyRequest;
use App\Http\Requests\Tenants\UserManagement\User\UpdateUserRequest;
use App\Http\Requests\Tenants\Users\UpdateUserProfileImageRequest;
use App\Models\Admin\Settings\Settings as AdminSettings;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionAttachment;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Shared\Wizard\WizardDraftService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Facades\Activity;
use Tests\TestCase;

final class UnifiedFileUploadLifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.central.driver' => 'sqlite',
            'database.connections.central.database' => ':memory:',
            'tenancy.database.connection' => 'sqlite',
        ]);
        DB::purge('sqlite');
        DB::purge('central');

        Storage::fake('local');
        Storage::fake('public');
        Storage::fake('tenant_public');
        Storage::fake('tenant_private');
        Activity::disableLogging();
    }

    protected function tearDown(): void
    {
        Activity::enableLogging();
        tenancy()->initialized = false;
        parent::tearDown();
    }

    public function test_file_component_renders_download_button_and_per_file_multiple_remove_metadata(): void
    {
        $singleHtml = Blade::render(
            '<x-form.controls.file name="establishment_signature" label="توقيع المنشأة" :value="$value" accept="image/png,.png" />',
            [
                'value' => [
                    'url' => 'https://example.test/settings/signing-assets/signature',
                    'name' => 'signature.png',
                    'mime' => 'image/png',
                ],
            ]
        );

        self::assertStringContainsString('class="btn-view-file', $singleHtml);
        self::assertStringContainsString('class="btn-download-file', $singleHtml);
        self::assertStringContainsString('download="signature.png"', $singleHtml);
        self::assertStringContainsString('data-remove-existing-file="remove_establishment_signature"', $singleHtml);
        self::assertStringContainsString('data-remove-mode="single"', $singleHtml);
        self::assertStringContainsString('onerror=', $singleHtml);

        $multiHtml = Blade::render(
            '<x-form.controls.file name="campaigns[x][evidence][]" label="أدلة الحملة" multiple :value="$files" />',
            [
                'files' => [
                    [
                        'id' => 41,
                        'url' => 'https://example.test/storage/a.pdf',
                        'name' => 'proof-1.pdf',
                        'mime' => 'application/pdf',
                        'remove_name' => 'campaigns[x][remove_evidence][]',
                        'remove_value' => 41,
                    ],
                    [
                        'id' => 42,
                        'url' => 'https://example.test/storage/b.png',
                        'name' => 'proof-2.png',
                        'mime' => 'image/png',
                        'remove_name' => 'campaigns[x][remove_evidence][]',
                        'remove_value' => 42,
                    ],
                ],
            ]
        );

        self::assertStringContainsString('data-remove-existing-file="campaigns[x][remove_evidence][]"', $multiHtml);
        self::assertStringContainsString('data-remove-mode="multiple"', $multiHtml);
        self::assertStringContainsString('data-remove-value="41"', $multiHtml);
        self::assertStringContainsString('data-remove-value="42"', $multiHtml);
        self::assertSame(2, substr_count($multiHtml, 'class="btn-download-file'));
    }

    public function test_all_single_step_and_edit_file_requests_extend_wizard_form_request(): void
    {
        $requests = [
            MainSettingsRequest::class,
            AdminMainSettingsRequest::class,
            AdminUserUpdateRequest::class,
            UpdateUserRequest::class,
            UpdateAdminUserProfileImageRequest::class,
            UpdateUserProfileImageRequest::class,
            SaveMarketingCampaignRequest::class,
            SaveAuctionClosingRequest::class,
            TicketReplyRequest::class,
            ReplyTicketRequest::class,
            BankTransferReceiptRequest::class,
        ];

        foreach ($requests as $requestClass) {
            self::assertTrue(
                is_subclass_of($requestClass, WizardFormRequest::class),
                "{$requestClass} must extend WizardFormRequest for validation-error file recovery.",
            );
        }
    }

    public function test_wizard_form_request_merges_restored_draft_files_before_rules_and_has_file_checks(): void
    {
        Schema::create('wizard_drafts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('guard')->default('tenant');
            $table->unsignedBigInteger('user_id');
            $table->string('tenant_id')->default('');
            $table->string('resource_key');
            $table->unsignedTinyInteger('last_step')->default(1);
            $table->json('draft_data')->nullable();
            $table->json('draft_files')->nullable();
            $table->timestamps();
        });
        Schema::create('issuing_entities', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('image')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        $user = new User;
        $user->setRawAttributes(['id' => 99]);

        $tenantRoute = (new Route('POST', '/tenant/issuing-entities', fn () => null))->middleware(['web', 'auth:tenant']);
        $tenantClass = config('tenancy.tenant_model');
        $tenantModel = new $tenantClass;
        $tenantModel->setRawAttributes(['id' => 'tenant-test-1']);
        tenancy()->tenant = $tenantModel;
        tenancy()->initialized = true;

        $saveReq = Request::create('/tenant/wizard-drafts', 'POST', ['name' => 'إنفاذ'], [], [
            'image' => UploadedFile::fake()->image('entity.png', 200, 200),
        ]);
        $saveReq->setUserResolver(fn (): User => $user);
        $saveReq->setRouteResolver(fn (): Route => $tenantRoute);
        $this->actingAs($user, 'tenant');

        $draft = app(WizardDraftService::class)->save($saveReq, 'tenant.issuing-entity-create', 1);

        $submitReq = StoreIssuingEntityRequest::create('/tenant/issuing-entities', 'POST', [
            'name' => 'مركز الإسناد',
            '_wizard_draft_id' => $draft->getKey(),
            '_wizard_resource_key' => 'tenant.issuing-entity-create',
        ]);
        $submitReq->setUserResolver(fn (): User => $user);
        $submitReq->setRouteResolver(fn (): Route => $tenantRoute);
        $submitReq->setContainer(app());
        $submitReq->setRedirector(app('redirect'));

        // Must merge draft files BEFORE rules() runs so $this->hasFile('image') is true
        $submitReq->mergeWizardDraftIfPresent();
        self::assertTrue($submitReq->hasFile('image'));
        self::assertInstanceOf(UploadedFile::class, $submitReq->file('image'));

        $validator = Validator::make($submitReq->validationData(), $submitReq->rules());
        self::assertTrue($validator->passes(), json_encode($validator->errors()->all()));
    }

    public function test_tenant_and_admin_settings_support_deferred_image_removal_on_submit(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('office_name')->nullable();
            $table->string('logo_text')->nullable();
            $table->string('image')->nullable();
            $table->string('establishment_signature')->nullable();
            $table->string('establishment_stamp')->nullable();
            $table->string('office_address')->nullable();
            $table->string('commercial_register')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('x_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->text('google_maps_embed_url')->nullable();
            $table->boolean('maintenance_mode')->default(false);
            $table->boolean('trial_days_mode')->default(false);
            $table->timestamps();
        });

        $tenantAction = app(UpdateTenantSettingsAction::class);
        $tenantSettings = $tenantAction->execute(
            ['office_name' => 'شركة اختبار'],
            UploadedFile::fake()->image('logo.png'),
            UploadedFile::fake()->image('sig.png'),
            UploadedFile::fake()->image('stamp.png'),
        );
        $logoPath = $tenantSettings->image;
        $sigPath = $tenantSettings->establishment_signature;
        $stampPath = $tenantSettings->establishment_stamp;

        $updated = $tenantAction->execute([
            'office_name' => 'شركة اختبار',
            'remove_image' => true,
            'remove_establishment_signature' => true,
        ]);

        self::assertNull($updated->image);
        self::assertNull($updated->establishment_signature);
        self::assertSame($stampPath, $updated->establishment_stamp);
        Storage::disk('tenant_public')->assertMissing($logoPath);
        Storage::disk('tenant_private')->assertMissing($sigPath);
        Storage::disk('tenant_private')->assertExists($stampPath);

        // Admin settings removal
        $adminAction = app(UpdatePlatformSettingsAction::class);
        AdminSettings::query()->delete();
        $adminSettings = $adminAction->execute(
            ['office_name' => 'منصة ليفان'],
            ['maintenance_mode' => false, 'trial_days_mode' => false],
            UploadedFile::fake()->image('platform.png'),
        );
        $adminLogo = $adminSettings->image;
        Storage::disk('public')->assertExists($adminLogo);

        $adminAfterRemove = $adminAction->execute(
            ['office_name' => 'منصة ليفان', 'remove_image' => true],
            ['maintenance_mode' => false, 'trial_days_mode' => false],
        );
        self::assertNull($adminAfterRemove->image);
        Storage::disk('public')->assertMissing($adminLogo);
    }

    public function test_update_requests_require_replacement_when_removing_mandatory_files(): void
    {
        $bankReq = UpdateBankRequest::create('/admin/banks/1', 'PUT', ['remove_icon' => '1']);
        $bankValidator = Validator::make($bankReq->all(), ['icon' => $bankReq->rules()['icon']]);
        self::assertTrue($bankValidator->fails());
        self::assertTrue($bankValidator->errors()->has('icon'));

        $assignmentReq = UpdateAssignmentRequest::create('/tenant/assignments/1', 'PUT', [
            'remove_assignment_image' => '1',
            'remove_assignment_letter' => '1',
        ]);
        $assignmentRules = $assignmentReq->rules();
        $assignmentValidator = Validator::make($assignmentReq->all(), [
            'assignment_image' => $assignmentRules['assignment_image'],
            'assignment_letter' => $assignmentRules['assignment_letter'],
        ]);
        self::assertTrue($assignmentValidator->errors()->has('assignment_image'));
        self::assertTrue($assignmentValidator->errors()->has('assignment_letter'));

        $propertyReq = UpdatePropertyRequest::create('/tenant/properties/1', 'PUT', [
            'remove_main_image' => '1',
            'remove_deed_document' => '1',
        ]);
        $propertyRules = $propertyReq->rules();
        $propertyValidator = Validator::make($propertyReq->all(), [
            'main_image' => $propertyRules['main_image'],
            'deed_document' => $propertyRules['deed_document'],
        ]);
        self::assertTrue($propertyValidator->errors()->has('main_image'));
        self::assertTrue($propertyValidator->errors()->has('deed_document'));
    }

    public function test_assignment_property_and_auction_individual_removal_works_and_blocks_idor(): void
    {
        Schema::create('assignments', function (Blueprint $table): void {
            $table->id();
            $table->string('assignment_number')->nullable();
            $table->string('name')->nullable();
            $table->string('issuing_entity')->nullable();
            $table->unsignedBigInteger('issuing_entity_id')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->json('category')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('attachments', function (Blueprint $table): void {
            $table->id();
            $table->morphs('attachable');
            $table->string('disk')->default('tenant_public');
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('extension')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('category');
            $table->timestamps();
        });
        Schema::create('auctions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('assignment_id')->nullable();
            $table->string('name')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('report_image')->nullable();
            $table->json('marketing_data')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('auction_attachments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('auction_id');
            $table->unsignedBigInteger('property_id')->nullable();
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->string('file_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('category')->default('auction_doc');
            $table->json('channels')->nullable();
            $table->string('report_section')->nullable();
            $table->timestamps();
        });
        Schema::create('properties', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('assignment_id')->nullable();
            $table->string('name')->nullable();
            $table->string('main_image')->nullable();
            $table->string('aerial_image')->nullable();
            $table->string('status')->default('available');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('property_attachments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->string('disk')->default('tenant_public');
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('extension')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('category');
            $table->timestamps();
        });
        Schema::create('property_boundaries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('property_owners', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('property_units', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('auction_establishment_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('auction_id');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
        Schema::create('auction_closings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('auction_id');
            $table->timestamp('saved_at')->nullable();
            $table->timestamps();
        });
        Schema::create('auction_properties', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('auction_id');
            $table->unsignedBigInteger('property_id');
            $table->integer('sort_order')->default(0);
            $table->string('result_status')->default('pending');
            $table->decimal('opening_price', 15, 2)->nullable();
            $table->decimal('sale_price', 15, 2)->nullable();
            $table->integer('participants_count')->nullable();
            $table->integer('bids_count')->nullable();
            $table->text('unsold_reason')->nullable();
            $table->json('board_data')->nullable();
            $table->string('buyer_name')->nullable();
            $table->text('result_notes')->nullable();
            $table->timestamps();
        });

        // 1. Assignment individual removal + IDOR check
        $asgA = Assignment::query()->create(['name' => 'A']);
        $asgB = Assignment::query()->create(['name' => 'B']);
        Storage::disk('tenant_public')->put('assignments/a1.pdf', 'pdf1');
        Storage::disk('tenant_public')->put('assignments/a2.pdf', 'pdf2');
        Storage::disk('tenant_public')->put('assignments/b1.pdf', 'pdf3');

        $docA1 = $asgA->attachments()->create(['disk' => 'tenant_public', 'file_path' => 'assignments/a1.pdf', 'category' => AssignmentAttachmentCategory::ADDITIONAL_ATTACHMENT->value]);
        $docA2 = $asgA->attachments()->create(['disk' => 'tenant_public', 'file_path' => 'assignments/a2.pdf', 'category' => AssignmentAttachmentCategory::ADDITIONAL_ATTACHMENT->value]);
        $docB1 = $asgB->attachments()->create(['disk' => 'tenant_public', 'file_path' => 'assignments/b1.pdf', 'category' => AssignmentAttachmentCategory::ADDITIONAL_ATTACHMENT->value]);

        app(UpdateAssignmentAction::class)->execute($asgA, ['remove_additional_attachments' => [$docA1->id]]);
        self::assertDatabaseMissing('attachments', ['id' => $docA1->id]);
        self::assertDatabaseHas('attachments', ['id' => $docA2->id]);
        Storage::disk('tenant_public')->assertMissing('assignments/a1.pdf');
        Storage::disk('tenant_public')->assertExists('assignments/a2.pdf');

        try {
            app(UpdateAssignmentAction::class)->execute($asgA, ['remove_additional_attachments' => [$docB1->id]]);
            self::fail('Cross-assignment attachment removal must fail with ValidationException.');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('remove_additional_attachments', $e->errors());
        }

        // 2. Property individual removal + IDOR check
        $propA = Property::query()->create(['name' => 'Prop A']);
        $propB = Property::query()->create(['name' => 'Prop B']);
        Storage::disk('tenant_public')->put('properties/permit.pdf', 'permit');
        Storage::disk('tenant_public')->put('properties/gal1.jpg', 'img1');
        Storage::disk('tenant_public')->put('properties/gal2.jpg', 'img2');
        Storage::disk('tenant_public')->put('properties/other.jpg', 'other');

        $permit = $propA->attachments()->create(['disk' => 'tenant_public', 'file_path' => 'properties/permit.pdf', 'category' => PropertyAttachmentCategory::BUILDING_PERMIT->value]);
        $gal1 = $propA->attachments()->create(['disk' => 'tenant_public', 'file_path' => 'properties/gal1.jpg', 'category' => PropertyAttachmentCategory::GALLERY_IMAGE->value]);
        $gal2 = $propA->attachments()->create(['disk' => 'tenant_public', 'file_path' => 'properties/gal2.jpg', 'category' => PropertyAttachmentCategory::GALLERY_IMAGE->value]);
        $otherGal = $propB->attachments()->create(['disk' => 'tenant_public', 'file_path' => 'properties/other.jpg', 'category' => PropertyAttachmentCategory::GALLERY_IMAGE->value]);

        app(UpdatePropertyAction::class)->execute($propA, [
            'name' => 'Prop A',
            'remove_building_permit' => true,
            'remove_property_images' => [$gal1->id],
        ]);
        self::assertDatabaseMissing('attachments', ['id' => $permit->id]);
        self::assertDatabaseMissing('attachments', ['id' => $gal1->id]);
        self::assertDatabaseHas('attachments', ['id' => $gal2->id]);
        Storage::disk('tenant_public')->assertMissing('properties/permit.pdf');
        Storage::disk('tenant_public')->assertMissing('properties/gal1.jpg');
        Storage::disk('tenant_public')->assertExists('properties/gal2.jpg');

        try {
            app(UpdatePropertyAction::class)->execute($propA, ['remove_property_images' => [$otherGal->id]]);
            self::fail('Cross-property attachment removal must fail with ValidationException.');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('remove_property_images', $e->errors());
        }

        // 3. Auction image & individual attachment removal + IDOR check
        $auctionA = Auction::query()->create(['assignment_id' => $asgA->id, 'name' => 'Auc A', 'cover_image' => 'auctions/c.jpg', 'report_image' => 'auctions/r.jpg']);
        $auctionB = Auction::query()->create(['assignment_id' => $asgB->id, 'name' => 'Auc B']);
        Storage::disk('tenant_public')->put('auctions/c.jpg', 'cover');
        Storage::disk('tenant_public')->put('auctions/r.jpg', 'report');
        Storage::disk('tenant_public')->put('auctions/att1.pdf', 'att1');
        Storage::disk('tenant_public')->put('auctions/attB.pdf', 'attB');

        $attA = AuctionAttachment::query()->create(['auction_id' => $auctionA->id, 'file_path' => 'auctions/att1.pdf', 'file_name' => 'att1.pdf', 'category' => 'auction_doc']);
        $attB = AuctionAttachment::query()->create(['auction_id' => $auctionB->id, 'file_path' => 'auctions/attB.pdf', 'file_name' => 'attB.pdf', 'category' => 'auction_doc']);

        $updatedAuction = app(UpdateAuctionAction::class)->execute($auctionA, [
            'name' => 'Auc A',
            'remove_cover_image' => true,
            'remove_report_image' => true,
            'remove_attachments' => [$attA->id],
        ]);
        self::assertNull($updatedAuction->cover_image);
        self::assertNull($updatedAuction->report_image);
        self::assertDatabaseMissing('auction_attachments', ['id' => $attA->id]);
        Storage::disk('tenant_public')->assertMissing('auctions/c.jpg');
        Storage::disk('tenant_public')->assertMissing('auctions/r.jpg');
        Storage::disk('tenant_public')->assertMissing('auctions/att1.pdf');

        try {
            app(UpdateAuctionAction::class)->execute($auctionA, ['remove_attachments' => [$attB->id]]);
            self::fail('Cross-auction attachment removal must fail with ValidationException.');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('remove_attachments', $e->errors());
        }
    }

    public function test_checkout_payment_request_preserves_and_restores_receipt_across_validation_errors(): void
    {
        $session = app('session.store');
        $session->start();
        $session->put('checkout_verification', ['plan_id' => 1, 'verified_at' => now()->timestamp]);

        $receipt = UploadedFile::fake()->image('receipt.png');
        $failedRequest = CompleteCheckoutPaymentRequest::create('/checkout/1/payment', 'POST', [
            'payment_method' => 'bank_transfer',
            // bank_id intentionally missing to trigger validation error
        ], [], ['transfer_receipt' => $receipt]);
        $failedRequest->setLaravelSession($session);
        $failedRequest->setContainer(app());
        $failedRequest->setRedirector(app('redirect'));

        try {
            $failedRequest->validateResolved();
            self::fail('Expected validation error for missing bank_id.');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('bank_id', $e->errors());
        }

        $preserved = $session->get(CompleteCheckoutPaymentRequest::SESSION_RECEIPT_KEY);
        self::assertIsArray($preserved);
        self::assertSame('receipt.png', $preserved['name']);
        Storage::disk('local')->assertExists($preserved['path']);

        // Retry without re-uploading file -> prepareForValidation restores UploadedFile
        $retryRequest = CompleteCheckoutPaymentRequest::create('/checkout/1/payment', 'POST', [
            'payment_method' => 'bank_transfer',
        ]);
        $retryRequest->setLaravelSession($session);
        (function (): void {
            $this->prepareForValidation();
        })->call($retryRequest);

        self::assertTrue($retryRequest->hasFile('transfer_receipt'));
        self::assertSame('receipt.png', $retryRequest->file('transfer_receipt')->getClientOriginalName());

        // Explicitly removing the preserved receipt clears session & deletes temp file
        $removeRequest = CompleteCheckoutPaymentRequest::create('/checkout/1/payment', 'POST', [
            'payment_method' => 'bank_transfer',
            'remove_transfer_receipt' => '1',
        ]);
        $removeRequest->setLaravelSession($session);
        (function (): void {
            $this->prepareForValidation();
        })->call($removeRequest);

        self::assertFalse($removeRequest->hasFile('transfer_receipt'));
        self::assertNull($session->get(CompleteCheckoutPaymentRequest::SESSION_RECEIPT_KEY));
        Storage::disk('local')->assertMissing($preserved['path']);
    }
}
