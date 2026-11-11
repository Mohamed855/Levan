<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Http\Middleware\EnsureAdminTwoFactorIsEnforced;
use App\Http\Middleware\EnsureAuthenticatedAndActiveAdmin;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Bank\Bank;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class BankValidationTest extends TestCase
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
}
