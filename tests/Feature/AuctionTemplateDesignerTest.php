<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Access\Role;
use App\Models\Admin\AdminUser;
use App\Models\Admin\AuctionTemplate;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuctionTemplateDesignerTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
        $this->admin = AdminUser::factory()->create();
        $this->admin->assignRole(Role::query()->where('guard', 'admin')->where('name', 'SuperAdmin')->firstOrFail());
    }

    public function test_template_create_form_renders_designer_container_and_form_controls(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.auction-templates.create'));

        $response->assertOk()
            ->assertSee('data-template-designer', false)
            ->assertSee('data-canvas', false)
            ->assertSee('data-properties', false)
            ->assertSee('data-page-tabs', false)
            ->assertSee('data-schema-input', false)
            ->assertSee('name="type"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="description"', false)
            ->assertDontSee('x-filter.select')
            ->assertDontSee('x-shared.form.controls');
    }

    public function test_template_edit_form_renders_designer_with_existing_template(): void
    {
        $template = AuctionTemplate::query()->create([
            'name' => 'قالب تسويقي',
            'type' => 'brochure',
            'status' => 'draft',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.auction-templates.edit', $template));

        $response->assertOk()
            ->assertSee('data-template-designer', false)
            ->assertSee('data-canvas', false)
            ->assertSee('data-properties', false)
            ->assertSee('data-page-tabs', false)
            ->assertSee($template->name);
    }

    public function test_template_can_be_stored_with_schema(): void
    {
        $schema = json_encode([
            'canvas' => ['width' => 794, 'height' => 1123],
            'pages' => [
                [
                    'id' => 'page-1',
                    'background' => ['color' => '#ffffff'],
                    'elements' => [
                        [
                            'id' => 'elem-1',
                            'type' => 'variable',
                            'x' => 50,
                            'y' => 50,
                            'width' => 300,
                            'height' => 60,
                            'binding' => 'auction.name',
                            'required' => true,
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->actingAs($this->admin, 'admin')->post(route('admin.auction-templates.store'), [
            'name' => 'قالب مزاد عقاري',
            'type' => 'brochure',
            'description' => 'وصف القالب العقاري',
            'is_active' => '1',
            'schema' => $schema,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('auction_templates', [
            'name' => 'قالب مزاد عقاري',
            'type' => 'brochure',
        ]);
    }
}
