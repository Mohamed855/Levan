<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Admin\AuctionTemplates\CreateAuctionTemplateAction;
use App\Enums\Admin\AuctionTemplates\AuctionTemplateType;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\AuctionTemplates\AuctionTemplate;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\UserMangment\User\User;
use App\Rules\ExistsInCentralAuctionTemplates;
use App\Services\Tenants\Auctions\AuctionOutputService;
use App\Services\Tenants\Shared\CentralAuctionTemplatesGateway;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AuctionTemplatesFeatureTest extends TestCase
{
    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('central_public');

        $this->admin = AdminUser::firstOrCreate(
            ['email' => 'admin_test@levan.com'],
            [
                'name' => 'Admin Test',
                'password' => bcrypt('password'),
                'phone' => '0500000099',
                'status' => 'active',
            ]
        );
        $this->admin->givePermissionTo([
            'auction_templates.view',
            'auction_templates.create',
            'auction_templates.edit',
            'auction_templates.delete',
        ]);
    }

    public function test_superadmin_can_create_auction_template_with_fields(): void
    {
        $rawPdf = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n"
            . "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n"
            . "3 0 obj\n<< /Type /Page /Parent 2 0 R >>\nendobj\n"
            . "4 0 obj\n<< /T (auction_name) /FT /Tx >>\nendobj\n"
            . "5 0 obj\n<< /T (auction_date) /FT /Tx >>\nendobj\n"
            . "xref\n0 6\ntrailer\n<< /Root 1 0 R >>\n%%EOF";

        $pdfFile = UploadedFile::fake()->createWithContent('template.pdf', $rawPdf);
        $thumbnail = UploadedFile::fake()->image('thumb.png', 400, 300);

        $action = app(CreateAuctionTemplateAction::class);
        $template = $action->execute([
            'name' => 'بروشور تجريبي ' . uniqid(),
            'type' => AuctionTemplateType::BROCHURE->value,
            'description' => 'وصف القالب التجريبي',
            'is_active' => true,
        ], $pdfFile, $thumbnail);

        $this->assertDatabaseHas('auction_templates', [
            'id' => $template->id,
            'type' => 'brochure',
            'is_active' => 1,
        ]);

        $this->assertNotNull($template->file_path);
        $this->assertNotNull($template->thumbnail_path);
        $this->assertContains('auction_name', $template->field_names);
        $this->assertContains('auction_date', $template->field_names);
    }

    public function test_gateway_returns_active_templates_by_type(): void
    {
        $template = AuctionTemplate::create([
            'name' => 'قالب بوابة نشط ' . uniqid(),
            'type' => AuctionTemplateType::BROCHURE,
            'file_path' => 'auction_templates/brochure/test.pdf',
            'field_names' => ['auction_name'],
            'is_active' => true,
        ]);

        $gateway = app(CentralAuctionTemplatesGateway::class);
        $activeBrochures = $gateway->activeByType('brochure');

        $this->assertNotEmpty($activeBrochures);
        $found = collect($activeBrochures)->firstWhere('value', $template->id);
        $this->assertNotNull($found);
        $this->assertEquals($template->name, $found['label']);

        $foundModel = $gateway->findActive($template->id, 'brochure');
        $this->assertNotNull($foundModel);
        $this->assertEquals($template->id, $foundModel->id);

        $this->assertTrue($gateway->existsActive($template->id, 'brochure'));
        $this->assertFalse($gateway->existsActive(999999, 'brochure'));
    }

    public function test_validation_rule_validates_against_central_templates(): void
    {
        $template = AuctionTemplate::create([
            'name' => 'قالب فحص نشط ' . uniqid(),
            'type' => AuctionTemplateType::BILLBOARD,
            'file_path' => 'auction_templates/billboard/test.pdf',
            'is_active' => true,
        ]);

        $rule = new ExistsInCentralAuctionTemplates('billboard');

        $validatorPass = Validator::make(
            ['billboard_template_id' => $template->id],
            ['billboard_template_id' => [$rule]]
        );
        $this->assertTrue($validatorPass->passes());

        $validatorFail = Validator::make(
            ['billboard_template_id' => 999999],
            ['billboard_template_id' => [$rule]]
        );
        $this->assertTrue($validatorFail->fails());
    }

    public function test_auction_output_service_builds_correct_data(): void
    {
        $tenant = Tenant::first();
        if (! $tenant) {
            $this->markTestSkipped('No tenant found in database.');
        }

        tenancy()->initialize($tenant);

        try {
            $owner = User::first();
            $assignment = Assignment::firstOrCreate(
                ['assignment_number' => 'ASN-TEST-' . uniqid()],
                [
                    'name' => 'إسناد تجريبي للمخرجات',
                    'issuing_entity' => 'محكمة التنفيذ',
                    'is_active' => true,
                    'created_by' => $owner?->id,
                ]
            );

            $auction = Auction::create([
                'assignment_id' => $assignment->id,
                'name' => 'مزاد لوت الرياض التجريبي',
                'description' => 'وصف تفصيلي للمزاد',
                'start_date' => now()->toDateString(),
                'start_time' => '16:00',
                'end_date' => now()->addDays(2)->toDateString(),
                'end_time' => '18:00',
                'type' => 'electronic',
                'category' => 'lands',
                'city' => 'الرياض',
                'platform_name' => 'منصة المزاد الوطنية',
                'platform_url' => 'https://example.com/auction',
                'supervising_entity' => 'وزارة العدل',
                'status' => 'active',
                'is_active' => true,
            ]);

            $service = app(AuctionOutputService::class);
            $brochureData = $service->buildFieldData($auction, 'brochure');

            $this->assertEquals('مزاد لوت الرياض التجريبي', $brochureData['auction_name']);
            $this->assertEquals('الرياض', $brochureData['auction_city']);
            $this->assertEquals('منصة المزاد الوطنية', $brochureData['platform_name']);
            $this->assertEquals('وزارة العدل', $brochureData['supervising_entity']);
            $this->assertEquals('إلكتروني', $brochureData['auction_type']);
            $this->assertEquals($assignment->assignment_number, $brochureData['assignment_number']);
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
        }
    }

    public function test_superadmin_can_view_index_and_show_template(): void
    {
        $template = AuctionTemplate::create([
            'name' => 'قالب فحص العرض ' . uniqid(),
            'type' => AuctionTemplateType::BROCHURE,
            'file_path' => 'auction_templates/brochure/test.pdf',
            'field_names' => ['auction_name', 'auction_date'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.auction-templates.index'));

        $response->assertOk();
        $response->assertSee('قوالب المزادات');
        $response->assertSee('auction-templates-table');

        $showResponse = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.auction-templates.show', $template));

        $showResponse->assertOk();
        $showResponse->assertSee($template->name);
        $showResponse->assertSee('auction_name');
    }

    public function test_superadmin_can_toggle_template_status(): void
    {
        $template = AuctionTemplate::create([
            'name' => 'قالب فحص التفعيل ' . uniqid(),
            'type' => AuctionTemplateType::BILLBOARD,
            'file_path' => 'auction_templates/billboard/test.pdf',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->patchJson(route('admin.auction-templates.toggle-status', $template));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => false,
        ]);

        $this->assertFalse($template->fresh()->is_active);
    }

    public function test_superadmin_can_delete_template(): void
    {
        $template = AuctionTemplate::create([
            'name' => 'قالب فحص الحذف ' . uniqid(),
            'type' => AuctionTemplateType::SOCIAL,
            'file_path' => 'auction_templates/social/test.pdf',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->deleteJson(route('admin.auction-templates.destroy', $template));

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertSoftDeleted('auction_templates', ['id' => $template->id]);
    }

    public function test_superadmin_can_create_auction_template_with_image_file(): void
    {
        $imageFile = UploadedFile::fake()->image('template_poster.png', 1200, 1200);

        $action = app(CreateAuctionTemplateAction::class);
        $template = $action->execute([
            'name' => 'قالب بوستر صورة ' . uniqid(),
            'type' => AuctionTemplateType::SOCIAL->value,
            'description' => 'قالب صورة للتواصل الاجتماعي',
            'is_active' => true,
        ], $imageFile, null);

        $this->assertDatabaseHas('auction_templates', [
            'id' => $template->id,
            'type' => 'social',
            'is_active' => 1,
        ]);

        $this->assertNotNull($template->file_path);
        // Image template should auto-use file_path as thumbnail if no explicit thumbnail given
        $this->assertEquals($template->file_path, $template->thumbnail_path);
        $this->assertNotEmpty($template->field_names);
        $this->assertContains('auction_name', $template->field_names);
    }

    public function test_auction_output_service_generates_pdf_file_from_template(): void
    {
        $tenant = Tenant::first();
        if (! $tenant) {
            $this->markTestSkipped('No tenant found in database.');
        }

        // Create an image template file on central_public
        $imgContent = imagecreatetruecolor(600, 800);
        ob_start();
        imagepng($imgContent);
        $rawPng = ob_get_clean();
        imagedestroy($imgContent);

        $relPath = 'auction_templates/brochure/test_bg_' . uniqid() . '.png';
        Storage::disk('central_public')->put($relPath, $rawPng);

        $template = AuctionTemplate::create([
            'name' => 'قالب بروشور حقيقي ' . uniqid(),
            'type' => AuctionTemplateType::BROCHURE,
            'file_path' => $relPath,
            'is_active' => true,
        ]);

        tenancy()->initialize($tenant);

        try {
            $assignment = Assignment::firstOrCreate(
                ['assignment_number' => 'ASN-OUT-' . uniqid()],
                [
                    'name' => 'إسناد المخرجات',
                    'issuing_entity' => 'مركز الإسناد والتصفية (إنفاذ)',
                    'is_active' => true,
                ]
            );

            $auction = Auction::create([
                'assignment_id' => $assignment->id,
                'name' => 'مزاد لوت النرجس التلقائي',
                'description' => 'مزاد تجريبي لتأكيد المخرجات',
                'start_date' => now()->toDateString(),
                'start_time' => '16:00',
                'end_date' => now()->addDays(3)->toDateString(),
                'end_time' => '19:00',
                'type' => 'electronic',
                'category' => 'buildings',
                'city' => 'الرياض',
                'platform_name' => 'سعودي مزاد',
                'platform_url' => 'https://saudimazad.sa',
                'supervising_entity' => 'إنفاذ',
                'status' => 'active',
                'is_active' => true,
            ]);

            $service = app(AuctionOutputService::class);
            $generatedPdfPath = $service->generate($auction, 'brochure', $template->id);

            $this->assertFileExists($generatedPdfPath);
            $content = file_get_contents($generatedPdfPath);
            $this->assertStringStartsWith('%PDF-', $content);

            // Cleanup generated file
            if (file_exists($generatedPdfPath)) {
                @unlink($generatedPdfPath);
            }
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
        }
    }

    public function test_superadmin_can_view_create_wizard_with_canonical_contracts(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.auction-templates.create'));

        $response->assertOk();
        $response->assertSee('wizard-validation');
        $response->assertSee('data-form-validate');
        $response->assertSee('data-draft-resource-key="admin.auction-template-create"', false);
        $response->assertSee('step-basic-info');
        $response->assertSee('step-files');
        $response->assertSee('step-review');
        $response->assertSee('assignment-review');
        $response->assertSee('data-edit-step');
        $response->assertSee('preview-template-name');
    }

    public function test_superadmin_can_view_edit_page_with_canonical_contracts(): void
    {
        $template = AuctionTemplate::create([
            'name' => 'قالب فحص شاشة التعديل ' . uniqid(),
            'type' => AuctionTemplateType::BROCHURE,
            'file_path' => 'auction_templates/brochure/test.pdf',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.auction-templates.edit', $template));

        $response->assertOk();
        $response->assertSee('auction-template-edit-form');
        $response->assertSee('تعديل قالب المزاد:');
        $response->assertSee('data-form-validate');
        $response->assertSee($template->name);
    }

    public function test_superadmin_index_displays_five_lifecycle_stat_cards(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.auction-templates.index'));

        $response->assertOk();
        $response->assertSee('إجمالي القوالب');
        $response->assertSee('قوالب مفعلة');
        $response->assertSee('قوالب البروشور');
        $response->assertSee('اللوحات الإعلانية');
        $response->assertSee('قوالب معطلة');
    }

    public function test_specification_rule_enforces_type_dimensions_and_formats(): void
    {
        $brochureRule = new \App\Rules\AuctionTemplateFileSpecificationRule('brochure');
        $socialRule = new \App\Rules\AuctionTemplateFileSpecificationRule('social');
        $billboardRule = new \App\Rules\AuctionTemplateFileSpecificationRule('billboard');

        // 1. Brochure must be PDF, rejecting images
        $imageFile = UploadedFile::fake()->image('brochure.png', 1080, 1080);
        $v1 = Validator::make(['file' => $imageFile], ['file' => [$brochureRule]]);
        $this->assertTrue($v1->fails());

        // 2. Social must be square (rejecting non-square images like 1200x600)
        $wideImage = UploadedFile::fake()->image('social_wide.png', 1200, 600);
        $v2 = Validator::make(['file' => $wideImage], ['file' => [$socialRule]]);
        $this->assertTrue($v2->fails());

        $squareImage = UploadedFile::fake()->image('social_square.png', 1080, 1080);
        $v3 = Validator::make(['file' => $squareImage], ['file' => [$socialRule]]);
        $this->assertTrue($v3->passes());

        // 3. Billboard must be landscape (rejecting portrait like 600x1200)
        $portraitImage = UploadedFile::fake()->image('billboard_portrait.png', 600, 1200);
        $v4 = Validator::make(['file' => $portraitImage], ['file' => [$billboardRule]]);
        $this->assertTrue($v4->fails());

        $landscapeImage = UploadedFile::fake()->image('billboard_landscape.png', 1920, 1080);
        $v5 = Validator::make(['file' => $landscapeImage], ['file' => [$billboardRule]]);
        $this->assertTrue($v5->passes());
    }

    public function test_auction_output_service_generates_social_png_image(): void
    {
        $tenant = Tenant::first();
        if (! $tenant) {
            $this->markTestSkipped('No tenant found in database.');
        }

        $template = AuctionTemplate::create([
            'name' => 'قالب سوشيال تجريبي ' . uniqid(),
            'type' => AuctionTemplateType::SOCIAL,
            'file_path' => 'auction_templates/social/test.png',
            'is_active' => true,
        ]);

        // Place a mock template image on disk
        $disk = Storage::disk('central_public');
        $disk->put('auction_templates/social/test.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));

        tenancy()->initialize($tenant);

        try {
            $assignment = Assignment::firstOrCreate(
                ['assignment_number' => 'ASN-IMG-' . uniqid()],
                [
                    'name' => 'إسناد فحص الصور',
                    'issuing_entity' => 'مركز الإسناد والتصفية (إنفاذ)',
                    'is_active' => true,
                ]
            );

            $auction = Auction::create([
                'assignment_id' => $assignment->id,
                'name' => 'مزاد لوت سوشيال التجريبي',
                'description' => 'مزاد فحص توليد صورة السوشيال',
                'start_date' => now()->toDateString(),
                'start_time' => '16:00',
                'end_date' => now()->addDays(2)->toDateString(),
                'end_time' => '18:00',
                'type' => 'electronic',
                'category' => 'lands',
                'city' => 'جدة',
                'platform_name' => 'سعودي مزاد',
                'social_template_id' => $template->id,
                'status' => 'active',
                'is_active' => true,
            ]);

            $service = app(AuctionOutputService::class);
            $pngPath = $service->generateSocialImage($auction);

            $this->assertFileExists($pngPath);
            $content = file_get_contents($pngPath);
            // Verify PNG header: 89 50 4E 47 0D 0A 1A 0A
            $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $content);

            if (file_exists($pngPath)) {
                @unlink($pngPath);
            }
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
            $disk->delete('auction_templates/social/test.png');
        }
    }
}

