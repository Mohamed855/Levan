<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Shared\WizardDraft;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class WizardDraftCleanupTest extends TestCase
{
    private AdminUser $admin;

    private ?string $createdEmail = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        $this->admin = AdminUser::query()->where('email', 'superadmin@gmail.com')->firstOrFail();
        $this->actingAs($this->admin, 'admin');
    }

    protected function tearDown(): void
    {
        if ($this->createdEmail) {
            AdminUser::query()->where('email', $this->createdEmail)->forceDelete();
        }

        parent::tearDown();
    }

    public function test_a_draft_is_deleted_after_its_resource_is_created_successfully(): void
    {
        $this->createdEmail = 'wizard-draft-'.Str::lower(Str::random(12)).'@gmail.com';
        $resourceKey = '/admin/users';

        $draft = $this->postJson($this->adminUrl('wizard-drafts'), [
            'resource_key' => $resourceKey,
            'step' => 1,
            'name' => 'Wizard Draft User',
            'email' => $this->createdEmail,
            'phone' => '0501234567',
        ])->assertOk()->json();

        $this->assertDatabaseHas('wizard_drafts', [
            'id' => $draft['draft_id'],
            'guard' => 'admin',
            'user_id' => $this->admin->id,
            'tenant_id' => '',
            'resource_key' => $resourceKey,
        ]);

        $this->post($this->adminUrl('users'), [
            '_wizard_draft_id' => $draft['draft_id'],
            '_wizard_resource_key' => $resourceKey,
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertRedirect($this->adminUrl('users'));

        $this->assertDatabaseMissing('wizard_drafts', ['id' => $draft['draft_id']]);
    }

    public function test_a_draft_is_retained_when_resource_creation_fails(): void
    {
        $this->createdEmail = 'wizard-draft-'.Str::lower(Str::random(12)).'@gmail.com';
        $resourceKey = '/admin/users';

        $draft = $this->postJson($this->adminUrl('wizard-drafts'), [
            'resource_key' => $resourceKey,
            'step' => 1,
            'name' => 'Wizard Draft User',
            'email' => $this->createdEmail,
            'phone' => '0501234567',
        ])->assertOk()->json();

        $this->assertDatabaseHas('wizard_drafts', [
            'id' => $draft['draft_id'],
            'guard' => 'admin',
            'user_id' => $this->admin->id,
            'tenant_id' => '',
            'resource_key' => $resourceKey,
        ]);

        // Post with mismatched passwords to trigger validation failure
        $this->post($this->adminUrl('users'), [
            '_wizard_draft_id' => $draft['draft_id'],
            '_wizard_resource_key' => $resourceKey,
            'password' => 'secure-password',
            'password_confirmation' => 'wrong-password',
        ])->assertSessionHasErrors(['password']);

        // Draft must NOT be deleted
        $this->assertDatabaseHas('wizard_drafts', ['id' => $draft['draft_id']]);
    }

    public function test_saving_the_same_draft_twice_updates_its_existing_record(): void
    {
        $resourceKey = '/admin/draft-update-'.Str::uuid();

        $firstDraft = $this->postJson($this->adminUrl('wizard-drafts'), [
            'resource_key' => $resourceKey,
            'step' => 1,
            'name' => 'البيانات الأولى',
        ])->assertOk()->json();

        $secondDraft = $this->postJson($this->adminUrl('wizard-drafts'), [
            'resource_key' => $resourceKey,
            'step' => 2,
            'name' => 'البيانات المحدّثة',
        ])->assertOk()->json();

        $this->assertSame($firstDraft['draft_id'], $secondDraft['draft_id']);
        $this->assertSame(1, WizardDraft::query()
            ->where('guard', 'admin')
            ->where('user_id', $this->admin->id)
            ->where('tenant_id', '')
            ->where('resource_key', $resourceKey)
            ->count());
        $this->assertDatabaseHas('wizard_drafts', [
            'id' => $firstDraft['draft_id'],
            'last_step' => 2,
        ]);

        WizardDraft::query()->findOrFail($firstDraft['draft_id'])->delete();
    }

    public function test_a_user_can_discard_a_saved_draft(): void
    {
        $resourceKey = '/admin/draft-discard-'.Str::uuid();
        $draft = $this->postJson($this->adminUrl('wizard-drafts'), [
            'resource_key' => $resourceKey,
            'step' => 1,
            'name' => 'مسودة للحذف',
        ])->assertOk()->json();

        $this->deleteJson($this->adminUrl('wizard-drafts').'?resource_key='.urlencode($resourceKey))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('wizard_drafts', ['id' => $draft['draft_id']]);
    }

    public function test_multiple_file_batches_are_accumulated_and_an_individual_file_can_be_removed(): void
    {
        Storage::fake('local');
        $resourceKey = '/admin/draft-files-'.Str::uuid();

        $this->post($this->adminUrl('wizard-drafts'), [
            'resource_key' => $resourceKey,
            'step' => 1,
            'general_documents' => [UploadedFile::fake()->create('first.pdf', 20, 'application/pdf')],
        ], ['Accept' => 'application/json'])->assertOk();

        $this->post($this->adminUrl('wizard-drafts'), [
            'resource_key' => $resourceKey,
            'step' => 2,
            'general_documents' => [UploadedFile::fake()->create('second.pdf', 20, 'application/pdf')],
        ], ['Accept' => 'application/json'])->assertOk();

        $draft = $this->getJson($this->adminUrl('wizard-drafts').'?resource_key='.urlencode($resourceKey))
            ->assertOk()
            ->json();

        $this->assertCount(2, $draft['files']['general_documents']);
        $first = $draft['files']['general_documents'][0];

        $this->deleteJson($this->adminUrl('wizard-drafts/file'), [
            'resource_key' => $resourceKey,
            'file_key' => $first['key'],
        ])->assertOk()->assertJsonPath('success', true);

        $remaining = WizardDraft::query()->findOrFail($draft['draft_id'])->draft_files['general_documents'];
        $this->assertCount(1, $remaining);
        WizardDraft::query()->findOrFail($draft['draft_id'])->delete();
    }

    public function test_draft_file_url_is_exposed_in_manifest_and_can_be_served_and_streamed(): void
    {
        Storage::fake('local');
        $resourceKey = '/admin/draft-avatar-'.Str::uuid();

        $this->post($this->adminUrl('wizard-drafts'), [
            'resource_key' => $resourceKey,
            'step' => 1,
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ], ['Accept' => 'application/json'])->assertOk();

        $draft = $this->getJson($this->adminUrl('wizard-drafts').'?resource_key='.urlencode($resourceKey))
            ->assertOk()
            ->json();

        $this->assertArrayHasKey('avatar', $draft['files']);
        $avatarFile = $draft['files']['avatar'];
        $this->assertArrayHasKey('url', $avatarFile);
        $this->assertNotEmpty($avatarFile['url']);

        $fileResponse = $this->get($avatarFile['url']);
        $fileResponse->assertOk();

        $draftRecord = WizardDraft::query()->findOrFail($draft['draft_id']);
        $storedPath = $draftRecord->draft_files['avatar']['path'];
        $this->assertTrue(Storage::disk('local')->exists($storedPath));

        $this->deleteJson($this->adminUrl('wizard-drafts').'?resource_key='.urlencode($resourceKey))
            ->assertOk();

        $this->assertDatabaseMissing('wizard_drafts', ['id' => $draft['draft_id']]);
        $this->assertFalse(Storage::disk('local')->exists($storedPath));
        $this->assertEmpty(Storage::disk('local')->allFiles('wizard-drafts/'.$draft['draft_id']));
    }

    public function test_draft_storage_directory_is_purged_when_resource_is_created_successfully(): void
    {
        Storage::fake('local');
        $this->createdEmail = 'wizard-draft-storage-'.Str::lower(Str::random(12)).'@gmail.com';
        $resourceKey = '/admin/users';

        $draft = $this->postJson($this->adminUrl('wizard-drafts'), [
            'resource_key' => $resourceKey,
            'step' => 1,
            'name' => 'Wizard Draft User',
            'email' => $this->createdEmail,
            'phone' => '0501234567',
            'attachment' => UploadedFile::fake()->create('attachment.pdf', 50, 'application/pdf'),
        ])->assertOk()->json();

        $draftRecord = WizardDraft::query()->findOrFail($draft['draft_id']);
        $storedPath = $draftRecord->draft_files['attachment']['path'];
        $this->assertTrue(Storage::disk('local')->exists($storedPath));

        $this->post($this->adminUrl('users'), [
            '_wizard_draft_id' => $draft['draft_id'],
            '_wizard_resource_key' => $resourceKey,
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertRedirect($this->adminUrl('users'));

        $this->assertDatabaseMissing('wizard_drafts', ['id' => $draft['draft_id']]);
        $this->assertFalse(Storage::disk('local')->exists($storedPath));
        $this->assertEmpty(Storage::disk('local')->allFiles('wizard-drafts/'.$draft['draft_id']));
    }

    private function adminUrl(string $path): string
    {
        return 'http://localhost/admin/'.ltrim($path, '/');
    }
}
