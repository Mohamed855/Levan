<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Assignments;

use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Assignments\IssuingEntity;
use App\Models\Tenants\UserMangment\User\User;
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
    }

    public function test_can_create_new_issuing_entity(): void
    {
        $uniqueName = 'محكمة التنفيذ بالدرعية ' . uniqid();

        $response = $this->actingAs($this->user, 'tenant')
            ->post(tenant_route_name('issuing-entities.store'), [
                'name' => $uniqueName,
                'is_active' => '1',
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
                'is_active' => '0',
            ]);

        $response->assertRedirect(tenant_route_name('issuing-entities.index'));
        $this->assertDatabaseHas('issuing_entities', [
            'id' => $entity->id,
            'name' => $updatedName,
            'is_active' => false,
        ]);
    }

    public function test_can_toggle_issuing_entity_status(): void
    {
        $entity = IssuingEntity::create([
            'name' => 'جهة للتبديل ' . uniqid(),
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'tenant')
            ->patchJson(tenant_route_name('issuing-entities.toggle-status', $entity->id), [
                'status' => 'inactive',
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
}
