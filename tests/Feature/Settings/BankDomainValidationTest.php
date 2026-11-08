<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Http\Middleware\EnsureAdminTwoFactorIsEnforced;
use App\Http\Middleware\EnsureAuthenticatedAndActiveAdmin;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Bank\Bank;
use App\Models\Admin\ControlDomain\ControlDomain;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class BankDomainValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'activitylog.enabled' => false,
        ]);
        DB::purge('mysql');
        Storage::fake('public');

        Schema::connection('mysql')->create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::connection('mysql')->create('banks', function (Blueprint $table): void {
            $table->id();
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('iban');
            $table->string('account_number');
            $table->string('icon');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::connection('mysql')->create('control_domains', function (Blueprint $table): void {
            $table->id();
            $table->string('domain_extension')->unique();
            $table->decimal('price', 8, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $admin = (new AdminUser)->forceFill(['id' => 91, 'name' => 'Settings administrator']);
        $admin->setRelation('roles', collect([(new Role)->forceFill(['name' => 'SuperAdmin', 'guard_name' => 'admin'])]));
        $this->actingAs($admin, 'admin');
        $this->withoutMiddleware([EnsureAuthenticatedAndActiveAdmin::class, EnsureAdminTwoFactorIsEnforced::class]);
    }

    protected function tearDown(): void
    {
        DB::purge('mysql');
        parent::tearDown();
    }

    private function bank(array $overrides = []): array
    {
        return array_replace([
            'bank_name' => 'البنك الأهلي',
            'account_name' => 'شركة لوت',
            'iban' => 'SA0380000000608010167519',
            'account_number' => '000608010167519',
            'icon' => UploadedFile::fake()->image('bank.png'),
        ], $overrides);
    }

    public function test_empty_bank_fields_are_rejected_by_the_shared_create_validator(): void
    {
        $this->postJson('/superadmin/wizard-drafts/validate-step', [
            'target_path' => '/superadmin/settings/banks',
            'target_method' => 'POST',
            'fields' => ['bank_name', 'account_name', 'iban', 'account_number', 'icon'],
        ])->assertUnprocessable()->assertJsonValidationErrors(['bank_name', 'account_name', 'iban', 'account_number', 'icon']);
    }

    public function test_bank_create_and_update_reject_invalid_account_details_without_mutating_records(): void
    {
        $payload = $this->bank(['iban' => 'not-an-iban', 'account_number' => 'account-123']);
        $this->postJson('/superadmin/settings/banks', $payload)
            ->assertUnprocessable()->assertJsonValidationErrors(['iban', 'account_number']);
        self::assertSame(0, Bank::count());

        $bank = Bank::withoutEvents(fn () => Bank::create(array_replace($this->bank(), ['icon' => 'banks/icons/existing.png'])));
        $this->putJson("/superadmin/settings/banks/{$bank->id}", $payload)
            ->assertUnprocessable()->assertJsonValidationErrors(['iban', 'account_number']);
        self::assertSame('SA0380000000608010167519', $bank->refresh()->iban);
        self::assertSame('000608010167519', $bank->account_number);
    }

    public function test_bank_create_and_edit_shared_validation_accept_valid_details_and_optional_existing_icon(): void
    {
        $this->post('/superadmin/wizard-drafts/validate-step', $this->bank() + [
            'target_path' => '/superadmin/settings/banks',
            'target_method' => 'POST',
            'fields' => ['bank_name', 'account_name', 'iban', 'account_number', 'icon'],
        ], ['Accept' => 'application/json'])->assertOk()->assertJson(['success' => true]);

        $bank = Bank::withoutEvents(fn () => Bank::create(array_replace($this->bank(), ['icon' => 'banks/icons/existing.png'])));
        $payload = $this->bank();
        unset($payload['icon']);
        $this->postJson('/superadmin/wizard-drafts/validate-step', $payload + [
            'target_path' => "/superadmin/settings/banks/{$bank->id}",
            'target_method' => 'PUT',
            'fields' => ['bank_name', 'account_name', 'iban', 'account_number', 'icon'],
        ])->assertOk()->assertJson(['success' => true]);
    }

    public function test_invalid_bank_icon_is_rejected(): void
    {
        $this->post('/superadmin/settings/banks', $this->bank(['icon' => UploadedFile::fake()->create('bank.pdf', 10, 'application/pdf')]), ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors(['icon']);
        self::assertSame(0, Bank::count());
    }

    public function test_domain_create_and_edit_require_an_extension_and_non_negative_price(): void
    {
        $domain = ControlDomain::withoutEvents(fn () => ControlDomain::create(['domain_extension' => '.com', 'price' => 100]));
        foreach ([['POST', '/superadmin/settings/domains'], ['PUT', "/superadmin/settings/domains/{$domain->id}"]] as [$method, $path]) {
            $this->postJson('/superadmin/wizard-drafts/validate-step', [
                'target_path' => $path,
                'target_method' => $method,
                'fields' => ['domain_extension', 'price'],
            ])->assertUnprocessable()->assertJsonValidationErrors(['domain_extension', 'price']);
        }
    }

    public static function invalidDomains(): array
    {
        return [
            'missing leading dot' => [['domain_extension' => 'com', 'price' => 100], ['domain_extension']],
            'URL instead of extension' => [['domain_extension' => 'https://example.com', 'price' => 100], ['domain_extension']],
            'invalid characters' => [['domain_extension' => '.bad_extension', 'price' => 100], ['domain_extension']],
            'too long' => [['domain_extension' => '.'.str_repeat('a', 256), 'price' => 100], ['domain_extension']],
            'negative price' => [['domain_extension' => '.com', 'price' => -1], ['price']],
            'price exceeds storage precision' => [['domain_extension' => '.com', 'price' => 1000000], ['price']],
            'too many decimal places' => [['domain_extension' => '.com', 'price' => '100.123'], ['price']],
        ];
    }

    #[DataProvider('invalidDomains')]
    public function test_invalid_domain_details_are_rejected_before_creation(array $payload, array $fields): void
    {
        $this->postJson('/superadmin/settings/domains', $payload)
            ->assertUnprocessable()->assertJsonValidationErrors($fields);
        self::assertSame(0, ControlDomain::count());
    }

    public function test_domain_validation_checks_duplicates_and_accepts_a_compound_extension(): void
    {
        $domain = ControlDomain::withoutEvents(fn () => ControlDomain::create(['domain_extension' => '.com', 'price' => 100]));
        $this->postJson('/superadmin/settings/domains', ['domain_extension' => '.com', 'price' => 100])
            ->assertUnprocessable()->assertJsonValidationErrors(['domain_extension']);

        foreach ([['POST', '/superadmin/settings/domains', '.com.sa'], ['PUT', "/superadmin/settings/domains/{$domain->id}", '.com']] as [$method, $path, $extension]) {
            $this->postJson('/superadmin/wizard-drafts/validate-step', [
                'target_path' => $path,
                'target_method' => $method,
                'fields' => ['domain_extension', 'price'],
                'domain_extension' => $extension,
                'price' => '0.00',
            ])->assertOk()->assertJson(['success' => true]);
        }
    }

    public function test_editing_a_domain_cannot_change_its_readonly_extension(): void
    {
        $domain = ControlDomain::withoutEvents(fn () => ControlDomain::create(['domain_extension' => '.com', 'price' => 100]));
        $this->putJson("/superadmin/settings/domains/{$domain->id}", ['domain_extension' => '.net', 'price' => 200])
            ->assertUnprocessable()->assertJsonValidationErrors(['domain_extension']);
        self::assertSame('.com', $domain->refresh()->domain_extension);
        self::assertSame(100.0, (float) $domain->price);
    }
}
