<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Assignments;

use App\Actions\Tenants\Assignments\CreateAssignmentAction;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssignmentCreationTest extends TestCase
{
    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::findOrFail('test');
        tenancy()->initialize($this->tenant);
        $this->user = User::where('email', 'demo@levan.com')->firstOrFail();
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_assignment_creation_without_optional_additional_attachments_succeeds(): void
    {
        Storage::fake('tenant_public');

        $property = Property::create([
            'assignment_id' => null,
            'property_number' => 'PROP-'.uniqid(),
            'name' => 'عقار تجريبي للإسناد بدون مرفقات إضافية',
            'type' => 'residential_land',
            'city' => 'الرياض',
            'district' => 'الياسمين',
            'area' => 500,
            'data_status' => 'completed',
            'status' => 'available',
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $assignmentNumber = 'TEST-NO-EXTRA-'.strtoupper(uniqid());

        $response = $this->actingAs($this->user, 'tenant')
            ->post('http://test.localhost/assignments', [
                'assignment_number' => $assignmentNumber,
                'name' => 'إسناد بدون مرفقات إضافية',
                'issuing_entity' => 'محكمة التنفيذ بالرياض',
                'city' => 'الرياض',
                'district' => 'الياسمين',
                'category' => 'lands',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonth()->toDateString(),
                'notes' => 'وصف الإسناد التجريبي بدون مرفقات إضافية',
                'property_ids' => [$property->id],
                'assignment_image' => UploadedFile::fake()->image('assignment.jpg'),
                'assignment_letter' => UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf'),
                'general_documents' => [UploadedFile::fake()->create('general.docx', 100)],
                // Notice: additional_attachments is intentionally omitted
            ]);

        $response->assertRedirect('http://test.localhost/assignments');
        $response->assertSessionHas('success', 'تم إضافة الإسناد بنجاح');

        $assignment = Assignment::where('assignment_number', $assignmentNumber)->firstOrFail();
        $this->assertSame($assignment->id, $property->fresh()->assignment_id);
        $this->assertCount(3, $assignment->attachments);

        $categories = $assignment->attachments->pluck('category')->all();
        $this->assertContains('assignment_image', $categories);
        $this->assertContains('assignment_letter', $categories);
        $this->assertContains('general_document', $categories);
        $this->assertNotContains('additional_attachment', $categories);
    }

    public function test_assignment_creation_with_additional_attachments_succeeds(): void
    {
        Storage::fake('tenant_public');

        $property = Property::create([
            'assignment_id' => null,
            'property_number' => 'PROP-'.uniqid(),
            'name' => 'عقار تجريبي مع مرفقات إضافية',
            'type' => 'residential_land',
            'city' => 'الرياض',
            'district' => 'الياسمين',
            'area' => 500,
            'data_status' => 'completed',
            'status' => 'available',
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $assignmentNumber = 'TEST-WITH-EXTRA-'.strtoupper(uniqid());

        $response = $this->actingAs($this->user, 'tenant')
            ->post('http://test.localhost/assignments', [
                'assignment_number' => $assignmentNumber,
                'name' => 'إسناد مع مرفقات إضافية',
                'issuing_entity' => 'محكمة التنفيذ بالرياض',
                'city' => 'الرياض',
                'district' => 'الياسمين',
                'category' => 'lands',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonth()->toDateString(),
                'notes' => 'وصف الإسناد التجريبي مع مرفقات إضافية',
                'property_ids' => [$property->id],
                'assignment_image' => UploadedFile::fake()->image('assignment.jpg'),
                'assignment_letter' => UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf'),
                'general_documents' => [UploadedFile::fake()->create('general.docx', 100)],
                'additional_attachments' => [UploadedFile::fake()->create('extra.pdf', 100, 'application/pdf')],
            ]);

        $response->assertRedirect('http://test.localhost/assignments');
        $response->assertSessionHas('success', 'تم إضافة الإسناد بنجاح');

        $assignment = Assignment::where('assignment_number', $assignmentNumber)->firstOrFail();
        $this->assertSame($assignment->id, $property->fresh()->assignment_id);
        $this->assertCount(4, $assignment->attachments);
    }

    public function test_create_assignment_action_directly_handles_missing_attachments_safely(): void
    {
        Storage::fake('tenant_public');

        $property = Property::create([
            'assignment_id' => null,
            'property_number' => 'PROP-'.uniqid(),
            'name' => 'عقار تجريبي للأكشن المباشر',
            'type' => 'residential_land',
            'city' => 'الرياض',
            'district' => 'الياسمين',
            'area' => 500,
            'data_status' => 'completed',
            'status' => 'available',
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $action = app(CreateAssignmentAction::class);

        $assignmentNumber = 'ACTION-TEST-'.strtoupper(uniqid());

        $data = [
            'assignment_number' => $assignmentNumber,
            'name' => 'إسناد عبر الأكشن',
            'issuing_entity' => 'جهة اختبار',
            'city' => 'الرياض',
            'category' => 'lands',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'notes' => 'وصف اختباري للأكشن',
            'property_ids' => [$property->id],
            'assignment_image' => UploadedFile::fake()->image('assignment.jpg'),
            'assignment_letter' => UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf'),
            'general_documents' => [UploadedFile::fake()->create('general.docx', 100)],
            // additional_attachments key does not exist at all in $data
        ];

        $assignment = $action->execute($data, $this->user->id);

        $this->assertInstanceOf(Assignment::class, $assignment);
        $this->assertSame($assignmentNumber, $assignment->assignment_number);
        $this->assertSame($assignment->id, $property->fresh()->assignment_id);
    }
}

