<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionAttachment;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Auctions\AuctionService;
use App\Services\Tenants\Properties\PropertyService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TenantAssetModulesFeatureTest extends TestCase
{
    private Tenant $tenant;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::findOrFail('test');
        tenancy()->initialize($this->tenant);
        $this->owner = User::where('email', 'demo@levan.com')->firstOrFail();
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_an_auction_cannot_link_a_property_from_another_assignment(): void
    {
        [$selectedAssignment, $otherAssignment] = $this->assignments();
        $otherProperty = Property::create([
            'assignment_id' => $otherAssignment->id,
            'property_number' => 'PROP-'.uniqid(),
            'name' => 'عقار اختبار غير تابع للإسناد',
            'type' => 'residential_land',
            'city' => 'الرياض',
            'area' => 100,
            'data_status' => 'completed',
            'status' => 'available',
            'is_active' => true,
            'created_by' => $this->owner->id,
        ]);

        $this->actingAs($this->owner, 'tenant')
            ->postJson($this->tenantUrl('auctions'), [
                'name' => 'مزاد ربط غير صالح',
                'start_date' => now()->addDay()->toDateString(),
                'end_date' => now()->addDays(2)->toDateString(),
                'type' => 'electronic',
                'category' => 'lands',
                'city' => 'الرياض',
                'assignment_id' => $selectedAssignment->id,
                'property_ids' => [$otherProperty->id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('property_ids.0');
    }

    public function test_auction_service_defends_the_assignment_property_invariant(): void
    {
        [$selectedAssignment, $otherAssignment] = $this->assignments();
        $otherProperty = Property::create([
            'assignment_id' => $otherAssignment->id,
            'property_number' => 'SERVICE-'.uniqid(),
            'name' => 'Service invariant property',
            'type' => 'residential_land',
            'city' => 'Riyadh',
            'area' => 100,
            'data_status' => 'completed',
            'status' => 'available',
            'is_active' => true,
            'created_by' => $this->owner->id,
        ]);

        $this->actingAs($this->owner, 'tenant');
        $this->expectException(ValidationException::class);

        app(AuctionService::class)->createAuction([
            'name' => 'Invalid service auction',
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'type' => 'electronic',
            'category' => 'lands',
            'city' => 'Riyadh',
            'assignment_id' => $selectedAssignment->id,
            'property_ids' => [$otherProperty->id],
        ]);
    }

    public function test_assignment_creation_links_properties_and_stores_polymorphic_files(): void
    {
        Storage::fake('tenant_public');
        $property = Property::create([
            'assignment_id' => null,
            'property_number' => 'PROP-'.uniqid(),
            'name' => 'عقار إسناد جديد',
            'type' => 'villa',
            'city' => 'الرياض',
            'district' => 'الياسمين',
            'area' => 400,
            'data_status' => 'completed',
            'status' => 'available',
            'is_active' => true,
            'created_by' => $this->owner->id,
        ]);
        $assignmentNumber = 'TEST-CREATE-'.strtoupper(uniqid());

        $this->actingAs($this->owner, 'tenant')
            ->post($this->tenantUrl('assignments'), [
                'assignment_number' => $assignmentNumber,
                'name' => 'إسناد إنشاء متكامل',
                'issuing_entity' => 'جهة اختبار',
                'city' => 'الرياض',
                'district' => 'الياسمين',
                'category' => 'lands',
                'status' => 'active',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonth()->toDateString(),
                'notes' => 'وصف الإسناد التجريبي',
                'property_ids' => [$property->id],
                'assignment_image' => UploadedFile::fake()->image('assignment.jpg'),
                'assignment_letter' => UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf'),
                'general_documents' => [UploadedFile::fake()->create('general.docx', 100)],
                'additional_attachments' => [UploadedFile::fake()->create('extra.zip', 100)],
            ])
            ->assertRedirect($this->tenantUrl('assignments'));

        $assignment = Assignment::where('assignment_number', $assignmentNumber)->firstOrFail();
        $this->assertSame($assignment->id, $property->fresh()->assignment_id);
        $this->assertSame('assignment', $assignment->attachments()->firstOrFail()->attachable_type);
        $this->assertEqualsCanonicalizing(
            ['assignment_image', 'assignment_letter', 'general_document', 'additional_attachment'],
            $assignment->attachments()->pluck('category')->all(),
        );
        $assignment->attachments->each(fn ($attachment) => Storage::disk('tenant_public')->assertExists($attachment->file_path));

        $imagePath = $assignment->assignmentImage()->firstOrFail()->file_path;
        $imageUrl = tasset($imagePath);
        $this->assertStringEndsWith('/'.$imagePath, $imageUrl);
        $this->assertStringNotContainsString('/test/public/test/', $imageUrl);

        $tableResponse = $this->actingAs($this->owner, 'tenant')
            ->getJson($this->tenantUrl('assignments'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();
        $assignmentRow = collect($tableResponse->json('data'))
            ->first(fn (array $row): bool => str_contains($row['assignment_number'], $assignmentNumber));

        $this->assertNotNull($assignmentRow);
        $this->assertStringContainsString($imageUrl, $assignmentRow['image']);
        $this->assertStringContainsString('نشط', $assignmentRow['status']);
        $this->assertStringNotContainsString('js-table-status-toggle', $assignmentRow['status']);
    }

    public function test_assignment_creation_allows_omitting_district(): void
    {
        Storage::fake('tenant_public');
        $property = $this->createUnassignedProperty('OPTIONAL-DISTRICT');
        $assignmentNumber = 'OPTIONAL-DISTRICT-'.strtoupper(uniqid());

        $this->actingAs($this->owner, 'tenant')
            ->post($this->tenantUrl('assignments'), [
                'assignment_number' => $assignmentNumber,
                'name' => 'Assignment without district',
                'issuing_entity' => 'Test authority',
                'city' => 'Riyadh',
                'category' => 'lands',
                'status' => 'active',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonth()->toDateString(),
                'notes' => 'District is intentionally omitted.',
                'property_ids' => [$property->id],
                'assignment_image' => UploadedFile::fake()->image('assignment.jpg'),
                'assignment_letter' => UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf'),
                'general_documents' => [UploadedFile::fake()->create('general.pdf', 100, 'application/pdf')],
                'additional_attachments' => [UploadedFile::fake()->create('extra.pdf', 100, 'application/pdf')],
            ])
            ->assertRedirect($this->tenantUrl('assignments'));

        $this->assertDatabaseHas('assignments', [
            'assignment_number' => $assignmentNumber,
            'district' => null,
        ]);
    }

    public function test_assignment_index_uses_the_requested_select_filter_order(): void
    {
        $content = $this->actingAs($this->owner, 'tenant')
            ->get($this->tenantUrl('assignments'))
            ->assertOk()
            ->getContent();

        $filterIds = ['filter-category', 'filter-status', 'filter-start-date', 'filter-end-date', 'filter-city'];
        $positions = array_map(fn (string $id): int|false => strpos($content, 'id="'.$id.'"'), $filterIds);

        $this->assertNotContains(false, $positions);
        $this->assertSame($positions, array_values(array_unique($positions)));
        $this->assertSame($positions, collect($positions)->sort()->values()->all());
        $this->assertStringContainsString('تصنيف الإسناد', $content);
    }

    public function test_assignment_table_filters_by_exact_start_and_end_dates(): void
    {
        $match = Assignment::create([
            'assignment_number' => 'DATE-MATCH-'.strtoupper(uniqid()),
            'name' => 'Exact date match',
            'issuing_entity' => 'Test authority',
            'category' => 'lands',
            'status' => 'active',
            'start_date' => '2031-01-10',
            'end_date' => '2031-02-10',
            'is_active' => true,
            'created_by' => $this->owner->id,
        ]);
        $other = Assignment::create([
            'assignment_number' => 'DATE-OTHER-'.strtoupper(uniqid()),
            'name' => 'Different date',
            'issuing_entity' => 'Test authority',
            'category' => 'lands',
            'status' => 'active',
            'start_date' => '2031-01-11',
            'end_date' => '2031-02-11',
            'is_active' => true,
            'created_by' => $this->owner->id,
        ]);

        $rows = $this->actingAs($this->owner, 'tenant')
            ->getJson($this->tenantUrl('assignments?start_date=2031-01-10&end_date=2031-02-10'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->json('data');
        $assignmentNumbers = collect($rows)->pluck('assignment_number')->implode(' ');

        $this->assertStringContainsString($match->assignment_number, $assignmentNumbers);
        $this->assertStringNotContainsString($other->assignment_number, $assignmentNumbers);
    }

    public function test_assignment_create_view_has_property_and_attachment_summary_contracts(): void
    {
        $response = $this->actingAs($this->owner, 'tenant')
            ->get($this->tenantUrl('assignments/create'))
            ->assertOk();

        $response->assertSee('data-property-image-fallback', false);
        $response->assertSee('general-documents-count', false);
        $response->assertSee('additional-attachments-count', false);
        $response->assertSee('review-general-documents', false);
        $response->assertSee('review-additional-attachments', false);
        $response->assertSee('لم يتم إرفاق ملفات بعد.', false);
    }

    public function test_assignment_creation_rejects_a_property_already_linked_to_an_assignment(): void
    {
        [$existingAssignment] = $this->assignments();
        $property = Property::create([
            'assignment_id' => $existingAssignment->id,
            'property_number' => 'PROP-'.uniqid(),
            'name' => 'عقار مرتبط مسبقًا',
            'type' => 'villa',
            'city' => 'الرياض',
            'area' => 400,
            'data_status' => 'completed',
            'status' => 'available',
            'is_active' => true,
            'created_by' => $this->owner->id,
        ]);

        $this->actingAs($this->owner, 'tenant')
            ->postJson($this->tenantUrl('wizard-drafts/validate-step'), [
                'target_path' => '/assignments',
                'target_method' => 'POST',
                'fields' => ['property_ids'],
                'property_ids' => [$property->id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('property_ids.0');
    }

    public function test_property_assignment_auction_flow_links_only_the_selected_assignment_properties(): void
    {
        Storage::fake('tenant_public');
        $firstProperty = $this->createUnassignedProperty('FLOW-A');
        $secondProperty = $this->createUnassignedProperty('FLOW-B');
        $assignmentNumber = 'FLOW-'.strtoupper(uniqid());

        $this->actingAs($this->owner, 'tenant')
            ->post($this->tenantUrl('assignments'), [
                'assignment_number' => $assignmentNumber,
                'name' => 'Lifecycle assignment',
                'issuing_entity' => 'Lifecycle authority',
                'city' => 'Riyadh',
                'district' => 'North',
                'category' => 'lands',
                'status' => 'active',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonth()->toDateString(),
                'notes' => 'End-to-end lifecycle verification.',
                'property_ids' => [$firstProperty->id, $secondProperty->id],
                'assignment_image' => UploadedFile::fake()->image('assignment.jpg'),
                'assignment_letter' => UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf'),
                'general_documents' => [UploadedFile::fake()->create('general.pdf', 100, 'application/pdf')],
                'additional_attachments' => [UploadedFile::fake()->create('extra.pdf', 100, 'application/pdf')],
            ])
            ->assertRedirect($this->tenantUrl('assignments'));

        $assignment = Assignment::where('assignment_number', $assignmentNumber)->firstOrFail();
        $this->assertSame($assignment->id, $firstProperty->fresh()->assignment_id);
        $this->assertSame($assignment->id, $secondProperty->fresh()->assignment_id);

        $auctionName = 'Lifecycle auction '.uniqid();
        $response = $this->post($this->tenantUrl('auctions'), [
            'name' => $auctionName,
            'description' => 'Lifecycle auction description.',
            'start_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_date' => now()->addDays(2)->toDateString(),
            'end_time' => '17:00',
            'type' => 'electronic',
            'category' => 'lands',
            'city' => 'Riyadh',
            'expected_properties_count' => 1,
            'assignment_id' => $assignment->id,
            'platform_name' => 'Auction platform',
            'project_manager_name' => 'Project Manager',
            'property_ids' => [$firstProperty->id],
                'report_image' => UploadedFile::fake()->image('lifecycle-report.webp'),
        ]);

        $auction = Auction::where('name', $auctionName)->latest('id')->firstOrFail();
        $response->assertRedirect($this->tenantUrl('auctions/'.$auction->id));
        $this->assertSame($assignment->id, $auction->assignment_id);
        $this->assertSame('pending_approval', $auction->status);
        $this->assertEquals([$firstProperty->id], $auction->properties()->pluck('properties.id')->all());
        $this->assertDatabaseMissing('auction_properties', [
            'auction_id' => $auction->id,
            'property_id' => $secondProperty->id,
        ]);
    }

    public function test_property_activity_toggle_does_not_mark_the_property_as_sold(): void
    {
        [$assignment] = $this->assignments();
        $property = Property::create([
            'assignment_id' => $assignment->id,
            'property_number' => 'PROP-'.uniqid(),
            'name' => 'عقار اختبار حالة النشاط',
            'type' => 'villa',
            'city' => 'جدة',
            'area' => 250,
            'data_status' => 'completed',
            'status' => 'available',
            'is_active' => true,
            'created_by' => $this->owner->id,
        ]);

        app(PropertyService::class)->toggleStatus($property, false);

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'is_active' => false,
            'status' => 'available',
        ]);
    }

    public function test_wizard_validation_uses_the_same_assignment_property_relationship_rule(): void
    {
        [$selectedAssignment, $otherAssignment] = $this->assignments();
        $otherProperty = Property::create([
            'assignment_id' => $otherAssignment->id,
            'property_number' => 'PROP-'.uniqid(),
            'name' => 'عقار تحقق Wizard',
            'type' => 'residential_land',
            'city' => 'الرياض',
            'area' => 100,
            'data_status' => 'completed',
            'status' => 'available',
            'is_active' => true,
            'created_by' => $this->owner->id,
        ]);

        $this->actingAs($this->owner, 'tenant')
            ->postJson($this->tenantUrl('wizard-drafts/validate-step'), [
                'target_path' => '/auctions',
                'target_method' => 'POST',
                'fields' => ['assignment_id', 'property_ids'],
                'assignment_id' => $selectedAssignment->id,
                'property_ids' => [$otherProperty->id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('property_ids.0');

        $this->actingAs($this->owner, 'tenant')
            ->postJson($this->tenantUrl('wizard-drafts/validate-step'), [
                'target_path' => "/properties/{$otherProperty->id}",
                'target_method' => 'PUT',
                '_method' => 'PUT',
                'fields' => ['name'],
                'name' => $otherProperty->name,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->actingAs($this->owner, 'tenant')
            ->putJson($this->tenantUrl('wizard-drafts/validate-step'), [
                'target_path' => "/properties/{$otherProperty->id}",
                'target_method' => 'PUT',
                'fields' => ['name'],
                'name' => $otherProperty->name,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_tenant_auction_draft_can_be_saved_restored_and_discarded(): void
    {
        $resourceKey = 'tenant.auction-create';

        $this->actingAs($this->owner, 'tenant')
            ->postJson($this->tenantUrl('wizard-drafts'), [
                'resource_key' => $resourceKey,
                'step' => 3,
                'name' => 'Draft auction',
                'property_ids' => [11, 12],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->getJson($this->tenantUrl('wizard-drafts').'?resource_key='.urlencode($resourceKey))
            ->assertOk()
            ->assertJsonPath('step', 3)
            ->assertJsonPath('data.name', 'Draft auction')
            ->assertJsonPath('data.property_ids.0', 11);

        $this->deleteJson($this->tenantUrl('wizard-drafts').'?resource_key='.urlencode($resourceKey))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->getJson($this->tenantUrl('wizard-drafts').'?resource_key='.urlencode($resourceKey))
            ->assertOk()
            ->assertJsonPath('draft_id', null);
    }

    public function test_user_without_module_delete_permission_cannot_mass_delete_assignments(): void
    {
        [$assignment] = $this->assignments();
        $limitedUser = User::create([
            'name' => 'مستخدم اختبار محدود',
            'email' => 'limited-'.uniqid().'@levan.test',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
        ]);

        $this->actingAs($limitedUser, 'tenant')
            ->postJson($this->tenantUrl('mass-delete'), [
                'model' => 'Assignment',
                'ids' => [$assignment->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('assignments', ['id' => $assignment->id, 'deleted_at' => null]);
        $limitedUser->forceDelete();
    }

    public function test_archive_restore_accepts_patch_and_rejects_post(): void
    {
        [$assignment] = $this->assignments();
        $assignment->delete();

        $this->actingAs($this->owner, 'tenant')
            ->postJson($this->tenantUrl("archive/assignments/{$assignment->id}/restore"))
            ->assertMethodNotAllowed();

        $this->patchJson($this->tenantUrl("archive/assignments/{$assignment->id}/restore"))
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_assignment_with_related_records_cannot_be_deleted_or_force_deleted(): void
    {
        [$assignment] = $this->assignments();
        Property::create([
            'assignment_id' => $assignment->id,
            'property_number' => 'PROP-'.uniqid(),
            'name' => 'عقار يمنع حذف الإسناد',
            'type' => 'residential_land',
            'city' => 'الرياض',
            'area' => 100,
            'data_status' => 'completed',
            'status' => 'available',
            'is_active' => true,
            'created_by' => $this->owner->id,
        ]);

        $this->actingAs($this->owner, 'tenant')
            ->deleteJson($this->tenantUrl("assignments/{$assignment->id}"))
            ->assertUnprocessable();

        $assignment->delete();

        $this->deleteJson($this->tenantUrl("archive/assignments/{$assignment->id}/force-delete"))
            ->assertUnprocessable();

        $this->assertDatabaseHas('assignments', ['id' => $assignment->id]);
    }

    public function test_force_deleting_an_auction_removes_its_cover_and_attachments(): void
    {
        Storage::fake('tenant_public');
        [$assignment] = $this->assignments();
        $coverPath = 'auctions/covers/test-cover.webp';
        $attachmentPath = 'auctions/attachments/test-document.pdf';
        Storage::disk('tenant_public')->put($coverPath, 'cover');
        Storage::disk('tenant_public')->put($attachmentPath, 'attachment');

        $auction = Auction::create([
            'assignment_id' => $assignment->id,
            'name' => 'مزاد حذف نهائي',
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'type' => 'electronic',
            'category' => 'lands',
            'city' => 'الرياض',
            'expected_properties_count' => 1,
            'cover_image' => $coverPath,
            'status' => 'in_preparation',
            'is_active' => true,
            'created_by' => $this->owner->id,
        ]);
        $attachment = AuctionAttachment::create([
            'auction_id' => $auction->id,
            'file_path' => $attachmentPath,
            'file_name' => 'test-document.pdf',
            'file_size' => 10,
        ]);
        $auction->delete();

        $this->actingAs($this->owner, 'tenant')
            ->deleteJson($this->tenantUrl("archive/auctions/{$auction->id}/force-delete"))
            ->assertOk()
            ->assertJson(['success' => true]);

        Storage::disk('tenant_public')->assertMissing([$coverPath, $attachmentPath]);
        $this->assertDatabaseMissing('auctions', ['id' => $auction->id]);
        $this->assertDatabaseMissing('auction_attachments', ['id' => $attachment->id]);
    }

    public function test_force_deleting_a_property_removes_all_of_its_stored_files(): void
    {
        Storage::fake('tenant_public');
        $property = $this->createUnassignedProperty('DELETE-FILES');
        $mainImagePath = 'properties/main/test-main.webp';
        $documentPath = 'properties/attachments/test-deed.pdf';
        Storage::disk('tenant_public')->put($mainImagePath, 'image');
        Storage::disk('tenant_public')->put($documentPath, 'document');
        $property->update(['main_image' => $mainImagePath]);
        $property->attachments()->create([
            'disk' => 'tenant_public',
            'file_path' => $documentPath,
            'original_name' => 'test-deed.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'file_size' => 8,
            'category' => 'deed_document',
        ]);
        $property->delete();

        $this->actingAs($this->owner, 'tenant')
            ->deleteJson($this->tenantUrl("archive/properties/{$property->id}/force-delete"))
            ->assertOk()
            ->assertJson(['success' => true]);

        Storage::disk('tenant_public')->assertMissing([$mainImagePath, $documentPath]);
        $this->assertDatabaseMissing('properties', ['id' => $property->id]);
    }

    public function test_auction_creation_records_pivot_sort_order(): void
    {
        [$assignment] = $this->assignments();
        $prop1 = $this->createUnassignedProperty('SORT-1');
        $prop2 = $this->createUnassignedProperty('SORT-2');
        $prop1->update(['assignment_id' => $assignment->id]);
        $prop2->update(['assignment_id' => $assignment->id]);

        $auctionName = 'Sort Order Auction '.uniqid();
        $response = $this->actingAs($this->owner, 'tenant')
            ->post($this->tenantUrl('auctions'), [
                'name' => $auctionName,
                'description' => 'Sort order auction description.',
                'start_date' => now()->addDay()->toDateString(),
                'start_time' => '09:00',
                'end_date' => now()->addDays(2)->toDateString(),
                'end_time' => '17:00',
                'type' => 'electronic',
                'category' => 'lands',
                'city' => 'Riyadh',
                'expected_properties_count' => 2,
                'assignment_id' => $assignment->id,
                'platform_name' => 'Auction platform',
                'property_ids' => [$prop2->id, $prop1->id],
                'report_image' => UploadedFile::fake()->image('sort-order-report.webp'),
            ]);

        $auction = Auction::where('name', $auctionName)->latest('id')->firstOrFail();
        $response->assertRedirect($this->tenantUrl('auctions/'.$auction->id));
        $this->assertDatabaseHas('auction_properties', [
            'auction_id' => $auction->id,
            'property_id' => $prop2->id,
            'sort_order' => 1,
        ]);
        $this->assertDatabaseHas('auction_properties', [
            'auction_id' => $auction->id,
            'property_id' => $prop1->id,
            'sort_order' => 2,
        ]);
    }

    public function test_auction_update_replaces_subset_and_detaches_on_assignment_change(): void
    {
        [$assignmentA, $assignmentB] = $this->assignments();
        $propA1 = $this->createUnassignedProperty('A1');
        $propA2 = $this->createUnassignedProperty('A2');
        $propB1 = $this->createUnassignedProperty('B1');
        $propA1->update(['assignment_id' => $assignmentA->id]);
        $propA2->update(['assignment_id' => $assignmentA->id]);
        $propB1->update(['assignment_id' => $assignmentB->id]);

        $auction = Auction::create([
            'assignment_id' => $assignmentA->id,
            'name' => 'Update Auction '.uniqid(),
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'type' => 'electronic',
            'category' => 'lands',
            'city' => 'Riyadh',
            'status' => 'active',
            'is_active' => true,
            'created_by' => $this->owner->id,
        ]);
        $auction->properties()->sync([$propA1->id => ['sort_order' => 1]]);

        // 1. Update subset within same assignment
        $this->actingAs($this->owner, 'tenant')
            ->put($this->tenantUrl("auctions/{$auction->id}"), [
                'name' => $auction->name,
                'start_date' => now()->addDay()->toDateString(),
                'end_date' => now()->addDays(2)->toDateString(),
                'type' => 'electronic',
                'category' => 'lands',
                'city' => 'Riyadh',
                'assignment_id' => $assignmentA->id,
                'property_ids' => [$propA2->id],
            ])
            ->assertRedirect($this->tenantUrl("auctions/{$auction->id}"));

        $this->assertEquals([$propA2->id], $auction->fresh()->properties()->pluck('properties.id')->all());

        // 2. Change assignment to assignmentB with property from assignmentB
        $this->actingAs($this->owner, 'tenant')
            ->put($this->tenantUrl("auctions/{$auction->id}"), [
                'name' => $auction->name,
                'start_date' => now()->addDay()->toDateString(),
                'end_date' => now()->addDays(2)->toDateString(),
                'type' => 'electronic',
                'category' => 'lands',
                'city' => 'Riyadh',
                'assignment_id' => $assignmentB->id,
                'property_ids' => [$propB1->id],
            ])
            ->assertRedirect($this->tenantUrl("auctions/{$auction->id}"));

        $freshAuction = $auction->fresh();
        $this->assertSame($assignmentB->id, $freshAuction->assignment_id);
        $this->assertEquals([$propB1->id], $freshAuction->properties()->pluck('properties.id')->all());
        $this->assertDatabaseMissing('auction_properties', [
            'auction_id' => $auction->id,
            'property_id' => $propA2->id,
        ]);
    }

    public function test_property_linked_to_active_auction_cannot_be_deleted(): void
    {
        [$assignment] = $this->assignments();
        $property = $this->createUnassignedProperty('LOCKED-PROP');
        $property->update(['assignment_id' => $assignment->id]);

        $auction = Auction::create([
            'assignment_id' => $assignment->id,
            'name' => 'Active Auction '.uniqid(),
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'type' => 'electronic',
            'category' => 'lands',
            'city' => 'Riyadh',
            'status' => 'active',
            'is_active' => true,
            'created_by' => $this->owner->id,
        ]);
        $auction->properties()->sync([$property->id => ['sort_order' => 1]]);

        // Soft delete rejected
        $this->actingAs($this->owner, 'tenant')
            ->deleteJson($this->tenantUrl("properties/{$property->id}"))
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('properties', ['id' => $property->id, 'deleted_at' => null]);
    }

    public function test_auction_draft_purged_on_successful_creation(): void
    {
        [$assignment] = $this->assignments();
        $prop = $this->createUnassignedProperty('DRAFT-PURGE');
        $prop->update(['assignment_id' => $assignment->id]);

        $resourceKey = 'tenant.auction-create';
        $draft = $this->actingAs($this->owner, 'tenant')
            ->postJson($this->tenantUrl('wizard-drafts'), [
                'resource_key' => $resourceKey,
                'step' => 2,
                'name' => 'Draft To Purge',
                'assignment_id' => $assignment->id,
                'property_ids' => [$prop->id],
            ])
            ->assertOk()
            ->json();

        $this->assertDatabaseHas('wizard_drafts', ['id' => $draft['draft_id']]);

        $response = $this->actingAs($this->owner, 'tenant')
            ->post($this->tenantUrl('auctions'), [
                '_wizard_draft_id' => $draft['draft_id'],
                '_wizard_resource_key' => $resourceKey,
                'name' => 'Final Auction From Draft',
                'description' => 'Auction restored from a draft.',
                'start_date' => now()->addDay()->toDateString(),
                'start_time' => '09:00',
                'end_date' => now()->addDays(2)->toDateString(),
                'end_time' => '17:00',
                'type' => 'electronic',
                'category' => 'lands',
                'city' => 'Riyadh',
                'expected_properties_count' => 1,
                'assignment_id' => $assignment->id,
                'platform_name' => 'Auction platform',
                'property_ids' => [$prop->id],
                'report_image' => UploadedFile::fake()->image('draft-report.webp'),
            ]);

        $auction = \App\Models\Tenants\Auctions\Auction::where('name', 'Final Auction From Draft')->latest('id')->firstOrFail();
        $response->assertRedirect($this->tenantUrl('auctions/'.$auction->id));

        $this->assertDatabaseMissing('wizard_drafts', ['id' => $draft['draft_id']]);
    }

    public function test_auction_create_view_dom_contracts(): void
    {
        $response = $this->actingAs($this->owner, 'tenant')
            ->get($this->tenantUrl('auctions/create'))
            ->assertOk();

        $response->assertSee('data-form-validate', false);
        $response->assertSee('data-draft-resource-key="tenant.auction-create"', false);
        $response->assertSee('data-assignment-properties-url', false);
        $response->assertSee('step-review', false);
        $response->assertSee('assignment-review', false);
        $response->assertSee('data-edit-step', false);
    }

    /** @return array{0: Assignment, 1: Assignment} */
    private function assignments(): array
    {
        $suffix = strtoupper(uniqid());

        return [
            Assignment::create([
                'assignment_number' => "TEST-{$suffix}-A",
                'name' => 'إسناد اختبار أول',
                'issuing_entity' => 'جهة اختبار',
                'category' => 'lands',
                'status' => 'active',
                'is_active' => true,
                'created_by' => $this->owner->id,
            ]),
            Assignment::create([
                'assignment_number' => "TEST-{$suffix}-B",
                'name' => 'إسناد اختبار ثانٍ',
                'issuing_entity' => 'جهة اختبار',
                'category' => 'lands',
                'status' => 'active',
                'is_active' => true,
                'created_by' => $this->owner->id,
            ]),
        ];
    }

    private function createUnassignedProperty(string $suffix): Property
    {
        return Property::create([
            'property_number' => $suffix.'-'.uniqid(),
            'name' => 'Lifecycle property '.$suffix,
            'type' => 'residential_land',
            'city' => 'Riyadh',
            'area' => 100,
            'data_status' => 'completed',
            'status' => 'available',
            'is_active' => true,
            'created_by' => $this->owner->id,
        ]);
    }

    private function tenantUrl(string $path): string
    {
        return 'http://test.localhost/'.ltrim($path, '/');
    }
}
