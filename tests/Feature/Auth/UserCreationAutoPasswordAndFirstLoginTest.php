<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Http\Middleware\EnsureAdminTwoFactorIsEnforced;
use App\Mail\Auth\UserWelcomeMail;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class UserCreationAutoPasswordAndFirstLoginTest extends TestCase
{
    private AdminUser $creator;

    private Role $role;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'activitylog.enabled' => false,
            'dashboard-notifications.enabled' => false,
        ]);

        DB::purge('mysql');
        Cache::forget('app_settings');
        $this->withoutVite();
        $this->withoutMiddleware(EnsureAdminTwoFactorIsEnforced::class);
        Mail::fake();

        Schema::connection('mysql')->create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('image')->nullable();
            $table->string('office_name')->nullable();
            $table->string('logo_text')->nullable();
            $table->timestamps();
        });

        Schema::connection('mysql')->create('admin_users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->string('status')->default('active');
            $table->string('image')->nullable();
            $table->boolean('is_super_admin')->default(false);
            $table->boolean('must_change_password')->default(false);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql')->create('roles', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::connection('mysql')->create('permissions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->string('section')->nullable();
            $table->timestamps();
        });

        Schema::connection('mysql')->create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::connection('mysql')->create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::connection('mysql')->create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::connection('mysql')->create('wizard_drafts', function (Blueprint $table): void {
            $table->id();
            $table->string('resource_key');
            $table->string('guard_name')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('draft_data')->nullable();
            $table->json('draft_files')->nullable();
            $table->unsignedInteger('last_step')->default(1);
            $table->timestamps();
        });

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = collect(['users.view', 'users.create', 'users.edit', 'dashboard.view'])
            ->map(fn (string $name) => Permission::on('mysql')->create(['name' => $name, 'guard_name' => 'admin']));

        $this->role = Role::on('mysql')->create(['name' => 'Admin', 'guard_name' => 'admin']);
        $this->role->syncPermissions($permissions);

        $this->creator = AdminUser::create([
            'name' => 'Central Manager',
            'email' => 'manager@example.com',
            'phone' => '0500000001',
            'password' => Hash::make('CreatorPassword123!'),
            'status' => 'active',
            'is_super_admin' => true,
        ]);
        $this->creator->assignRole($this->role);
    }

    public function test_superadmin_user_create_and_edit_forms_exclude_password_inputs(): void
    {
        $target = AdminUser::create([
            'name' => 'Editable Admin',
            'email' => 'editable@example.com',
            'phone' => '0500000002',
            'password' => Hash::make('OriginalPass123!'),
            'status' => 'active',
        ]);
        $auditorRole = Role::on('mysql')->create(['name' => 'Auditor', 'guard_name' => 'admin']);
        $target->assignRole($auditorRole);

        $this->actingAs($this->creator, 'admin')
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('سيتم توليد كلمة المرور تلقائياً وإرسالها إلى البريد الإلكتروني للمستخدم في الرسالة الترحيبية.')
            ->assertDontSee('name="password"', false)
            ->assertDontSee('name="password_confirmation"', false);

        $this->actingAs($this->creator, 'admin')
            ->get(route('admin.users.edit', $target))
            ->assertOk()
            ->assertDontSee('name="password"', false)
            ->assertDontSee('name="password_confirmation"', false);
    }

    public function test_created_superadmin_receives_generated_password_and_is_forced_to_change_on_first_login(): void
    {
        $this->actingAs($this->creator, 'admin')
            ->post(route('admin.users.store'), [
                'name' => 'New Admin Member',
                'email' => 'new-admin@example.com',
                'phone' => '0551234567',
                'role_id' => $this->role->id,
            ])
            ->assertRedirect(route('admin.users.index'));

        $created = AdminUser::where('email', 'new-admin@example.com')->firstOrFail();
        $this->assertTrue((bool) $created->must_change_password);

        $temporaryPassword = null;
        Mail::assertQueued(UserWelcomeMail::class, function (UserWelcomeMail $mail) use (&$temporaryPassword): bool {
            if (! $mail->hasTo('new-admin@example.com')) {
                return false;
            }
            $temporaryPassword = $mail->temporaryPassword;

            return ! empty($temporaryPassword)
                && $mail->connection === 'sync'
                && $mail->loginUrl === route('admin.login')
                && $mail->roleName === 'مدير النظام';
        });

        $this->assertNotNull($temporaryPassword);
        $this->assertTrue(Hash::check($temporaryPassword, $created->password));

        // Log out creator and log in as the newly created admin with the emailed temporary password
        Auth::guard('admin')->logout();

        $this->post(route('admin.login'), [
            'email' => 'new-admin@example.com',
            'password' => $temporaryPassword,
        ])->assertRedirect('/superadmin');

        $this->assertAuthenticatedAs($created, 'admin');

        // Any superadmin route must redirect to the mandatory password change screen
        $this->get('/superadmin')
            ->assertRedirect(route('admin.users.password.change'));

        $this->get(route('admin.users.password.change'))
            ->assertOk()
            ->assertSee('initial-password-modal', false)
            ->assertSee('data-bs-backdrop="static"', false);

        // Changing the temporary password logs the user out and clears must_change_password
        $newPassword = 'FinalAdminPassword123!';
        $this->post(route('admin.users.password.update'), [
            'current_password' => $temporaryPassword,
            'new_password' => $newPassword,
            'new_password_confirmation' => $newPassword,
        ])->assertRedirect(route('admin.login'));

        $this->assertGuest('admin');
        $this->assertFalse((bool) $created->fresh()->must_change_password);

        // Logging in with the new password grants dashboard access without forced redirect
        $this->post(route('admin.login'), [
            'email' => 'new-admin@example.com',
            'password' => $newPassword,
        ])->assertRedirect('/superadmin');

        $this->get('/superadmin/users/create')->assertOk();
    }
}
