<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\EnsureAdminTwoFactorIsEnforced;
use App\Http\Requests\Tenants\Users\ChangeCurrentUserPasswordRequest;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Admin\AdminMangment\Users\UserService as AdminUserService;
use App\Services\Tenants\UserManagement\User\UserService as TenantUserService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class AccountPasswordChangeTest extends TestCase
{
    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.connections.tenant' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'activitylog.enabled' => false,
        ]);
        DB::purge('mysql');
        DB::purge('tenant');
        $this->withoutMiddleware(EnsureAdminTwoFactorIsEnforced::class);

        Schema::connection('mysql')->create('admin_users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('status');
            $table->boolean('is_super_admin')->default(false);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->admin = AdminUser::create([
            'name' => 'Account Owner',
            'email' => 'owner@example.test',
            'password' => 'OldPassword123!',
            'status' => 'active',
            'is_super_admin' => true,
        ]);
    }

    public function test_admin_password_page_has_an_authenticated_route(): void
    {
        self::assertTrue(Route::has('admin.users.password.change'));
        $this->get(route('admin.users.password.change'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_password_change_rejects_incorrect_current_password(): void
    {
        self::assertTrue(Route::has('admin.users.password.update'));
        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.users.password.update'), [
                'current_password' => 'WrongPassword123!',
                'new_password' => 'NewPassword123!',
                'new_password_confirmation' => 'NewPassword123!',
            ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

        self::assertTrue(Hash::check('OldPassword123!', $this->admin->fresh()->password));
    }

    public function test_admin_password_change_requires_matching_confirmation(): void
    {
        self::assertTrue(Route::has('admin.users.password.update'));
        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.users.password.update'), [
                'current_password' => 'OldPassword123!',
                'new_password' => 'NewPassword123!',
                'new_password_confirmation' => 'DifferentPassword123!',
            ])->assertUnprocessable()->assertJsonValidationErrors('new_password');
    }

    public function test_failed_password_change_does_not_flash_plaintext_passwords(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->from(route('admin.users.password.change'))
            ->post(route('admin.users.password.update'), [
                'current_password' => 'WrongPassword123!',
                'new_password' => 'NewPassword123!',
                'new_password_confirmation' => 'NewPassword123!',
            ])->assertRedirect(route('admin.users.password.change'))
            ->assertSessionHasErrors('current_password')
            ->assertSessionMissing('_old_input.current_password')
            ->assertSessionMissing('_old_input.new_password')
            ->assertSessionMissing('_old_input.new_password_confirmation');
    }

    public function test_admin_can_change_only_their_own_password_and_is_logged_out(): void
    {
        self::assertTrue(Route::has('admin.users.password.update'));
        $otherAdmin = AdminUser::create([
            'name' => 'Other Account', 'email' => 'other@example.test',
            'password' => 'OtherPassword123!', 'status' => 'active',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.users.password.update'), [
                'user_id' => $otherAdmin->id,
                'current_password' => 'OldPassword123!',
                'new_password' => 'NewPassword123!',
                'new_password_confirmation' => 'NewPassword123!',
            ])->assertRedirect(route('admin.login'))->assertSessionHas('success');

        self::assertTrue(Hash::check('NewPassword123!', $this->admin->fresh()->password));
        self::assertTrue(Hash::check('OtherPassword123!', $otherAdmin->fresh()->password));
        $this->assertGuest('admin');
    }

    public function test_admin_password_form_uses_shared_server_validation_without_changing_password(): void
    {
        $originalPassword = $this->admin->password;
        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.wizard-drafts.validate-step'), [
                'target_path' => route('admin.users.password.update', [], false),
                'target_method' => 'POST',
                'fields' => ['current_password', 'new_password', 'new_password_confirmation'],
                'current_password' => 'OldPassword123!',
                'new_password' => 'NewPassword123!',
                'new_password_confirmation' => 'NewPassword123!',
            ])->assertOk()->assertJsonPath('success', true);

        self::assertSame($originalPassword, $this->admin->fresh()->password);
    }

    public function test_tenant_password_validation_uses_the_tenant_guard_when_both_guards_are_signed_in(): void
    {
        $tenantUser = new User;
        $tenantUser->setRawAttributes(['id' => 42, 'password' => Hash::make('TenantPassword123!')]);
        $this->actingAs($tenantUser, 'tenant')->actingAs($this->admin, 'admin');

        $rules = (new ChangeCurrentUserPasswordRequest)->rules();
        $payload = [
            'current_password' => 'TenantPassword123!',
            'new_password' => 'NewPassword123!',
            'new_password_confirmation' => 'NewPassword123!',
        ];
        self::assertTrue(Validator::make($payload, $rules)->passes());

        $payload['current_password'] = 'OldPassword123!';
        self::assertTrue(Validator::make($payload, $rules)->errors()->has('current_password'));
    }

    public function test_admin_user_edit_can_still_update_the_password(): void
    {
        $originalPassword = $this->admin->password;
        app(AdminUserService::class)->update($this->admin, [
            'name' => 'Updated Name',
            'password' => 'InjectedPassword123!',
            'password_confirmation' => 'InjectedPassword123!',
        ]);

        self::assertSame('Updated Name', $this->admin->fresh()->name);
        self::assertNotSame($originalPassword, $this->admin->fresh()->password);
        self::assertTrue(Hash::check('InjectedPassword123!', $this->admin->fresh()->password));
    }

    public function test_tenant_user_edit_can_still_update_the_password(): void
    {
        Schema::connection('tenant')->create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('password');
            $table->string('global_uid');
            $table->timestamps();
            $table->softDeletes();
        });
        $user = User::create(['name' => 'Tenant Owner', 'password' => Hash::make('OldPassword123!')]);
        $originalPassword = $user->password;

        app(TenantUserService::class)->updateUser($user, [
            'name' => 'Updated Tenant',
            'password' => 'InjectedPassword123!',
            'password_confirmation' => 'InjectedPassword123!',
        ]);

        self::assertSame('Updated Tenant', $user->fresh()->name);
        self::assertNotSame($originalPassword, $user->fresh()->password);
        self::assertTrue(Hash::check('InjectedPassword123!', $user->fresh()->password));
    }
}
