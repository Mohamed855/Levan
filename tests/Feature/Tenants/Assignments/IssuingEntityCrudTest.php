<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Assignments;

use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Assignments\IssuingEntity;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IssuingEntityCrudTest extends TestCase
{
    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::findOrFail('test');
        tenancy()->initialize($this->tenant);
        $this->user = User::where('email', 'demo@lot.com')->firstOrFail();
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_can_view_issuing_entities_index(): void
    {
        $response = $this->actingAs($this->user, 'tenant')
            ->get(tenant_route_name('issuing-entities.index'));

        $response->assertOk();
        $response->assertSee('الجهات المسندة');
        $response->assertSee('تاريخ الإنشاء من');
        $response->assertSee('تاريخ الإنشاء إلى');
    }

    public function test_can_filter_issuing_entities_by_created_at_date_range(): void
    {
        $pastEntity = IssuingEntity::create([
            'name' => 'جهة قديمة للتصفية ' . uniqid(),
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);
        $pastEntity->timestamps = false;
        $pastEntity->created_at = now()->subDays(10);
        $pastEntity->save();

        $todayEntity = IssuingEntity::create([
            'name' => 'جهة اليوم للتصفية ' . uniqid(),
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'tenant')
            ->getJson(tenant_route_name('issuing-entities.index') . '?' . http_build_query([
                'draw' => 1,
                'created_from' => now()->format('Y-m-d'),
                'created_to' => now()->format('Y-m-d'),
            ]));

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name')->all();
        $this->assertContains($todayEntity->name, $names);
        $this->assertNotContains($pastEntity->name, $names);
    }

    public function test_can_create_new_issuing_entity(): void
    {
        $uniqueName = 'محكمة التنفيذ بالدرعية ' . uniqid();

        $response = $this->actingAs($this->user, 'tenant')
            ->post(tenant_route_name('issuing-entities.store'), [
                'name' => $uniqueName,
            ]);

        $response->assertRedirect(tenant_route_name('issuing-entities.index'));
        $this->assertDatabaseHas('issuing_entities', [
            'name' => $uniqueName,
            'is_active' => true,
        ]);
    }

    public function test_can_update_issuing_entity(): void
    {
        $entity = IssuingEntity::create([
            'name' => 'جهة للتحديث ' . uniqid(),
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $updatedName = 'جهة بعد التحديث ' . uniqid();

        $response = $this->actingAs($this->user, 'tenant')
            ->put(tenant_route_name('issuing-entities.update', $entity->id), [
                'name' => $updatedName,
            ]);

        $response->assertRedirect(tenant_route_name('issuing-entities.index'));
        $this->assertDatabaseHas('issuing_entities', [
            'id' => $entity->id,
            'name' => $updatedName,
            'is_active' => true,
        ]);
    }

    public function test_can_toggle_issuing_entity_status_with_numeric_and_string_values(): void
    {
        $entity = IssuingEntity::create([
            'name' => 'جهة للتبديل ' . uniqid(),
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        // Toggle via numeric 0 (as sent by switch without data attributes)
        $response = $this->actingAs($this->user, 'tenant')
            ->patchJson(tenant_route_name('issuing-entities.toggle-status', $entity->id), [
                'status' => 0,
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => false,
        ]);

        $this->assertDatabaseHas('issuing_entities', [
            'id' => $entity->id,
            'is_active' => false,
        ]);

        // Toggle back via string 'active'
        $response = $this->actingAs($this->user, 'tenant')
            ->patchJson(tenant_route_name('issuing-entities.toggle-status', $entity->id), [
                'status' => 'active',
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => true,
        ]);

        $this->assertDatabaseHas('issuing_entities', [
            'id' => $entity->id,
            'is_active' => true,
        ]);
    }

    public function test_can_delete_unlinked_issuing_entity(): void
    {
        $entity = IssuingEntity::create([
            'name' => 'جهة للحذف الفردي ' . uniqid(),
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'tenant')
            ->delete(tenant_route_name('issuing-entities.destroy', $entity->id));

        $response->assertRedirect();
        $this->assertSoftDeleted('issuing_entities', [
            'id' => $entity->id,
        ]);
    }

    public function test_cannot_delete_issuing_entity_linked_to_assignments(): void
    {
        $entity = IssuingEntity::create([
            'name' => 'جهة مرتبطة ' . uniqid(),
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        Assignment::create([
            'assignment_number' => 'ASG-LINK-' . uniqid(),
            'name' => 'إسناد مرتبط للتجربة',
            'issuing_entity_id' => $entity->id,
            'issuing_entity' => $entity->name,
            'category' => 'lands',
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'tenant')
            ->delete(tenant_route_name('issuing-entities.destroy', $entity->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Verify entity was NOT deleted
        $this->assertDatabaseHas('issuing_entities', [
            'id' => $entity->id,
            'deleted_at' => null,
        ]);
    }

    public function test_cannot_toggle_status_of_issuing_entity_linked_to_assignments(): void
    {
        $entity = IssuingEntity::create([
            'name' => 'جهة مرتبطة للتعطيل ' . uniqid(),
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        Assignment::create([
            'assignment_number' => 'ASG-TOGGLE-' . uniqid(),
            'name' => 'إسناد مرتبط لمنع التعطيل',
            'issuing_entity_id' => $entity->id,
            'issuing_entity' => $entity->name,
            'category' => 'lands',
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'tenant')
            ->patchJson(tenant_route_name('issuing-entities.toggle-status', $entity->id), [
                'status' => 'inactive',
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'لا يمكن تعطيل جهة مسندة مرتبطة بإسنادات في النظام.',
        ]);

        // Entity status must remain active
        $this->assertDatabaseHas('issuing_entities', [
            'id' => $entity->id,
            'is_active' => true,
        ]);
    }

    public function test_action_view_hides_delete_button_when_entity_has_assignments(): void
    {
        $this->actingAs($this->user, 'tenant');

        $linkedEntity = (object) [
            'id' => 999,
            'assignments_count' => 3,
        ];

        $renderedLinked = view('tenant.issuing-entities.action', [
            'row' => $linkedEntity,
            'route' => 'issuing-entities',
        ])->render();

        $this->assertStringNotContainsString('delete-form-999', $renderedLinked);
        $this->assertStringNotContainsString('js-delete-btn', $renderedLinked);

        $unlinkedEntity = (object) [
            'id' => 1000,
            'assignments_count' => 0,
        ];

        $renderedUnlinked = view('tenant.issuing-entities.action', [
            'row' => $unlinkedEntity,
            'route' => 'issuing-entities',
        ])->render();

        $this->assertStringContainsString('delete-form-1000', $renderedUnlinked);
        $this->assertStringContainsString('js-delete-btn', $renderedUnlinked);
    }

    public function test_can_validate_step_for_issuing_entities_create_via_wizard_draft_endpoint(): void
    {
        $response = $this->actingAs($this->user, 'tenant')
            ->postJson(tenant_route_name('wizard-drafts.validate-step'), [
                'target_path' => tenant_route_name('issuing-entities.store'),
                'target_method' => 'POST',
                'fields' => ['name'],
                'name' => 'جهة جديدة للتحقق ' . uniqid(),
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_can_validate_step_for_issuing_entities_update_via_wizard_draft_endpoint(): void
    {
        $entity = IssuingEntity::create([
            'name' => 'جهة للتحقق من التعديل ' . uniqid(),
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'tenant')
            ->postJson(tenant_route_name('wizard-drafts.validate-step'), [
                'target_path' => tenant_route_name('issuing-entities.update', $entity->id),
                'target_method' => 'PUT',
                'fields' => ['name'],
                'name' => 'جهة معدلة للتحقق ' . uniqid(),
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_can_save_and_retrieve_issuing_entity_draft(): void
    {
        $draftName = 'مسودة جهة مسندة ' . uniqid();

        // 1. Save draft
        $saveResponse = $this->actingAs($this->user, 'tenant')
            ->postJson(tenant_route_name('wizard-drafts.store'), [
                'resource_key' => 'tenant.issuing-entity-create',
                'step' => 1,
                'name' => $draftName,
            ]);

        $saveResponse->assertOk();
        $saveResponse->assertJson([
            'success' => true,
            'message' => 'تم حفظ المسودة بنجاح.',
        ]);

        $draftId = $saveResponse->json('draft_id');
        $this->assertNotEmpty($draftId);

        // 2. Retrieve draft
        $getResponse = $this->actingAs($this->user, 'tenant')
            ->getJson(tenant_route_name('wizard-drafts.index') . '?resource_key=tenant.issuing-entity-create');

        $getResponse->assertOk();
        $this->assertEquals($draftId, $getResponse->json('draft_id'));
        $this->assertEquals($draftName, $getResponse->json('data.name'));
    }

    public function test_draft_is_purged_after_issuing_entity_creation(): void
    {
        $draftName = 'جهة مسودة للتثبيت ' . uniqid();

        // 1. Save draft
        $saveResponse = $this->actingAs($this->user, 'tenant')
            ->postJson(tenant_route_name('wizard-drafts.store'), [
                'resource_key' => 'tenant.issuing-entity-create',
                'step' => 1,
                'name' => $draftName,
            ]);

        $draftId = $saveResponse->json('draft_id');

        // Verify draft exists in database
        $this->assertDatabaseHas('wizard_drafts', [
            'id' => $draftId,
            'resource_key' => 'tenant.issuing-entity-create',
        ]);

        // 2. Submit form to create entity with draft references
        $createResponse = $this->actingAs($this->user, 'tenant')
            ->post(tenant_route_name('issuing-entities.store'), [
                'name' => $draftName,
                '_wizard_draft_id' => $draftId,
                '_wizard_resource_key' => 'tenant.issuing-entity-create',
            ]);

        $createResponse->assertRedirect(tenant_route_name('issuing-entities.index'));

        // 3. Draft must be purged from database
        $this->assertDatabaseMissing('wizard_drafts', [
            'id' => $draftId,
        ]);
    }

    public function test_issuing_entity_seeder_is_linked_to_test_tenant_seeder_and_not_tenant_database_seeder(): void
    {
        $tenantDatabaseSeederContent = file_get_contents(base_path('database/seeders/tenant/TenantDatabaseSeeder.php'));
        $testTenantSeederContent = file_get_contents(base_path('database/seeders/admin/TestTenantSeeder.php'));

        // Must NOT be seeded for all newly created tenants in TenantDatabaseSeeder
        $this->assertStringNotContainsString('IssuingEntitySeeder::class', $tenantDatabaseSeederContent);

        // MUST be called in TestTenantSeeder
        $this->assertStringContainsString('IssuingEntitySeeder::class', $testTenantSeederContent);
    }

    public function test_can_create_new_issuing_entity_with_image(): void
    {
        Storage::fake('tenant_public');

        $uniqueName = 'محكمة التنفيذ مع صورة ' . uniqid();
        $fakeImage = UploadedFile::fake()->image('entity_logo.png', 200, 200);

        $response = $this->actingAs($this->user, 'tenant')
            ->post(tenant_route_name('issuing-entities.store'), [
                'name' => $uniqueName,
                'image' => $fakeImage,
            ]);

        $response->assertRedirect(tenant_route_name('issuing-entities.index'));

        $entity = IssuingEntity::where('name', $uniqueName)->firstOrFail();
        $this->assertNotNull($entity->image);
        Storage::disk('tenant_public')->assertExists($entity->image);
    }

    public function test_can_update_issuing_entity_image_and_replaces_old_file(): void
    {
        Storage::fake('tenant_public');

        $initialImage = UploadedFile::fake()->image('old_logo.png', 200, 200);
        $storedOldPath = $initialImage->store('issuing-entities', 'tenant_public');

        $entity = IssuingEntity::create([
            'name' => 'جهة لتحديث الصورة ' . uniqid(),
            'image' => $storedOldPath,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        Storage::disk('tenant_public')->assertExists($storedOldPath);

        $newImage = UploadedFile::fake()->image('new_logo.png', 300, 300);

        $response = $this->actingAs($this->user, 'tenant')
            ->put(tenant_route_name('issuing-entities.update', $entity->id), [
                'name' => $entity->name,
                'image' => $newImage,
            ]);

        $response->assertRedirect(tenant_route_name('issuing-entities.index'));

        $entity->refresh();
        $this->assertNotEquals($storedOldPath, $entity->image);
        Storage::disk('tenant_public')->assertMissing($storedOldPath);
        Storage::disk('tenant_public')->assertExists($entity->image);
    }

    public function test_can_remove_issuing_entity_image(): void
    {
        Storage::fake('tenant_public');

        $initialImage = UploadedFile::fake()->image('to_remove.png', 200, 200);
        $storedOldPath = $initialImage->store('issuing-entities', 'tenant_public');

        $entity = IssuingEntity::create([
            'name' => 'جهة لحذف الصورة ' . uniqid(),
            'image' => $storedOldPath,
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        Storage::disk('tenant_public')->assertExists($storedOldPath);

        $response = $this->actingAs($this->user, 'tenant')
            ->put(tenant_route_name('issuing-entities.update', $entity->id), [
                'name' => $entity->name,
                'remove_image' => 1,
            ]);

        $response->assertRedirect(tenant_route_name('issuing-entities.index'));

        $entity->refresh();
        $this->assertNull($entity->image);
        Storage::disk('tenant_public')->assertMissing($storedOldPath);
    }

    public function test_datatable_renders_image_column_before_name(): void
    {
        $entityWithImage = IssuingEntity::create([
            'name' => 'جهة مع صورة للجدول ' . uniqid(),
            'image' => 'issuing-entities/sample.png',
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'tenant')
            ->getJson(tenant_route_name('issuing-entities.index') . '?' . http_build_query(['draw' => 1]));

        $response->assertOk();
        $data = collect($response->json('data'))->firstWhere('name', $entityWithImage->name);
        $this->assertNotNull($data);
        $this->assertArrayHasKey('image', $data);
        $this->assertStringContainsString('rounded-circle', $data['image']);
    }
}
