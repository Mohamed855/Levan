<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Actions\Admin\Settings\ManageBankAction;
use App\DataTables\Admin\Setting\Bank\BankDataTable;
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

final class BankIconAndDataTableTest extends TestCase
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
            $table->string('icon')->nullable();
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

    public function test_manage_bank_action_stores_uploaded_icon_path_in_database(): void
    {
        $action = app(ManageBankAction::class);
        $file = UploadedFile::fake()->image('alrajhi.png', 80, 80);

        $bank = $action->create([
            'bank_name' => 'مصرف الراجحي',
            'icon' => $file,
            'account_name' => 'شركة لوت',
            'iban' => 'SA0380000000608010167519',
            'account_number' => '608010167519',
        ], $file);

        $this->assertNotNull($bank->icon);
        $this->assertStringStartsWith('banks/icons/', $bank->icon);
        $this->assertStringNotContainsString('Temp', $bank->icon);
        $this->assertStringNotContainsString('.tmp', $bank->icon);
        Storage::disk('public')->assertExists($bank->icon);
    }

    public function test_manage_bank_action_update_preserves_existing_icon_when_no_new_file_uploaded(): void
    {
        $action = app(ManageBankAction::class);
        $file = UploadedFile::fake()->image('initial.png', 80, 80);

        $bank = $action->create([
            'bank_name' => 'البنك الأول',
            'account_name' => 'حساب 1',
            'iban' => 'SA0380000000608010167519',
            'account_number' => '608010167519',
        ], $file);

        $initialPath = $bank->icon;
        $this->assertNotNull($initialPath);

        // Update without new file, but with icon => null in data
        $updatedBank = $action->update($bank, [
            'bank_name' => 'البنك الأول المحدث',
            'icon' => null,
            'account_name' => 'حساب 1 معدل',
            'iban' => 'SA0380000000608010167519',
            'account_number' => '608010167519',
        ], null);

        $this->assertSame($initialPath, $updatedBank->icon);
        Storage::disk('public')->assertExists($initialPath);
    }

    public function test_bank_datatable_renders_image_with_tabler_fallback_icon(): void
    {
        $bankWithIcon = Bank::withoutEvents(fn () => Bank::create([
            'bank_name' => 'بنك بشعار',
            'account_name' => 'شركة لوت',
            'iban' => 'SA0380000000608010167519',
            'account_number' => '608010167519',
            'icon' => 'banks/icons/sample.png',
        ]));

        $bankWithoutIcon = Bank::withoutEvents(fn () => Bank::create([
            'bank_name' => 'بنك بدون شعار',
            'account_name' => 'شركة لوت',
            'iban' => 'SA0380000000608010167519',
            'account_number' => '608010167519',
            'icon' => null,
        ]));

        $dataTable = app(BankDataTable::class);
        $response = $dataTable->ajax();
        $json = $response->getData(true);

        $this->assertCount(2, $json['data']);

        // First bank has icon img with fallback
        $this->assertStringContainsString('banks/icons/sample.png', $json['data'][0]['icon']);
        $this->assertStringContainsString('ti-building-bank', $json['data'][0]['icon']);
        $this->assertStringContainsString('object-fit-contain', $json['data'][0]['icon']);

        // Second bank has placeholder fallback directly
        $this->assertStringNotContainsString('<img', $json['data'][1]['icon']);
        $this->assertStringContainsString('ti-building-bank', $json['data'][1]['icon']);
        $this->assertStringContainsString('bg-label-dark', $json['data'][1]['icon']);
    }

    public function test_bank_model_icon_url_handles_temp_path_safely(): void
    {
        $bank = new Bank(['icon' => 'C:\\Users\\user\\AppData\\Local\\Temp\\php89B7.tmp']);
        $this->assertNull($bank->icon_url);

        $bankValid = new Bank(['icon' => 'banks/icons/valid.png']);
        $this->assertStringContainsString('storage/banks/icons/valid.png', (string) $bankValid->icon_url);
    }
}
