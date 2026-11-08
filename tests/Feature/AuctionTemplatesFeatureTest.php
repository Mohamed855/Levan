<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Admin\AuctionTemplates\CreateAuctionTemplateAction;
use App\Actions\Admin\AuctionTemplates\PublishAuctionTemplateVersionAction;
use App\Actions\Tenants\Auctions\CreateAuctionAction;
use App\Enums\Admin\AuctionTemplates\AuctionTemplateType;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\AuctionTemplates\AuctionTemplate;
use App\Models\Admin\AuctionTemplates\AuctionTemplateVersion;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\UserMangment\User\User;
use App\Rules\AuctionTemplateFileSpecificationRule;
use App\Rules\ExistsInCentralAuctionTemplates;
use App\Rules\PublishedTemplateVersionRule;
use App\Services\Tenants\Auctions\AuctionTemplateRenderer;
use App\Services\Tenants\Shared\CentralAuctionTemplatesGateway;
use App\Support\Auctions\TemplateSchema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AuctionTemplatesFeatureTest extends TestCase
{
    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('central_public');

        $this->admin = AdminUser::firstOrCreate(
            ['email' => 'admin_test@lot.com'],
            [
                'name' => 'Admin Test',
                'password' => bcrypt('password'),
                'phone' => '0500000099',
                'status' => 'active',
                'is_super_admin' => false,
            ]
        );

        try {
            $this->admin->givePermissionTo([
                'auction_templates.view',
                'auction_templates.create',
                'auction_templates.edit',
                'auction_templates.delete',
            ]);
        } catch (\Throwable) {
            // Unified schema compatibility
        }
    }

    public function test_superadmin_can_create_auction_template_with_fields(): void
    {
        $rawPdf = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n"
            ."2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n"
            ."3 0 obj\n<< /Type /Page /Parent 2 0 R >>\nendobj\n"
            ."4 0 obj\n<< /T (auction_name) /FT /Tx >>\nendobj\n"
            ."5 0 obj\n<< /T (auction_date) /FT /Tx >>\nendobj\n"
            ."xref\n0 6\ntrailer\n<< /Root 1 0 R >>\n%%EOF";

        $pdfFile = UploadedFile::fake()->createWithContent('template.pdf', $rawPdf);

        $action = app(CreateAuctionTemplateAction::class);
        $template = $action->execute([
            'name' => 'قالب إعلاني تجريبي '.uniqid(),
            'type' => AuctionTemplateType::BILLBOARD->value,
            'description' => 'وصف القالب التجريبي',
            'is_active' => true,
        ], $pdfFile);

        $this->assertDatabaseHas('auction_templates', [
            'id' => $template->id,
            'type' => 'billboard',
            'is_active' => 1,
        ]);

        $this->assertNotNull($template->file_path);
        $this->assertContains('auction_name', $template->field_names);
        $this->assertContains('auction_date', $template->field_names);
    }

    public function test_gateway_returns_active_templates_by_type(): void
    {
        $template = AuctionTemplate::create([
            'name' => 'قالب بوابة نشط '.uniqid(),
            'type' => AuctionTemplateType::BILLBOARD,
            'file_path' => 'auction_templates/billboard/test.png',
            'field_names' => ['auction_name'],
            'is_active' => true,
        ]);

        $gateway = app(CentralAuctionTemplatesGateway::class);
        $activeBillboards = $gateway->activeByType('billboard');

        $this->assertNotEmpty($activeBillboards);
        $found = collect($activeBillboards)->firstWhere('value', $template->id);
        $this->assertNotNull($found);
        $this->assertEquals($template->name, $found['label']);

        $foundModel = $gateway->findActive($template->id, 'billboard');
        $this->assertNotNull($foundModel);
        $this->assertEquals($template->id, $foundModel->id);

        $this->assertTrue($gateway->existsActive($template->id, 'billboard'));
        $this->assertFalse($gateway->existsActive(999999, 'billboard'));
    }

    public function test_validation_rule_validates_against_central_templates(): void
    {
        $template = AuctionTemplate::create([
            'name' => 'قالب فحص نشط '.uniqid(),
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

    public function test_auction_template_renderer_builds_correct_bindings(): void
    {
        $tenant = Tenant::find('test') ?? Tenant::first();
        if (! $tenant) {
            $this->markTestSkipped('No tenant found in database.');
        }

        tenancy()->initialize($tenant);

        try {
            $owner = User::first();
            $assignment = Assignment::firstOrCreate(
                ['assignment_number' => 'ASN-TEST-'.uniqid()],
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

            $renderer = app(AuctionTemplateRenderer::class);
            $bindings = $renderer->bindings($auction);

            $this->assertEquals('مزاد لوت الرياض التجريبي', $bindings['auction.name']);
            $this->assertEquals('الرياض', $bindings['auction.city']);
            $this->assertEquals('منصة المزاد الوطنية', $bindings['auction.platform_name']);
            $this->assertEquals('وزارة العدل', $bindings['auction.supervising_entity']);
            $this->assertEquals('electronic', $bindings['auction.type']);
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
        }
    }

    public function test_superadmin_can_view_index_and_show_template(): void
    {
        $template = AuctionTemplate::create([
            'name' => 'قالب فحص العرض '.uniqid(),
            'type' => AuctionTemplateType::BILLBOARD,
            'file_path' => 'auction_templates/billboard/test.png',
            'field_names' => ['auction_name', 'auction_date'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.auction-templates.index'));

        $response->assertOk();
        $response->assertSee('قوالب إعلانية');
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
            'name' => 'قالب فحص التفعيل '.uniqid(),
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
            'name' => 'قالب فحص الحذف '.uniqid(),
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

    public function test_superadmin_cannot_create_a_static_image_as_a_fillable_auction_template(): void
    {
        $imageFile = UploadedFile::fake()->image('template_poster.png', 1200, 1200);

        $action = app(CreateAuctionTemplateAction::class);
        $this->expectException(ValidationException::class);

        $action->execute([
            'name' => 'قالب بوستر صورة '.uniqid(),
            'type' => AuctionTemplateType::SOCIAL->value,
            'description' => 'قالب صورة للتواصل الاجتماعي',
            'is_active' => true,
        ], $imageFile);
    }

    public function test_superadmin_can_save_and_retrieve_wizard_draft_for_templates(): void
    {
        $draftData = [
            'resource_key' => 'admin.auction-templates.create',
            'step' => 1,
            'name' => 'قالب مسودة إعلاني تجريبي',
            'type' => 'billboard',
            'description' => 'وصف المسودة',
            'schema' => json_encode(['canvas' => ['width' => 1200, 'height' => 628]]),
        ];

        $saveResponse = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.wizard-drafts.store'), $draftData);

        $saveResponse->assertOk();
        $saveResponse->assertJson([
            'success' => true,
            'message' => 'تم حفظ المسودة بنجاح.',
        ]);

        $draftId = $saveResponse->json('draft_id');
        $this->assertNotEmpty($draftId);

        $getResponse = $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.wizard-drafts.index', ['resource_key' => 'admin.auction-templates.create']));

        $getResponse->assertOk();
        $this->assertEquals($draftId, $getResponse->json('draft_id'));
        $this->assertEquals('قالب مسودة إعلاني تجريبي', $getResponse->json('data.name'));
    }

    public function test_superadmin_can_view_create_wizard_with_canonical_contracts(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.auction-templates.create'));

        $response->assertOk();
        $response->assertSee('data-form-validate');
        $response->assertSee('data-draft-resource-key="admin.auction-templates.create"', false);
        $response->assertSee('template-designer');
        $response->assertSee('template-canvas');
        $response->assertSee('designer-preview-modal');
        $response->assertSee('حفظ كمسودة');
    }

    public function test_superadmin_can_view_edit_page_with_canonical_contracts(): void
    {
        $template = AuctionTemplate::create([
            'name' => 'قالب فحص شاشة التعديل '.uniqid(),
            'type' => AuctionTemplateType::BILLBOARD,
            'file_path' => 'auction_templates/billboard/test.png',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.auction-templates.edit', $template));

        $response->assertOk();
        $response->assertSee('auction-template-edit-form');
        $response->assertSee('تعديل القالب الإعلاني:');
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
        $response->assertSee('سوشيال ميديا');
        $response->assertSee('اللوحات الإعلانية');
        $response->assertSee('قوالب معطلة');
    }

    public function test_specification_rule_enforces_type_dimensions_and_formats(): void
    {
        $socialRule = new AuctionTemplateFileSpecificationRule('social');
        $billboardRule = new AuctionTemplateFileSpecificationRule('billboard');

        // 1. PDF files must be rejected
        $pdfFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
        $v1 = Validator::make(['file' => $pdfFile], ['file' => [$socialRule]]);
        $this->assertTrue($v1->fails());

        // 2. Social square image templates are accepted for manual field placement.
        $wideImage = UploadedFile::fake()->image('social_wide.png', 1200, 600);
        $v2 = Validator::make(['file' => $wideImage], ['file' => [$socialRule]]);
        $this->assertTrue($v2->fails());

        $squareImage = UploadedFile::fake()->image('social_square.png', 1080, 1080);
        $v3 = Validator::make(['file' => $squareImage], ['file' => [$socialRule]]);
        $this->assertFalse($v3->fails());

        // 3. Billboard images must remain landscape.
        $portraitImage = UploadedFile::fake()->image('billboard_portrait.png', 600, 1200);
        $v4 = Validator::make(['file' => $portraitImage], ['file' => [$billboardRule]]);
        $this->assertTrue($v4->fails());

        $landscapeImage = UploadedFile::fake()->image('billboard_landscape.png', 1920, 1080);
        $v5 = Validator::make(['file' => $landscapeImage], ['file' => [$billboardRule]]);
        $this->assertFalse($v5->fails());
    }

    public function test_superadmin_can_create_auction_template_with_schema_and_version(): void
    {
        $action = app(CreateAuctionTemplateAction::class);

        $validSchema = [
            'canvas' => ['width' => 1080, 'height' => 1080, 'unit' => 'px', 'background' => '#ffffff'],
            'pages' => [
                [
                    'page_number' => 1,
                    'elements' => [
                        [
                            'id' => 'elem-title',
                            'type' => 'text',
                            'binding' => 'auction.name',
                            'default_value' => 'اسم المزاد',
                            'position' => ['x' => 50, 'y' => 50, 'width' => 400, 'height' => 60],
                            'style' => ['fontSize' => 24, 'color' => '#1D4F44', 'fontFamily' => 'Almarai'],
                        ],
                    ],
                ],
            ],
        ];

        $template = $action->execute([
            'name' => 'قالب إعلاني فيرجن '.uniqid(),
            'type' => AuctionTemplateType::BILLBOARD->value,
            'description' => 'قالب إعلاني بمصمم مرئي ومخطط',
            'schema' => $validSchema,
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('auction_templates', [
            'id' => $template->id,
            'type' => 'billboard',
            'status' => 'draft',
            'current_version' => null,
        ]);

        $this->assertDatabaseHas('auction_template_versions', [
            'auction_template_id' => $template->id,
            'version' => 1,
            'status' => 'draft',
        ]);

        $version = $template->versions()->first();
        $this->assertNotNull($version);
        $this->assertSame(1080, $version->schema['canvas']['width'] ?? null);
    }

    public function test_superadmin_can_publish_template_version(): void
    {
        $template = AuctionTemplate::create([
            'name' => 'قالب للنشر '.uniqid(),
            'type' => AuctionTemplateType::BILLBOARD,
            'status' => 'draft',
            'current_version' => 1,
            'is_active' => true,
        ]);

        $version = AuctionTemplateVersion::create([
            'auction_template_id' => $template->id,
            'version' => 1,
            'schema' => TemplateSchema::sample(AuctionTemplateType::BILLBOARD),
            'status' => 'draft',
        ]);

        $action = app(PublishAuctionTemplateVersionAction::class);
        $published = $action->execute($version);

        $this->assertSame('published', $published->status);
        $this->assertNotNull($published->published_at);

        $template->refresh();
        $this->assertSame('published', $template->status);
        $this->assertSame(1, $template->current_version);
    }

    public function test_gateway_returns_published_template_versions_only(): void
    {
        $template = AuctionTemplate::create([
            'name' => 'قالب سوشيال منشور '.uniqid(),
            'type' => AuctionTemplateType::SOCIAL,
            'status' => 'published',
            'current_version' => 2,
            'is_active' => true,
        ]);

        AuctionTemplateVersion::create([
            'auction_template_id' => $template->id,
            'version' => 1,
            'schema' => TemplateSchema::sample(AuctionTemplateType::SOCIAL),
            'status' => 'archived',
        ]);

        $v2 = AuctionTemplateVersion::create([
            'auction_template_id' => $template->id,
            'version' => 2,
            'schema' => TemplateSchema::sample(AuctionTemplateType::SOCIAL),
            'status' => 'published',
            'published_at' => now(),
        ]);

        $draftTemplate = AuctionTemplate::create([
            'name' => 'قالب مسودة '.uniqid(),
            'type' => AuctionTemplateType::SOCIAL,
            'status' => 'draft',
            'current_version' => 1,
            'is_active' => true,
        ]);

        AuctionTemplateVersion::create([
            'auction_template_id' => $draftTemplate->id,
            'version' => 1,
            'schema' => TemplateSchema::sample(AuctionTemplateType::SOCIAL),
            'status' => 'draft',
        ]);

        $gateway = app(CentralAuctionTemplatesGateway::class);
        $publishedSocial = $gateway->publishedByType('social');

        $values = array_column($publishedSocial, 'value');
        $this->assertContains($v2->id, $values);

        $found = $gateway->findPublished($v2->id, 'social');
        $this->assertNotNull($found);
        $this->assertSame($v2->id, $found->id);

        $this->assertTrue($gateway->existsPublished($v2->id, 'social'));
        $this->assertFalse($gateway->existsPublished(999999, 'social'));
    }
}

