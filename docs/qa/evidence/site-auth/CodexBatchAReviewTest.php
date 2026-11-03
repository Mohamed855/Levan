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
final class CodexBatchAReviewTest extends TestCase
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

    public function test_missing_guard_must_not_complete_admin_password_step(): void
    {
        $admin = $this->adminFixture();
        $this->withSession(['login.id' => $admin->id])
            ->post('/superadmin/two-factor-challenge', ['recovery_code' => 'synthetic-review-recovery']);

        self::assertFalse(Auth::guard('admin')->check(), 'Missing login.guard must fail closed.');
    }

    public function test_missing_tenant_binding_must_not_complete_tenant_password_step(): void
    {
        tenancy()->initialize(Tenant::create(['id' => 'review-b']));
        $user = User::create([
            'name' => 'Review B', 'email' => 'review-b@example.test',
            'password' => 'SyntheticReview123!', 'status' => 'active',
        ]);
        $user->forceFill([
            'two_factor_secret' => encrypt('synthetic-unused-secret'),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode(['synthetic-review-recovery'])),
        ])->save();
        $this->withSession(['login.id' => $user->id, 'login.guard' => 'tenant'])
            ->post('/review-b/two-factor-challenge', ['recovery_code' => 'synthetic-review-recovery']);

        self::assertFalse(Auth::guard('tenant')->check(), 'Missing login.tenant_id must fail closed.');
    }

    public function test_unconfirmed_two_factor_state_must_reject_old_pending_challenge(): void
    {
        $admin = $this->adminFixture();
        $admin->forceFill(['two_factor_confirmed_at' => null])->save();
        $this->withSession(['login.id' => $admin->id, 'login.guard' => 'admin'])
            ->post('/superadmin/two-factor-challenge', ['recovery_code' => 'synthetic-review-recovery']);

        self::assertFalse(Auth::guard('admin')->check(), 'Revoked confirmation must invalidate pending 2FA.');
    }

    public function test_admin_cancel_must_clear_tenant_context_left_by_panel_switch(): void
    {
        $this->withSession([
            'login.id' => 1, 'login.guard' => 'admin',
            'login.remember' => true, 'login.tenant_id' => 'previous-tenant',
        ])->get('/superadmin/two-factor-challenge/cancel')->assertRedirect('/superadmin/login');

        self::assertNull(session('login.tenant_id'), 'Cancel must clear all pending login context.');
    }
}
