<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Actions\Admin\Clients\ResetClientUserPasswordAction;
use App\Actions\Tenants\Auth\ResetTenantPasswordAction;
use App\Http\Controllers\Tenants\Auth\NewPasswordController;
use App\Http\Middleware\EnsureAdminTwoFactorIsEnforced;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use App\Policies\Admin\Tenants\TenantPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

final class ScopedPasswordResetAndFaqAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.connections.tenant' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'activitylog.enabled' => false,
        ]);
        DB::purge('mysql');
        DB::purge('tenant');
        $this->withoutMiddleware(EnsureAdminTwoFactorIsEnforced::class);

        Schema::connection('mysql')->create('tenants', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->text('data')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        DB::connection('mysql')->table('tenants')->insert(['id' => 'tenant-a']);

        Schema::connection('mysql')->create('faqs', function (Blueprint $table): void {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_admin_without_client_edit_permission_cannot_reset_a_tenant_password(): void
    {
        $this->signInAdmin([]);
        $this->fakeTenantPasswordBoundary();

        $this->postJson(route('admin.password.store'), $this->passwordPayload())->assertForbidden();
    }

    public function test_admin_reset_requires_a_valid_tenant_and_email(): void
    {
        $this->signInAdmin(['clients.edit']);
        $this->fakeTenantPasswordBoundary();

        $this->postJson(route('admin.password.store'), [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertUnprocessable()->assertJsonValidationErrors(['tenant_id', 'email']);

        $this->postJson(route('admin.password.store'), [
            ...$this->passwordPayload(), 'tenant_id' => 'unknown-tenant',
        ])->assertUnprocessable()->assertJsonValidationErrors('tenant_id');
    }

    public function test_admin_reset_enforces_the_selected_tenant_record_policy(): void
    {
        $this->signInAdmin(['clients.edit']);
        $this->fakeTenantPasswordBoundary();
        Gate::policy(Tenant::class, DenyTenantPasswordResetPolicy::class);

        $this->postJson(route('admin.password.store'), $this->passwordPayload())->assertForbidden();
    }

    public function test_admin_reset_rejects_an_archived_tenant(): void
    {
        $this->signInAdmin(['clients.edit']);
        $this->fakeTenantPasswordBoundary();
        DB::connection('mysql')->table('tenants')->where('id', 'tenant-a')->update(['deleted_at' => now()]);

        $this->postJson(route('admin.password.store'), $this->passwordPayload())
            ->assertUnprocessable()->assertJsonValidationErrors('tenant_id');
    }

    public function test_password_reset_action_does_not_rely_only_on_request_authorization(): void
    {
        $this->signInAdmin([]);
        $this->fakeTenantPasswordBoundary();
        $this->expectException(AuthorizationException::class);

        $this->app->make(ResetClientUserPasswordAction::class)->execute(
            auth('admin')->user(), 'tenant-a', 'member@example.com', 'NewPassword123!',
        );
    }

    public function test_missing_tenant_user_returns_a_field_error_to_ajax(): void
    {
        $this->signInAdmin(['clients.edit']);
        $this->fakeTenantPasswordBoundary(false);

        $this->postJson(route('admin.password.store'), $this->passwordPayload())
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_authorized_client_editor_can_reset_and_returns_to_the_admin_page(): void
    {
        $this->signInAdmin(['clients.edit']);
        $this->fakeTenantPasswordBoundary();

        $this->from('/superadmin/clients/tenant-a')
            ->post(route('admin.password.store'), $this->passwordPayload())
            ->assertRedirect('/superadmin/clients/tenant-a')
            ->assertSessionHas('success');
    }

    public function test_legacy_company_edit_permission_can_use_the_same_scoped_reset(): void
    {
        $this->signInAdmin(['companies.edit']);
        $this->fakeTenantPasswordBoundary();

        $this->from('/superadmin/companies/tenant-a')
            ->post(route('admin.password.store'), $this->passwordPayload())
            ->assertRedirect('/superadmin/companies/tenant-a')
            ->assertSessionHas('success');
    }

    public function test_faq_create_permission_saves_without_edit_or_view_permission(): void
    {
        $this->signInAdmin(['faqs.create']);

        $this->post(route('admin.faqs.store'), [
            'question' => 'كيف أضيف مزادًا؟', 'answer' => 'من إدارة المزادات.', 'is_active' => '1',
        ])->assertRedirect(route('admin.faqs.index'));

        $this->assertDatabaseHas('faqs', ['question' => 'كيف أضيف مزادًا؟', 'is_active' => 1], 'mysql');
    }

    public function test_faq_editor_cannot_create_without_create_permission(): void
    {
        $this->signInAdmin(['faqs.view', 'faqs.edit']);

        $this->postJson(route('admin.faqs.store'), ['question' => 'Question', 'answer' => 'Answer'])
            ->assertForbidden();
        $this->assertDatabaseCount('faqs', 0, 'mysql');
    }

    public function test_faq_edit_permission_updates_without_create_or_view_permission(): void
    {
        $id = DB::connection('mysql')->table('faqs')->insertGetId([
            'question' => 'Original', 'answer' => 'Answer', 'is_active' => true,
        ]);
        $this->signInAdmin(['faqs.edit']);

        $this->put(route('admin.faqs.update', $id), ['question' => 'Updated', 'answer' => 'New answer'])
            ->assertRedirect(route('admin.faqs.index'));

        $this->assertDatabaseHas('faqs', ['id' => $id, 'question' => 'Updated'], 'mysql');
    }

    public function test_faq_create_still_returns_field_errors_for_invalid_input(): void
    {
        $this->signInAdmin(['faqs.create']);

        $this->postJson(route('admin.faqs.store'), [])
            ->assertUnprocessable()->assertJsonValidationErrors(['question', 'answer']);
        $this->assertDatabaseCount('faqs', 0, 'mysql');
    }

    public function test_verified_tenant_otp_reset_keeps_using_the_verified_email(): void
    {
        Schema::connection('tenant')->create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->string('remember_token')->nullable();
            $table->boolean('must_change_password')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        $oldHash = Hash::make('OriginalPassword123!');
        DB::connection('tenant')->table('users')->insert([
            ['name' => 'Verified', 'email' => 'verified@example.com', 'password' => $oldHash],
            ['name' => 'Other', 'email' => 'other@example.com', 'password' => $oldHash],
        ]);
        Route::middleware('web')->post('/testing/verified-password-reset', [NewPasswordController::class, 'store']);

        $this->withSession(['password_reset_verification' => [
            'verified' => true, 'email' => 'verified@example.com', 'expires_at' => now()->addMinutes(5)->timestamp,
        ]])->post('/testing/verified-password-reset', [
            'email' => 'other@example.com', 'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertSessionHas('status')->assertSessionMissing('password_reset_verification');

        $this->assertTrue(Hash::check('NewPassword123!', DB::connection('tenant')->table('users')->where('email', 'verified@example.com')->value('password')));
        $this->assertSame($oldHash, DB::connection('tenant')->table('users')->where('email', 'other@example.com')->value('password'));
    }

    private function signInAdmin(array $permissions): void
    {
        $admin = Mockery::mock(AdminUser::class)->makePartial();
        $admin->setRawAttributes(['id' => 1, 'status' => 'active']);
        $admin->shouldReceive('isSuperAdmin')->andReturnFalse();
        $admin->shouldReceive('checkPermissionTo')->andReturnFalse();
        $admin->shouldReceive('can')->andReturnUsing(fn ($ability): bool => in_array($ability, $permissions, true));
        $this->actingAs($admin, 'admin');
    }

    private function fakeTenantPasswordBoundary(bool $userExists = true): void
    {
        $boundary = Mockery::mock(ResetTenantPasswordAction::class);
        $boundary->shouldReceive('executeForTenant')->andReturn($userExists ? new User() : null);
        $this->app->instance(ResetTenantPasswordAction::class, $boundary);
    }

    private function passwordPayload(): array
    {
        return [
            'tenant_id' => 'tenant-a', 'email' => 'member@example.com',
            'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!',
        ];
    }
}

final class DenyTenantPasswordResetPolicy extends TenantPolicy
{
    public function update(AdminUser $user, Tenant $tenant): bool
    {
        return false;
    }
}
