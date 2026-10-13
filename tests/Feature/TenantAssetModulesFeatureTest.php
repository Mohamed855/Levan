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
        $this->post($this->tenantUrl('auctions'), [
            'name' => $auctionName,
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'type' => 'electronic',
            'category' => 'lands',
            'city' => 'Riyadh',
            'assignment_id' => $assignment->id,
            'property_ids' => [$firstProperty->id],
            'status' => 'draft',
        ])->assertRedirect($this->tenantUrl('auctions'));

        $auction = Auction::where('name', $auctionName)->firstOrFail();
        $this->assertSame($assignment->id, $auction->assignment_id);
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
            'employee_number' => 'LIMITED-'.uniqid(),
            'email' => 'limited-'.uniqid().'@levan.test',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
            'license_seat_id' => $this->owner->license_seat_id,
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
            'status' => 'draft',
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
