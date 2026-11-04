<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Independent review probes; intentionally outside the regular suite. */
final class CodexBatchARoundTwoReviewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.connections.tenant' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'tenancy.bootstrappers' => [],
            'activitylog.enabled' => false,
        ]);
        DB::purge('mysql');
        DB::purge('tenant');
        $this->withoutVite();

        foreach (['mysql' => 'admin_users', 'tenant' => 'users'] as $connection => $tableName) {
            Schema::connection($connection)->create($tableName, function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('status')->default('active');
                $table->boolean('is_super_admin')->default(false);
                $table->text('two_factor_secret')->nullable();
                $table->text('two_factor_recovery_codes')->nullable();
                $table->timestamp('two_factor_confirmed_at')->nullable();
                $table->timestamp('last_login_at')->nullable();
                $table->string('last_login_ip')->nullable();
                $table->string('current_session_id')->nullable();
                $table->string('global_uid')->nullable();
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes();
            });
        }
        Schema::connection('mysql')->create('tenants', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('db_database')->default(':memory:');
            $table->string('db_username')->default('');
            $table->string('db_password')->default('');
            $table->string('company_name')->default('Review fixture');
            $table->string('commercial_number')->default('1234567890');
            $table->string('access_type')->default('path');
            $table->json('data')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        parent::tearDown();
    }

    private function adminFixture(): AdminUser
    {
        $admin = AdminUser::create([
            'name' => 'Independent review', 'email' => 'review@example.test',
            'password' => 'SyntheticReview123!', 'status' => 'active',
        ]);
        $admin->forceFill([
            'two_factor_secret' => encrypt('synthetic-unused-secret'),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode(['synthetic-review-recovery'])),
        ])->save();

        return $admin;
    }

    public function test_admin_totp_cannot_authenticate_a_second_pending_session(): void
    {
        $engine = app(\PragmaRX\Google2FA\Google2FA::class);
        $secret = $engine->generateSecretKey();
        $admin = $this->adminFixture();
        $admin->forceFill(['two_factor_secret' => encrypt($secret)])->save();
        $code = $engine->getCurrentOtp($secret);
        $pending = ['login.id' => $admin->id, 'login.guard' => 'admin'];
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        $this->withSession($pending)->post('/superadmin/two-factor-challenge', ['code' => $code])
            ->assertRedirect('/superadmin');
        self::assertTrue(Auth::guard('admin')->check());
        Auth::guard('admin')->logout();

        $this->withSession($pending)->post('/superadmin/two-factor-challenge', ['code' => $code]);
        self::assertFalse(Auth::guard('admin')->check(), 'A previously accepted TOTP must not create another login within the same time step.');
    }

    public function test_tenant_totp_cannot_authenticate_a_second_pending_session(): void
    {
        tenancy()->initialize(Tenant::create(['id' => 'review-replay']));
        $engine = app(\PragmaRX\Google2FA\Google2FA::class);
        $secret = $engine->generateSecretKey();
        $user = User::create([
            'name' => 'Synthetic replay fixture', 'email' => 'replay@example.test',
            'password' => 'SyntheticReview123!', 'status' => 'active',
        ]);
        $user->forceFill([
            'two_factor_secret' => encrypt($secret), 'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode(['synthetic-recovery'])),
        ])->save();
        $code = $engine->getCurrentOtp($secret);
        $pending = ['login.id' => $user->id, 'login.guard' => 'tenant', 'login.tenant_id' => 'review-replay'];
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        $this->withSession($pending)->post('/review-replay/two-factor-challenge', ['code' => $code])
            ->assertRedirect(tenant_route_name('dashboard'));
        self::assertTrue(Auth::guard('tenant')->check());
        Auth::guard('tenant')->logout();

        $this->withSession($pending)->post('/review-replay/two-factor-challenge', ['code' => $code]);
        self::assertFalse(Auth::guard('tenant')->check(), 'A previously accepted TOTP must not create another tenant login.');
    }

    public function test_recovery_code_consumption_rejects_a_second_preloaded_snapshot(): void
    {
        $admin = $this->adminFixture();
        // Models represent the interleaving: request A reads, request B reads, A consumes, B consumes.
        $requestASnapshot = $admin->fresh();
        $requestBSnapshot = $admin->fresh();
        $resolver = app(\App\Actions\Auth\ResolveTwoFactorLogin::class);
        $consume = new \ReflectionMethod($resolver, 'consumeRecoveryCode');
        $consume->invoke($resolver, $requestASnapshot, 'synthetic-review-recovery');
        self::assertSame([], json_decode(decrypt($admin->fresh()->two_factor_recovery_codes), true));

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $consume->invoke($resolver, $requestBSnapshot, 'synthetic-review-recovery');
    }

    public function test_characterization_disabled_tenancy_bootstrappers_do_not_isolate_fixture_databases(): void
    {
        $tenantA = Tenant::create(['id' => 'fixture-a']);
        $tenantB = Tenant::create(['id' => 'fixture-b']);
        tenancy()->initialize($tenantA);
        $userA = User::create(['id' => 1, 'name' => 'A', 'email' => 'a@example.test', 'password' => 'SyntheticReview123!', 'status' => 'active']);
        $pdoA = DB::connection('tenant')->getPdo();

        tenancy()->initialize($tenantB);
        $userB = User::create(['id' => 1, 'name' => 'B', 'email' => 'b@example.test', 'password' => 'SyntheticReview123!', 'status' => 'active']);

        // This passes to document the limitation of the submitted test harness, not approve tenant isolation.
        self::assertSame($pdoA, DB::connection('tenant')->getPdo());
        self::assertNotSame($userA->id, $userB->id);
        self::assertSame(2, User::count());
    }
}
