<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            'email' => 'noedit' . random_int(1000, 9999) . '@levan.test',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
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

    public function test_profile_modal_save_button_submits_its_upload_form(): void
    {
        $response = $this->actingAs($this->owner, 'tenant')
            ->get($this->tenantUrl('users/profile'))->assertOk();

        $document = new \DOMDocument();
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $button = $xpath->query('//div[@id="changeProfileImageModal"]//button[@type="submit"]')->item(0);
        self::assertNotNull($button);

        $form = $button->hasAttribute('form')
            ? $document->getElementById($button->getAttribute('form'))
            : $xpath->query('ancestor::form', $button)->item(0);

        self::assertNotNull($form, 'The save button must belong to the profile image upload form.');
        self::assertSame('changeProfileImageForm', $form->getAttribute('id'));
        self::assertNotSame('ajax', $form->getAttribute('data-validation-submit'));
    }

    public function test_owner_can_upload_profile_image_and_return_to_the_updated_profile(): void
    {
        Storage::fake('tenant_public');
        $connection = $this->owner->getConnection();
        $connection->beginTransaction();

        try {
            $profileUrl = $this->tenantUrl('users/profile');
            $originalImage = $this->owner->image;
            $response = $this->actingAs($this->owner, 'tenant')->from($profileUrl)
                ->post($this->tenantUrl("users/{$this->owner->id}/image"), [
                    'profile_image' => UploadedFile::fake()->image('new-profile.jpg', 160, 160),
                ]);

            $response->assertRedirect($profileUrl)->assertSessionHas('success');
            $storedImage = $this->owner->fresh()->image;
            self::assertNotEmpty($storedImage);
            self::assertNotSame($originalImage, $storedImage);
            Storage::disk('tenant_public')->assertExists($storedImage);
            $this->actingAs($this->owner->fresh(), 'tenant');
            $this->followRedirects($response)->assertOk()
                ->assertSee(Storage::disk('tenant_public')->url($storedImage), false);
        } finally {
            $connection->rollBack();
        }
    }

    public function test_json_upload_still_returns_the_new_tenant_image_url(): void
    {
        Storage::fake('tenant_public');
        $connection = $this->owner->getConnection();
        $connection->beginTransaction();

        try {
            $response = $this->actingAs($this->owner, 'tenant')
                ->postJson($this->tenantUrl("users/{$this->owner->id}/image"), [
                    'profile_image' => UploadedFile::fake()->image('profile-api.jpg'),
                ])->assertOk()->assertJsonPath('success', true);

            $storedImage = $this->owner->fresh()->image;
            Storage::disk('tenant_public')->assertExists($storedImage);
            $response->assertJsonPath('image_url', Storage::disk('tenant_public')->url($storedImage));
        } finally {
            $connection->rollBack();
        }
    }

    private function tenantUrl(string $path): string
    {
        return 'http://test.localhost/' . ltrim($path, '/');
    }
}
