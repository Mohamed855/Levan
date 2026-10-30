<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class AdminLoginGuardIsolationTest extends TestCase
{
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
        $this->withoutVite();

        Schema::connection('mysql')->create('admin_users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('status');
            $table->boolean('is_super_admin')->default(false);
            $table->string('two_factor_secret')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_persisted_tenant_session_does_not_break_admin_login_validation(): void
    {
        $this->withPersistedTenantSession()
            ->post('http://localhost/superadmin/login', [])
            ->assertSessionHasErrors(['email', 'password']);

        self::assertFalse(Auth::guard('tenant')->hasUser());
        self::assertFalse(tenancy()->initialized);
    }

    public function test_persisted_tenant_session_does_not_break_admin_two_factor_validation(): void
    {
        $this->withPersistedTenantSession()
            ->withSession(['login.id' => 1])
            ->post('http://localhost/superadmin/two-factor-challenge', [])
            ->assertSessionHasErrors(['code', 'recovery_code']);

        self::assertFalse(Auth::guard('tenant')->hasUser());
        self::assertFalse(tenancy()->initialized);
    }

    public function test_admin_can_log_in_with_a_persisted_tenant_session(): void
    {
        $admin = AdminUser::create([
            'name' => 'Central Admin', 'email' => 'admin@example.test',
            'password' => 'AdminPassword123!', 'status' => 'active', 'is_super_admin' => true,
        ]);

        $this->withPersistedTenantSession()
            ->post('http://localhost/superadmin/login', [
                'email' => $admin->email, 'password' => 'AdminPassword123!',
            ])->assertRedirect('/superadmin');

        $this->assertAuthenticatedAs($admin, 'admin');
        self::assertFalse(Auth::guard('tenant')->hasUser());
        self::assertFalse(tenancy()->initialized);
    }

    public function test_admin_login_remains_rate_limited_with_a_persisted_tenant_session(): void
    {
        $this->withPersistedTenantSession();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('http://localhost/superadmin/login', [])->assertUnprocessable();
        }

        $this->postJson('http://localhost/superadmin/login', [])->assertTooManyRequests();
        self::assertFalse(Auth::guard('tenant')->hasUser());
    }

    private function withPersistedTenantSession(): self
    {
        Auth::shouldUse('tenant');

        return $this->withSession([
            'auth_tenant_id' => 'previous-tenant',
            Auth::guard('tenant')->getName() => 7,
        ]);
    }
}
