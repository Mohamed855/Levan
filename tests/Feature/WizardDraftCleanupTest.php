<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin\AdminMangment\Users\AdminUser;
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
        $this->createdEmail = 'wizard-draft-' . Str::lower(Str::random(12)) . '@gmail.com';
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
        $this->createdEmail = 'wizard-draft-' . Str::lower(Str::random(12)) . '@gmail.com';
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

    private function adminUrl(string $path): string
    {
        return 'http://localhost/admin/' . ltrim($path, '/');
    }
}
