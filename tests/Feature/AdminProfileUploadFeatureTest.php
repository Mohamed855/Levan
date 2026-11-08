<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminProfileUploadFeatureTest extends TestCase
{
    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::on('mysql')->firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'admin']);
        $this->admin = AdminUser::on('mysql')->firstOrCreate(
            ['email' => 'admin_test_upload@lot.test'],
            [
                'name' => 'مدير النظام التجريبي',
                'phone' => '0512345678',
                'password' => Hash::make('Password123!'),
                'status' => 'active',
            ]
        );
        if (! $this->admin->hasRole('SuperAdmin', 'admin')) {
            $this->admin->assignRole($role);
        }
    }

    public function test_admin_user_without_edit_permission_cannot_update_another_users_profile_image(): void
    {
        $role = Role::on('mysql')->firstOrCreate(['name' => 'AuditorOnly', 'guard_name' => 'admin']);
        $otherUser = AdminUser::on('mysql')->create([
            'name' => 'مستخدم بدون صلاحية تعديل',
            'email' => 'noedit_' . uniqid() . '@lot.test',
            'phone' => '0599999999',
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);
        $otherUser->assignRole($role);

        $this->actingAs($otherUser, 'admin')
            ->postJson(route('admin.users.update-image', $this->admin->id), [
                'profile_image' => UploadedFile::fake()->image('profile.jpg'),
            ])
            ->assertForbidden();

        $otherUser->forceDelete();
    }

    public function test_admin_receives_validation_error_for_non_image_profile_upload(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.users.update-image', $this->admin->id), [
                'profile_image' => UploadedFile::fake()->create('document.pdf', 12, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('profile_image');
    }

    public function test_admin_profile_modal_save_button_submits_its_upload_form(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.profile.show'))->assertOk();

        $document = new \DOMDocument();
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $button = $xpath->query('//div[@id="changeProfileImageModal"]//button[@type="submit"]')->item(0);
        self::assertNotNull($button, 'Submit button in changeProfileImageModal must exist.');

        $form = $button->hasAttribute('form')
            ? $document->getElementById($button->getAttribute('form'))
            : $xpath->query('ancestor::form', $button)->item(0);

        self::assertNotNull($form, 'The save button must belong to the profile image upload form.');
        self::assertSame('changeProfileImageForm', $form->getAttribute('id'));
        self::assertNotSame('ajax', $form->getAttribute('data-validation-submit'));
    }

    public function test_admin_can_upload_profile_image_and_return_to_the_updated_profile(): void
    {
        Storage::fake('public');
        $originalImage = $this->admin->image;

        $profileUrl = route('admin.profile.show');
        $response = $this->actingAs($this->admin, 'admin')->from($profileUrl)
            ->post(route('admin.users.update-image', $this->admin->id), [
                'profile_image' => UploadedFile::fake()->image('new-admin-profile.jpg', 160, 160),
            ]);

        $response->assertRedirect($profileUrl)->assertSessionHas('success');
        $storedImage = $this->admin->fresh()->image;
        self::assertNotEmpty($storedImage);
        self::assertNotSame($originalImage, $storedImage);
        Storage::disk('public')->assertExists($storedImage);
    }

    public function test_admin_json_upload_returns_the_new_image_url(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.users.update-image', $this->admin->id), [
                'profile_image' => UploadedFile::fake()->image('profile-api.jpg'),
            ])->assertOk()->assertJsonPath('success', true);

        $storedImage = $this->admin->fresh()->image;
        Storage::disk('public')->assertExists($storedImage);
        $response->assertJsonPath('image_url', asset('storage/' . $storedImage));
    }

    public function test_admin_can_remove_existing_profile_image(): void
    {
        Storage::fake('public');

        $imagePath = UploadedFile::fake()->image('existing-admin-avatar.jpg')->storeAs('admin_users', 'existing-avatar.jpg', 'public');
        $this->admin->update(['image' => $imagePath]);
        Storage::disk('public')->assertExists($imagePath);

        $profileUrl = route('admin.profile.show');
        $response = $this->actingAs($this->admin, 'admin')->from($profileUrl)
            ->post(route('admin.users.update-image', $this->admin->id), [
                'remove_profile_image' => '1',
            ]);

        $response->assertRedirect($profileUrl)->assertSessionHas('success', 'تم حذف الصورة بنجاح');
        self::assertNull($this->admin->fresh()->image);
        Storage::disk('public')->assertMissing($imagePath);
    }
}

