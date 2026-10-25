<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TenantProfileUploadFeatureTest extends TestCase
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

    public function test_user_without_edit_permission_cannot_update_another_users_profile_image(): void
    {
        $otherUser = User::create([
            'name' => 'مستخدم صلاحيات محدود',
            'employee_number' => 'NOEDIT' . random_int(1000, 9999),
            'email' => 'noedit' . random_int(1000, 9999) . '@levan.test',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
            'license_seat_id' => $this->owner->license_seat_id,
        ]);

        $this->actingAs($otherUser, 'tenant')
            ->postJson($this->tenantUrl("users/{$this->owner->id}/image"), [
                'profile_image' => UploadedFile::fake()->image('profile.jpg'),
            ])
            ->assertForbidden();

        $otherUser->forceDelete();
    }

    public function test_owner_receives_validation_error_for_non_image_profile_upload(): void
    {
        $this->actingAs($this->owner, 'tenant')
            ->postJson($this->tenantUrl("users/{$this->owner->id}/image"), [
                'profile_image' => UploadedFile::fake()->create('document.pdf', 12, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('profile_image');
    }

    private function tenantUrl(string $path): string
    {
        return 'http://test.localhost/' . ltrim($path, '/');
    }
}
