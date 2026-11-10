<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Http\Controllers\Tenants\UserMangment\User\UserController;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class FirstLoginPasswordChangeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        config([
            'database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.connections.tenant' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'activitylog.enabled' => false,
            'dashboard-notifications.enabled' => false,
        ]);

        foreach (['mysql' => 'admin_users', 'tenant' => 'users'] as $connection => $table) {
            DB::purge($connection);
            Schema::connection($connection)->create($table, function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('status')->default('active');
                $table->boolean('is_super_admin')->default(true);
                $table->boolean('must_change_password')->default(false);
                $table->string('global_uid')->nullable();
                $table->string('current_session_id')->nullable();
                $table->timestamp('last_login_at')->nullable();
                $table->string('last_login_ip')->nullable();
                $table->text('two_factor_secret')->nullable();
                $table->timestamp('two_factor_confirmed_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Use the real tenant controller and auth middleware with isolated databases.
        Route::middleware(['web', 'auth:tenant', \App\Http\Middleware\EnsureInitialPasswordChanged::class.':tenant'])->group(function (): void {
            Route::get('/password-test/change', [UserController::class, 'showChangePasswordForm'])->name('users.password.change');
            Route::post('/password-test/change', [UserController::class, 'updatePassword'])->name('users.password.update');
            Route::get('/password-test/profile', fn () => 'profile')->name('profile.show');
            Route::get('/password-test/protected', fn () => 'protected');
            Route::post('/password-test/protected', fn () => 'updated');
            Route::post('/password-test/validate', [\App\Http\Controllers\Shared\Wizard\WizardDraftController::class, 'validateStep'])
                ->defaults('shared_form_validation_actions', [UserController::class.'@updatePassword'])
                ->name('wizard-drafts.validate-step');
        });
        Route::get('/password-test/login', fn () => 'login')->name('login');
        Route::middleware(['web', 'auth:admin', \App\Http\Middleware\EnsureInitialPasswordChanged::class.':admin'])
            ->match(['GET', 'POST'], '/password-test/admin-protected', fn () => 'protected');
        Route::getRoutes()->refreshNameLookups();
    }

    public static function guards(): array
    {
        return [['admin'], ['tenant']];
    }

    #[DataProvider('guards')]
    public function test_temporary_password_cannot_access_other_pages_or_actions(string $guard): void
    {
        $user = $this->createUser($guard);
        $this->actingAs($user, $guard);
        $url = $guard === 'admin' ? '/password-test/admin-protected' : '/password-test/protected';

        $this->get($url)->assertRedirect($this->passwordUrl($guard));
        $this->postJson($url)->assertForbidden()->assertJsonPath('redirect', $this->passwordUrl($guard));
    }

    #[DataProvider('guards')]
    public function test_required_form_is_a_static_modal_without_close_or_back_controls(string $guard): void
    {
        $this->actingAs($this->createUser($guard), $guard);

        $response = $this->get($this->passwordUrl($guard))->assertOk()
            ->assertSee('data-bs-backdrop="static"', false)
            ->assertSee('data-bs-keyboard="false"', false)
            ->assertSee('data-modal-dismissible="false"', false)
            ->assertSee('name="new_password"', false)
            ->assertDontSee('href="'.$this->profileUrl($guard).'"', false);

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $modal = $document->getElementById('initial-password-modal');
        self::assertNotNull($modal);
        self::assertStringNotContainsString('data-bs-dismiss="modal"', $document->saveHTML($modal));
    }

    #[DataProvider('guards')]
    public function test_invalid_password_keeps_the_requirement_and_session(string $guard): void
    {
        $user = $this->createUser($guard);
        $this->actingAs($user, $guard)->postJson($this->passwordUrl($guard), [
            'current_password' => 'WrongPassword123!',
            'new_password' => 'NewPassword123!',
            'new_password_confirmation' => 'NewPassword123!',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

        self::assertTrue((bool) $user->fresh()->must_change_password);
        self::assertTrue(Hash::check('TemporaryPassword123!', $user->fresh()->password));
        $this->assertAuthenticatedAs($user, $guard);
    }

    #[DataProvider('guards')]
    public function test_temporary_password_cannot_be_reused_as_the_new_password(string $guard): void
    {
        $this->actingAs($this->createUser($guard), $guard)->postJson($this->passwordUrl($guard), [
            'current_password' => 'TemporaryPassword123!',
            'new_password' => 'TemporaryPassword123!',
            'new_password_confirmation' => 'TemporaryPassword123!',
        ])->assertUnprocessable()->assertJsonValidationErrors('new_password');
    }

    #[DataProvider('guards')]
    public function test_required_password_change_ends_session_and_allows_only_the_new_password(string $guard): void
    {
        $user = $this->createUser($guard);
        $loginUrl = $guard === 'admin' ? route('admin.login') : route('login');
        $this->actingAs($user, $guard)->withSession(['sensitive_state' => 'old-session'])
            ->post($this->passwordUrl($guard), [
                'current_password' => 'TemporaryPassword123!',
                'new_password' => 'NewPassword123!',
                'new_password_confirmation' => 'NewPassword123!',
            ])->assertRedirect($loginUrl)->assertSessionHas('success')->assertSessionMissing('sensitive_state');

        $this->assertGuest($guard);
        self::assertFalse((bool) $user->fresh()->must_change_password);
        self::assertFalse(Auth::guard($guard)->validate(['email' => $user->email, 'password' => 'TemporaryPassword123!']));
        self::assertTrue(Auth::guard($guard)->attempt(['email' => $user->email, 'password' => 'NewPassword123!']));
        $this->get($guard === 'admin' ? '/password-test/admin-protected' : '/password-test/protected')->assertOk();
    }

    public function test_required_password_form_can_use_existing_server_validation(): void
    {
        $user = $this->createUser('admin');
        $originalPassword = $user->password;
        $this->actingAs($user, 'admin')->postJson(route('admin.wizard-drafts.validate-step'), [
            'target_path' => route('admin.users.password.update', [], false),
            'target_method' => 'POST',
            'fields' => ['current_password', 'new_password', 'new_password_confirmation'],
            'current_password' => 'TemporaryPassword123!',
            'new_password' => 'NewPassword123!',
            'new_password_confirmation' => 'NewPassword123!',
        ])->assertOk()->assertJsonPath('success', true);

        self::assertSame($originalPassword, $user->fresh()->password);
        self::assertTrue((bool) $user->fresh()->must_change_password);
    }

    public function test_required_user_cannot_validate_an_unrelated_form(): void
    {
        $this->actingAs($this->createUser('admin'), 'admin')
            ->postJson(route('admin.wizard-drafts.validate-step'), [
                'target_path' => route('admin.profile.update', [], false),
                'target_method' => 'PUT',
            ])->assertForbidden();
    }

    #[DataProvider('guards')]
    public function test_recording_a_login_does_not_clear_the_password_requirement(string $guard): void
    {
        $user = $this->createUser($guard);
        app(\App\Actions\Auth\PersistAuthenticationStateAction::class)->recordLogin($user, '127.0.0.1');

        self::assertTrue($user->fresh()->must_change_password);
        $this->actingAs($user, $guard)
            ->get($guard === 'admin' ? '/password-test/admin-protected' : '/password-test/protected')
            ->assertRedirect($this->passwordUrl($guard));
    }

    public function test_one_guard_does_not_apply_the_other_guards_requirement(): void
    {
        $admin = $this->createUser('admin');
        $admin->forceFill(['must_change_password' => false])->save();
        $this->actingAs($this->createUser('tenant'), 'tenant')->actingAs($admin, 'admin');

        $this->get('/password-test/admin-protected')->assertOk();
        $this->get('/password-test/protected')->assertRedirect($this->passwordUrl('tenant'));
    }

    public function test_tenant_password_routes_are_available_before_subscription_checks(): void
    {
        foreach (['path.users.password.change', 'path.users.password.update'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            self::assertNotNull($route);
            self::assertNotContains('tenant.subscription', $route->gatherMiddleware());
            self::assertContains(\App\Http\Middleware\EnsureInitialPasswordChanged::class.':tenant', $route->gatherMiddleware());
        }
    }

    #[DataProvider('guards')]
    public function test_migration_preserves_existing_accounts_without_forcing_password_changes(string $guard): void
    {
        config(['database.connections.password_migrations' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::setDefaultConnection('password_migrations');
        $tableName = $guard === 'admin' ? 'admin_users' : 'users';
        Schema::create($tableName, function (Blueprint $table): void {
            $table->id();
            $table->string('password');
        });
        DB::table($tableName)->insert(['password' => 'existing-hash']);
        $file = $guard === 'admin'
            ? 'admin/2026_11_10_000001_add_must_change_password_to_admin_users.php'
            : 'tenant/2026_11_10_000001_add_must_change_password_to_users.php';
        $migration = require database_path('migrations/'.$file);
        $migration->up();

        self::assertSame('existing-hash', DB::table($tableName)->value('password'));
        self::assertFalse((bool) DB::table($tableName)->value('must_change_password'));
    }

    private function createUser(string $guard): AdminUser|User
    {
        $class = $guard === 'admin' ? AdminUser::class : User::class;

        $user = $class::create([
            'name' => 'First Login', 'email' => 'first@example.test',
            'password' => Hash::make('TemporaryPassword123!'), 'status' => 'active',
            'is_super_admin' => true,
        ]);
        $user->forceFill(['must_change_password' => true])->save();

        return $user->refresh();
    }

    private function passwordUrl(string $guard): string
    {
        return route($guard === 'admin' ? 'admin.users.password.change' : 'users.password.change');
    }

    private function profileUrl(string $guard): string
    {
        return route($guard === 'admin' ? 'admin.profile.show' : 'profile.show');
    }
}
