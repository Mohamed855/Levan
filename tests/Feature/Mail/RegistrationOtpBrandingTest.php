<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Models\Admin\Settings\Settings as CentralSettings;
use App\Models\Tenants\Settings\Settings as TenantSettings;
use App\Services\Shared\Registration\RegistrationOtpService;
use Database\Seeders\admin\SettingsSeeder;
use Database\Seeders\tenant\SettingsTableSeeder;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

final class RegistrationOtpBrandingTest extends TestCase
{
    private string $originalPublicPath;

    private string $temporaryPublicPath;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.connections.tenant' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'activitylog.enabled' => false,
            'mail.default' => 'array',
            'queue.default' => 'sync',
            'services.moyasar.mode' => 'test',
            'seeding.test_seeders_enabled' => true,
        ]);
        DB::purge('mysql');
        DB::purge('tenant');
        DB::setDefaultConnection('mysql');
        (require database_path('migrations/admin/2026_09_23_000008_create_settings_table.php'))->up();
        DB::setDefaultConnection('tenant');
        (require database_path('migrations/tenant/2026_09_24_000001_create_settings_table.php'))->up();
        DB::setDefaultConnection('mysql');

        Storage::fake('public');
        Storage::fake('tenant_public');
        $this->originalPublicPath = public_path();
        $this->temporaryPublicPath = storage_path('framework/testing/mail-branding-'.bin2hex(random_bytes(6)));
        File::ensureDirectoryExists($this->temporaryPublicPath.'/static/brand/logos/lot');
        File::ensureDirectoryExists($this->temporaryPublicPath.'/storage/image');
        File::copy($this->originalPublicPath.'/static/brand/logos/lot/trans-profile-white.png', $this->temporaryPublicPath.'/static/brand/logos/lot/trans-profile-white.png');
        File::copy($this->originalPublicPath.'/static/auth/backgrounds/auth-security-bg.jpg', $this->temporaryPublicPath.'/storage/image/demo-office.jpg');
        $this->app->usePublicPath($this->temporaryPublicPath);
    }

    protected function tearDown(): void
    {
        $this->app->usePublicPath($this->originalPublicPath);
        File::deleteDirectory($this->temporaryPublicPath);
        parent::tearDown();
    }

    public function test_registration_otp_embeds_the_platform_logo_when_settings_contain_a_demo_image(): void
    {
        CentralSettings::create(['image' => 'image/demo-office.jpg', 'office_name' => 'لوت LOT']);
        $message = $this->sendRegistrationOtp();

        $this->assertSame(hash('sha256', '000000'), $message['hash']);
        $this->assertStringContainsString('000000', $message['email']->getHtmlBody());
        $parts = $message['email']->getAttachments();
        $this->assertCount(1, $parts);
        $this->assertSame('trans-profile-white.png', $parts[0]->getFilename());
        $this->assertSame('inline', $parts[0]->getDisposition());
        $this->assertSame(hash_file('sha256', public_path('static/brand/logos/lot/trans-profile-white.png')), hash('sha256', $parts[0]->getBody()));
    }

    public function test_a_genuine_custom_office_logo_is_preserved_in_email(): void
    {
        File::copy($this->originalPublicPath.'/static/brand/logos/lot/trans-profile-white.png', public_path('storage/image/custom-logo.png'));
        CentralSettings::create(['image' => 'image/custom-logo.png']);
        $message = $this->sendRegistrationOtp();

        $this->assertSame('custom-logo.png', $message['email']->getAttachments()[0]->getFilename());
    }

    public static function settingsLogos(): array
    {
        return [
            'central empty' => ['central', null, 'default-office.png'],
            'central demo' => ['central', 'image/demo-office.jpg', 'default-office.png'],
            'central custom' => ['central', 'image/custom-logo.png', 'image/custom-logo.png'],
            'tenant empty' => ['tenant', null, 'default-office.png'],
            'tenant demo' => ['tenant', 'image/demo-office.jpg', 'default-office.png'],
            'tenant custom' => ['tenant', 'image/custom-logo.png', 'image/custom-logo.png'],
        ];
    }

    #[DataProvider('settingsLogos')]
    public function test_demo_seeders_preserve_branding(string $scope, ?string $image, string $expected): void
    {
        $model = $scope === 'central' ? CentralSettings::class : TenantSettings::class;
        DB::setDefaultConnection($scope === 'central' ? 'mysql' : 'tenant');
        $model::create(['image' => $image]);
        $this->seed($scope === 'central' ? SettingsSeeder::class : SettingsTableSeeder::class);

        $this->assertSame($expected, $model::firstOrFail()->image);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame([], Storage::disk('tenant_public')->allFiles());
    }

    private function sendRegistrationOtp(): array
    {
        $message = null;
        Event::listen(MessageSending::class, function (MessageSending $event) use (&$message): void {
            $message = $event->message;
        });
        $hash = app(RegistrationOtpService::class)->issue('branding@example.test');
        $this->assertInstanceOf(Email::class, $message);

        return ['hash' => $hash, 'email' => $message];
    }
}
