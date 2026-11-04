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
final class CodexBatchACriterionAcceptanceTest extends TestCase
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


    private int $sequence = 0;

    private function fixture(string $mode): array
    {
        Auth::guard('admin')->logout();
        Auth::guard('tenant')->logout();
        Auth::forgetGuards();
        $this->flushSession();
        if (tenancy()->initialized) tenancy()->end();
        foreach (['mysql', 'tenant'] as $connection) {
            if (! Schema::connection($connection)->hasTable('settings')) {
                Schema::connection($connection)->create('settings', function (Blueprint $table): void {
                    $table->id();
                    $table->boolean('maintenance_mode')->default(false);
                    $table->boolean('force_two_factor_auth')->default(false);
                });
            }
        }
        if (! Schema::connection('mysql')->hasTable('domains')) {
            Schema::connection('mysql')->create('domains', function (Blueprint $table): void {
                $table->id(); $table->string('domain')->unique();
                $table->string('tenant_id'); $table->timestamps();
            });
        }
        $n = ++$this->sequence;
        // Each synthetic client has an independent HTTP throttle budget.
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.($n % 250 + 1)]);
        $guard = $mode === 'admin' ? 'admin' : 'tenant';
        $tenantId = null;
        $root = '/superadmin';
        $login = '/superadmin/login';
        if ($guard === 'tenant') {
            $tenantId = "accept-$mode-$n";
            $tenant = Tenant::create(['id' => $tenantId, 'access_type' => $mode === 'domain' ? 'domain' : 'path']);
            if ($mode === 'domain') {
                $host = "accept-$n.example.test";
                $tenant->domains()->create(['domain' => $host]);
                $root = "http://$host";
                $login = "$root/login";
            } else {
                $root = "/$tenantId";
                $login = '/admin/login';
            }
            tenancy()->initialize($tenant);
        }
        $engine = app(\PragmaRX\Google2FA\Google2FA::class);
        $secret = $engine->generateSecretKey();
        $class = $guard === 'admin' ? AdminUser::class : User::class;
        $user = $class::create([
            'name' => "Synthetic acceptance $n", 'email' => "$mode-$n@example.test",
            'password' => 'SyntheticReview123!', 'status' => 'active',
        ]);
        $codes = ["accept-$mode-$n-one", "accept-$mode-$n-two"];
        $user->forceFill([
            'two_factor_secret' => encrypt($secret), 'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode($codes)),
        ])->save();
        $pending = ['login.id' => $user->id, 'login.guard' => $guard,
            'login.tenant_id' => $tenantId, 'login.remember' => false];
        if ($mode === 'domain') tenancy()->end();
        return compact('user', 'guard', 'root', 'login', 'pending', 'secret', 'codes');
    }

    public function test_2fa12_current_past_boundary_expiry_and_replay_for_all_http_scopes(): void
    {
        $engine = new class extends \PragmaRX\Google2FA\Google2FA {
            public function getTimestamp() { return 60000000; }
        };
        $this->app->instance(\PragmaRX\Google2FA\Google2FA::class, $engine);
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $configured = config('fortify-options.two-factor-authentication.window');
        if (is_int($configured)) $engine->setWindow($configured);
        self::assertSame(1, $engine->getWindow(), 'Acceptance receipt records the installed effective window.');
        foreach (['admin', 'path', 'domain'] as $mode) {
            $f = $this->fixture($mode);
            $timestamp = $engine->getTimestamp();
            foreach ([-2, 2] as $offset) {
                $response = $this->withSession($f['pending'])->postJson($f['root'].'/two-factor-challenge', [
                    'code' => $engine->oathTotp($f['secret'], $timestamp + $offset),
                ]);
                $response->assertStatus(422)->assertJsonValidationErrors('code');
                self::assertFalse(Auth::guard($f['guard'])->check());
            }
            foreach ([-1, 0] as $offset) {
                $code = $engine->oathTotp($f['secret'], $timestamp + $offset);
                $this->withSession($f['pending'])->post($f['root'].'/two-factor-challenge', ['code' => $code])
                    ->assertRedirect();
                self::assertSame($f['user']->id, Auth::guard($f['guard'])->id());
                Auth::guard($f['guard'])->logout();
                $replay = $this->withSession($f['pending'])->postJson($f['root'].'/two-factor-challenge', ['code' => $code]);
                self::assertSame(422, $replay->getStatusCode(), json_encode([
                    'mode'=>$mode, 'offset'=>$offset, 'location'=>$replay->headers->get('Location'),
                    'authenticated'=>Auth::guard($f['guard'])->check(),
                    'cacheTimestamp'=>cache('fortify.2fa_codes.'.md5($code)),
                ]));
                $replay->assertJsonValidationErrors('code');
                self::assertFalse(Auth::guard($f['guard'])->check());
            }
        }
    }

    private function assertFutureWindowCannotReplay(string $mode): void
    {
        $engine = new class extends \PragmaRX\Google2FA\Google2FA {
            public function getTimestamp() { return 60000000; }
        };
        $this->app->instance(\PragmaRX\Google2FA\Google2FA::class, $engine);
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $f = $this->fixture($mode);
        $code = $engine->oathTotp($f['secret'], $engine->getTimestamp() + 1);
        $this->withSession($f['pending'])->post($f['root'].'/two-factor-challenge', ['code'=>$code])->assertRedirect();
        self::assertSame($f['user']->id, Auth::guard($f['guard'])->id());
        Auth::guard($f['guard'])->logout();
        $replay = $this->withSession($f['pending'])->postJson($f['root'].'/two-factor-challenge', ['code'=>$code]);
        self::assertFalse(Auth::guard($f['guard'])->check(), 'Previously accepted next-window TOTP must not create another login.');
        $replay->assertStatus(422)->assertJsonValidationErrors('code');
    }

    public function test_2fa12_admin_next_window_replay_is_rejected(): void
    {
        $this->assertFutureWindowCannotReplay('admin');
    }

    public function test_2fa12_tenant_path_next_window_replay_is_rejected(): void
    {
        $this->assertFutureWindowCannotReplay('path');
    }

    public function test_2fa12_tenant_domain_next_window_replay_is_rejected(): void
    {
        $this->assertFutureWindowCannotReplay('domain');
    }

    public function test_2fa09_backend_recovery_consumed_once_in_every_http_scope(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        foreach (['admin', 'path', 'domain'] as $mode) {
            $f = $this->fixture($mode);
            $this->withSession($f['pending'])->post($f['root'].'/two-factor-challenge', ['recovery_code' => $f['codes'][0]])
                ->assertRedirect();
            self::assertSame($f['user']->id, Auth::guard($f['guard'])->id());
            self::assertSame([$f['codes'][1]], json_decode(decrypt($f['user']->fresh()->two_factor_recovery_codes), true));
            Auth::guard($f['guard'])->logout();
            $this->withSession($f['pending'])->postJson($f['root'].'/two-factor-challenge', ['recovery_code' => $f['codes'][0]])
                ->assertStatus(422)->assertJsonValidationErrors('recovery_code');
            self::assertFalse(Auth::guard($f['guard'])->check());
            self::assertSame([$f['codes'][1]], json_decode(decrypt($f['user']->fresh()->two_factor_recovery_codes), true));
        }
    }

    public function test_2fa11_backend_shape_matrix_and_empty_inactive_field_for_every_http_scope(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        foreach (['admin', 'path', 'domain'] as $mode) {
            $f = $this->fixture($mode);
            foreach ([[], ['code'=>'12345'], ['code'=>'1234567'], ['code'=>'abc123'],
                ['code'=>['123456']], ['recovery_code'=>['bad']],
                ['code'=>'123456','recovery_code'=>$f['codes'][0]]] as $payload) {
                $this->withSession($f['pending'])->postJson($f['root'].'/two-factor-challenge', $payload)
                    ->assertStatus(422)->assertJsonStructure(['errors']);
                self::assertFalse(Auth::guard($f['guard'])->check());
                self::assertSame($f['codes'], json_decode(decrypt($f['user']->fresh()->two_factor_recovery_codes), true));
            }
            $valid = app(\PragmaRX\Google2FA\Google2FA::class)->getCurrentOtp($f['secret']);
            $this->withSession($f['pending'])->post($f['root'].'/two-factor-challenge', ['code'=>$valid,'recovery_code'=>''])
                ->assertRedirect();
            self::assertSame($f['user']->id, Auth::guard($f['guard'])->id());
            Auth::guard($f['guard'])->logout();
            $this->withSession($f['pending'])->post($f['root'].'/two-factor-challenge', ['code'=>'','recovery_code'=>$f['codes'][0]])
                ->assertRedirect();
            self::assertSame($f['user']->id, Auth::guard($f['guard'])->id());
        }
    }

    public function test_2fa19_backend_cancel_cleanup_old_post_and_new_account_for_all_http_scopes(): void
    {
        foreach (['admin', 'path', 'domain'] as $mode) {
            $f = $this->fixture($mode);
            $this->withSession($f['pending'])
                ->get($f['root'].'/two-factor-challenge/cancel')->assertRedirect($f['login']);
            foreach (['login.id','login.guard','login.tenant_id','login.remember'] as $key) self::assertNull(session($key));
            $this->postJson($f['root'].'/two-factor-challenge', ['recovery_code'=>$f['codes'][0]])->assertForbidden();
            self::assertFalse(Auth::guard($f['guard'])->check());
            self::assertSame($f['codes'], json_decode(decrypt($f['user']->fresh()->two_factor_recovery_codes), true));
            $other = $this->fixture($mode);
            $this->withSession($other['pending'])->post($other['root'].'/two-factor-challenge', ['recovery_code'=>$other['codes'][0]])
                ->assertRedirect();
            self::assertSame($other['user']->id, Auth::guard($other['guard'])->id());
        }
    }

    public function test_2fa20_backend_missing_context_deleted_user_and_corrupt_secret_for_all_http_scopes(): void
    {
        foreach (['admin', 'path', 'domain'] as $mode) {
            $f = $this->fixture($mode);
            $this->get($f['root'].'/two-factor-challenge')->assertRedirect($f['login']);
            $this->postJson($f['root'].'/two-factor-challenge', ['code'=>'123456'])->assertForbidden();
            self::assertFalse(Auth::guard($f['guard'])->check());
            $f['user']->delete();
            $this->withSession($f['pending'])->get($f['root'].'/two-factor-challenge')->assertRedirect($f['login']);
            self::assertFalse(Auth::guard($f['guard'])->check());
            $bad = $this->fixture($mode);
            $bad['user']->forceFill(['two_factor_secret'=>'synthetic-corrupt-encryption'])->save();
            $this->withSession($bad['pending'])->postJson($bad['root'].'/two-factor-challenge', ['code'=>'123456'])
                ->assertStatus(422)->assertJsonValidationErrors('code');
            self::assertFalse(Auth::guard($bad['guard'])->check());
        }
    }

    public function test_2fa03_dom_single_code_label_and_error_contract_for_all_http_scopes(): void
    {
        foreach (['admin', 'path', 'domain'] as $mode) {
            $f = $this->fixture($mode);
            $response = $this->withSession($f['pending'])->get($f['root'].'/two-factor-challenge')->assertOk();
            $document = new \DOMDocument();
            @$document->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
            $xpath = new \DOMXPath($document);
            self::assertSame(1, $xpath->query('//input[@name="code"]')->length);
            $code = $xpath->query('//input[@name="code"]')->item(0);
            self::assertSame('XXXXXX', $code->getAttribute('placeholder'));
            self::assertFalse($code->hasAttribute('disabled'));
            self::assertGreaterThan(0, $xpath->query('//label[@for="code"]')->length);
            self::assertGreaterThan(0, $xpath->query('//*[@id="'.$code->getAttribute('aria-describedby').'"]')->length);
            self::assertSame(0, $xpath->query('//*[@id="otpHidden"]')->length);
            self::assertTrue($xpath->query('//input[@name="recovery_code"]')->item(0)->hasAttribute('disabled'));
        }
    }

    public function test_2fa10_and_16_backend_regeneration_invalid_and_other_account_codes_in_all_http_scopes(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        foreach (['admin', 'path', 'domain'] as $mode) {
            $f = $this->fixture($mode);
            $other = $f['user']->replicate();
            $other->email = 'other-'.$this->sequence.'@example.test';
            $other->global_uid = null;
            $other->two_factor_recovery_codes = encrypt(json_encode(['synthetic-other-account']));
            $other->save();
            foreach (['synthetic-invalid', 'synthetic-other-account'] as $bad) {
                $this->withSession($f['pending'])->postJson($f['root'].'/two-factor-challenge', ['recovery_code'=>$bad])
                    ->assertStatus(422)->assertJsonValidationErrors('recovery_code');
                self::assertFalse(Auth::guard($f['guard'])->check());
                self::assertSame($f['codes'], json_decode(decrypt($f['user']->fresh()->two_factor_recovery_codes), true));
                self::assertSame(['synthetic-other-account'], json_decode(decrypt($other->fresh()->two_factor_recovery_codes), true));
            }
            $secretBefore = $f['user']->fresh()->two_factor_secret;
            $codes = $this->actingAs($f['user'], $f['guard'])->postJson($f['root'].'/two-factor-recovery-codes')
                ->assertOk()->json();
            self::assertCount(8, $codes);
            self::assertCount(8, array_unique($codes));
            self::assertSame($secretBefore, $f['user']->fresh()->two_factor_secret);
            Auth::guard($f['guard'])->logout();
            $this->withSession($f['pending'])->postJson($f['root'].'/two-factor-challenge', ['recovery_code'=>$f['codes'][0]])
                ->assertStatus(422)->assertJsonValidationErrors('recovery_code');
            self::assertFalse(Auth::guard($f['guard'])->check());
            $totp = app(\PragmaRX\Google2FA\Google2FA::class)->getCurrentOtp($f['secret']);
            $this->withSession($f['pending'])->post($f['root'].'/two-factor-challenge', ['code'=>$totp])->assertRedirect();
            self::assertSame($f['user']->id, Auth::guard($f['guard'])->id());
            Auth::guard($f['guard'])->logout();
            $this->withSession($f['pending'])->post($f['root'].'/two-factor-challenge', ['recovery_code'=>$codes[0]])->assertRedirect();
            self::assertSame($f['user']->id, Auth::guard($f['guard'])->id());
            self::assertSame(['synthetic-other-account'], json_decode(decrypt($other->fresh()->two_factor_recovery_codes), true));
        }
    }

    public function test_2fa17_backend_optional_disable_erases_secrets_and_rejects_old_pending_in_all_http_scopes(): void
    {
        foreach (['admin', 'path', 'domain'] as $mode) {
            $f = $this->fixture($mode);
            if (! Schema::connection('mysql')->hasTable('company_settings')) {
                Schema::connection('mysql')->create('company_settings', function (Blueprint $table): void {
                    $table->id(); $table->string('tenant_id')->nullable(); $table->boolean('two_factor_enabled')->default(false);
                });
            }
            $this->actingAs($f['user'], $f['guard'])->deleteJson($f['root'].'/two-factor-authentication')
                ->assertOk()->assertJsonPath('status', 'disabled');
            $fresh = $f['user']->fresh();
            self::assertNull($fresh->two_factor_secret);
            self::assertNull($fresh->two_factor_confirmed_at);
            self::assertNull($fresh->two_factor_recovery_codes);
            Auth::guard($f['guard'])->logout();
            $this->withSession($f['pending'])->postJson($f['root'].'/two-factor-challenge', ['recovery_code'=>$f['codes'][0]])
                ->assertForbidden();
            self::assertFalse(Auth::guard($f['guard'])->check());
        }
    }

    public function test_2fa24_backend_throttle_and_release_for_all_http_scopes(): void
    {
        foreach (['admin', 'path', 'domain'] as $mode) {
            $f = $this->fixture($mode);
            $engine = app(\PragmaRX\Google2FA\Google2FA::class);
            $valid = array_map(fn ($offset) => $engine->oathTotp($f['secret'], $engine->getTimestamp() + $offset), [-1,0,1]);
            $wrong = '000000';
            while (in_array($wrong, $valid, true)) $wrong = str_pad((string) ((int) $wrong + 1), 6, '0', STR_PAD_LEFT);
            for ($i=0; $i<5; $i++) {
                $this->withSession($f['pending'])->postJson($f['root'].'/two-factor-challenge', ['code'=>$wrong])
                    ->assertStatus(422)->assertJsonValidationErrors('code');
                self::assertFalse(Auth::guard($f['guard'])->check());
            }
            $this->withSession($f['pending'])->postJson($f['root'].'/two-factor-challenge', ['code'=>$wrong])
                ->assertStatus(429)->assertJsonStructure(['message']);
            $this->travel(61)->seconds();
            $this->withSession($f['pending'])->postJson($f['root'].'/two-factor-challenge', ['code'=>$wrong])
                ->assertStatus(422)->assertJsonValidationErrors('code');
            self::assertFalse(Auth::guard($f['guard'])->check());
            $this->travelBack();
        }
    }
}
