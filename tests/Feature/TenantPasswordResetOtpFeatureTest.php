<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\Auth\OtpMail;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Tests\TestCase;

final class PasswordResetOtpTenancyBootstrapper implements TenancyBootstrapper
{
    public function bootstrap(\Stancl\Tenancy\Contracts\Tenant $tenant): void
    {
        DB::setDefaultConnection('tenant');
    }

    public function revert(): void
    {
        DB::setDefaultConnection('mysql');
    }
}

class TenantPasswordResetOtpFeatureTest extends TestCase
{
    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.connections.tenant' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'tenancy.bootstrappers' => [PasswordResetOtpTenancyBootstrapper::class],
            'activitylog.enabled' => false,
            'services.moyasar.mode' => 'test',
        ]);
        DB::purge('mysql');
        DB::purge('tenant');
        $this->withoutVite();
        Mail::fake();

        Schema::connection('mysql')->create('tenants', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->text('data')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::connection('mysql')->create('domains', function (Blueprint $table): void {
            $table->id();
            $table->string('domain')->unique();
            $table->string('tenant_id');
            $table->timestamps();
        });
        Schema::connection('tenant')->create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('global_uid')->nullable();
            $table->string('status')->default('active');
            $table->boolean('must_change_password')->default(false);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::connection('tenant')->create('settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('maintenance_mode')->default(false);
        });
        DB::connection('mysql')->table('tenants')->insert(['id' => 'otp-test']);
        DB::connection('mysql')->table('domains')->insert(['domain' => 'test.localhost', 'tenant_id' => 'otp-test']);
        $this->tenant = Tenant::findOrFail('otp-test');
        tenancy()->initialize($this->tenant);

        $this->user = User::create(
            [
                'email' => 'test-otp-user@example.com',
                'name' => 'OTP Test User',
                'password' => Hash::make('OldPassword123!'),
                'status' => 'active',
            ]
        );
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_forgot_password_page_renders_email_form_initially(): void
    {
        $response = $this->get('http://test.localhost/forgot-password');

        $response->assertStatus(200);
        $response->assertSee('هل نسيت كلمة المرور؟');
        $response->assertSee('name="email"', false);
        $response->assertDontSee('checkout-otp-timer', false);
    }

    public function test_requesting_otp_fails_for_unknown_email(): void
    {
        $response = $this->post('http://test.localhost/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertNull(session('password_reset_verification'));
    }

    public function test_requesting_otp_succeeds_and_transitions_to_otp_step(): void
    {
        $response = $this->post('http://test.localhost/forgot-password', [
            'email' => $this->user->email,
        ]);

        $response->assertRedirect('http://test.localhost/forgot-password');

        $verification = session('password_reset_verification');
        $this->assertNotNull($verification);
        $this->assertSame($this->tenant->id, $verification['tenant_id']);
        $this->assertSame($this->user->id, $verification['user_id']);
        $this->assertSame($this->user->email, $verification['email']);
        $this->assertSame(hash('sha256', '000000'), $verification['otp_hash']);
        $this->assertFalse($verification['verified']);
        Mail::assertQueued(OtpMail::class, fn (OtpMail $mail): bool => $mail->hasTo($this->user->email) && $mail->otp === '000000');

        // Next GET request renders OTP view
        $viewResponse = $this->get('http://test.localhost/forgot-password');
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('التحقق من رمز الأمان');
        $viewResponse->assertSee('checkout-otp-timer', false);
        $viewResponse->assertSee('btn-resend-otp', false);
        $this->assertSame($this->user->email, session('password_reset_verification.email'));
    }

    public function test_verifying_invalid_otp_fails_and_increments_attempts(): void
    {
        $this->requestPasswordReset();

        $response = $this->post('http://test.localhost/forgot-password/verify', [
            'otp' => '999999',
        ]);

        $response->assertSessionHasErrors('otp');
        $verification = session('password_reset_verification');
        $this->assertSame(1, $verification['attempts']);
        $this->assertFalse($verification['verified']);
    }

    public function test_resending_otp_updates_timer_and_respects_max_resends(): void
    {
        $this->requestPasswordReset();

        // Resend 1
        $res1 = $this->postJson('http://test.localhost/forgot-password/resend-otp');
        $res1->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('resend_count', 1)
            ->assertJsonPath('remaining_resends', 2);

        // Resend 2
        $res2 = $this->postJson('http://test.localhost/forgot-password/resend-otp');
        $res2->assertOk()
            ->assertJsonPath('resend_count', 2);

        // Resend 3
        $res3 = $this->postJson('http://test.localhost/forgot-password/resend-otp');
        $res3->assertOk()
            ->assertJsonPath('resend_count', 3)
            ->assertJsonPath('remaining_resends', 0);

        // Resend 4 (blocked)
        $res4 = $this->postJson('http://test.localhost/forgot-password/resend-otp');
        $res4->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_editing_email_clears_otp_and_restores_email_input(): void
    {
        $this->requestPasswordReset();

        $response = $this->get('http://test.localhost/forgot-password/edit');

        $response->assertRedirect('http://test.localhost/forgot-password');
        $this->assertNull(session('password_reset_verification'));
        $this->assertSame($this->user->email, old('email'));
    }

    public function test_verifying_valid_otp_marks_session_verified_and_redirects_to_reset_password(): void
    {
        $this->requestPasswordReset();

        $response = $this->post('http://test.localhost/forgot-password/verify', [
            'otp' => '000000',
        ]);

        $response->assertRedirect('http://test.localhost/reset-password');
        $verification = session('password_reset_verification');
        $this->assertTrue($verification['verified']);

        // Can access reset-password page
        $resetPageResponse = $this->get('http://test.localhost/reset-password');
        $resetPageResponse->assertStatus(200);
        $resetPageResponse->assertSee('إعادة تعيين كلمة المرور');
        $resetPageResponse->assertSee('name="password"', false);
    }

    public function test_reset_password_page_redirects_if_otp_not_verified(): void
    {
        $response = $this->get('http://test.localhost/reset-password');

        $response->assertRedirect('http://test.localhost/forgot-password');
        $response->assertSessionHasErrors('email');
    }

    public function test_resetting_password_with_verified_otp_updates_password_and_redirects_to_login(): void
    {
        $newPassword = 'NewSecretPassword123!';

        $this->requestPasswordReset();
        $this->post('http://test.localhost/forgot-password/verify', ['otp' => '000000'])
            ->assertRedirect('http://test.localhost/reset-password');

        $response = $this->post('http://test.localhost/reset-password', [
            'email' => $this->user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response->assertRedirect('http://test.localhost/login');
        $response->assertSessionHas('status');

        $this->assertNull(session('password_reset_verification'));

        // Verify that the user's password was updated
        $this->assertTrue(Hash::check($newPassword, $this->user->fresh()->password));
    }

    public function test_form_validation_endpoint_validates_otp_successfully(): void
    {
        $response = $this->postJson('http://test.localhost/form-validation', [
            'target_path' => 'http://test.localhost/forgot-password/verify',
            'target_method' => 'POST',
            'fields' => ['otp'],
            'otp' => '123456',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_an_otp_bound_to_another_tenant_cannot_be_verified(): void
    {
        $this->requestPasswordReset();
        $verification = session('password_reset_verification');
        $verification['tenant_id'] = 'another-tenant';

        $this->withSession(['password_reset_verification' => $verification])
            ->post('http://test.localhost/forgot-password/verify', ['otp' => '000000'])
            ->assertRedirect('http://test.localhost/forgot-password')
            ->assertSessionHasErrors('otp')
            ->assertSessionMissing('password_reset_verification');
    }

    public function test_an_expired_otp_can_be_replaced_while_its_verification_session_is_active(): void
    {
        $this->requestPasswordReset();
        $this->travel(3)->minutes();

        try {
            $this->post('http://test.localhost/forgot-password/verify', ['otp' => '000000'])
                ->assertSessionHasErrors('otp');
            $this->postJson('http://test.localhost/forgot-password/resend-otp')
                ->assertOk()->assertJsonPath('resend_count', 1);
            $this->post('http://test.localhost/forgot-password/verify', ['otp' => '000000'])
                ->assertRedirect('http://test.localhost/reset-password');
            $this->assertTrue(session('password_reset_verification.verified'));
        } finally {
            $this->travelBack();
        }
    }

    public function test_otp_verification_rejects_an_expired_verification_session(): void
    {
        $this->requestPasswordReset();
        $this->travel(16)->minutes();

        try {
            $this->post('http://test.localhost/forgot-password/verify', ['otp' => '000000'])
                ->assertRedirect('http://test.localhost/forgot-password')
                ->assertSessionHasErrors('otp')
                ->assertSessionMissing('password_reset_verification');
        } finally {
            $this->travelBack();
        }
    }

    public function test_form_validation_endpoint_fails_when_otp_is_invalid_format(): void
    {
        $response = $this->postJson('http://test.localhost/form-validation', [
            'target_path' => 'http://test.localhost/forgot-password/verify',
            'target_method' => 'POST',
            'fields' => ['otp'],
            'otp' => '123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['otp']);
    }

    private function requestPasswordReset(): void
    {
        $this->from('http://test.localhost/forgot-password')
            ->post('http://test.localhost/forgot-password', ['email' => $this->user->email])
            ->assertRedirect('http://test.localhost/forgot-password');
    }
}
