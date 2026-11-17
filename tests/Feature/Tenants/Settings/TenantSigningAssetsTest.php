<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Settings;

use App\Actions\Tenants\Settings\UpdateTenantSettingsAction;
use App\Http\Controllers\Tenants\Settings\SettingsController;
use App\Http\Requests\Tenants\Settings\MainSettingsRequest;
use App\Models\Tenants\AuctionClosings\AuctionClosing;
use App\Models\Tenants\AuctionEstablishments\AuctionEstablishmentRequest;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionFormGeneration;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\Settings\Settings;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Auctions\Forms\AuctionRegulatoryFormDataBuilder;
use App\Services\Tenants\Auctions\Forms\AuctionRegulatoryForms;
use App\Services\Tenants\Auctions\Forms\AuctionRegulatoryFormService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class TenantSigningAssetsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('office_name')->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/tenant/2026_11_17_180000_add_establishment_signing_assets_to_settings_table.php'))->up();
        Storage::fake('tenant_private');
        Storage::fake('tenant_public');
    }

    protected function tearDown(): void
    {
        tenancy()->initialized = false;
        parent::tearDown();
    }

    public function test_signing_uploads_accept_only_png_images_up_to_ten_megabytes(): void
    {
        $rules = (new MainSettingsRequest)->rules();
        foreach (['establishment_signature', 'establishment_stamp'] as $field) {
            self::assertArrayHasKey($field, $rules);
            self::assertTrue(Validator::make([$field => UploadedFile::fake()->image('asset.png')], [$field => $rules[$field]])->passes());
            $jpeg = UploadedFile::fake()->image('asset.jpg');
            $disguised = new UploadedFile($jpeg->getPathname(), 'asset.png', 'image/png', null, true);
            foreach ([$jpeg, $disguised, UploadedFile::fake()->create('asset.png', 1, 'text/plain'), UploadedFile::fake()->image('asset.png')->size(10241)] as $invalid) {
                self::assertFalse(Validator::make([$field => $invalid], [$field => $rules[$field]])->passes());
            }
        }
    }

    public function test_signing_uploads_are_private_and_retained_without_replacement(): void
    {
        $action = app(UpdateTenantSettingsAction::class);
        $settings = $action->execute([], null, UploadedFile::fake()->image('signature.png'), UploadedFile::fake()->image('stamp.png'));
        self::assertNotNull($settings->establishment_signature);
        self::assertNotNull($settings->establishment_stamp);
        Storage::disk('tenant_private')->assertExists([$settings->establishment_signature, $settings->establishment_stamp]);
        self::assertSame([], Storage::disk('tenant_public')->allFiles());
        $oldSignature = $settings->establishment_signature;
        $oldStamp = $settings->establishment_stamp;
        $settings = $action->execute(['office_name' => 'Updated office']);
        self::assertSame($oldSignature, $settings->establishment_signature);
        self::assertSame($oldStamp, $settings->establishment_stamp);
        $settings = $action->execute([], null, UploadedFile::fake()->image('replacement.png'));
        self::assertNotSame($oldSignature, $settings->establishment_signature);
        self::assertSame($oldStamp, $settings->establishment_stamp);
        Storage::disk('tenant_private')->assertMissing($oldSignature);
        Storage::disk('tenant_private')->assertExists($oldStamp);
    }

    public function test_each_generation_snapshots_current_images_without_changing_approved_business_data(): void
    {
        tenancy()->initialized = true;
        $settings = app(UpdateTenantSettingsAction::class)->execute([], null, UploadedFile::fake()->image('signature.png'), UploadedFile::fake()->image('stamp.png'));
        $auction = $this->approvedAuction();
        $original = $auction->establishmentRequest->data;
        $builder = app(AuctionRegulatoryFormDataBuilder::class);
        $first = $builder->build($auction, 'establishment');
        $expected = 'data:image/png;base64,'.base64_encode(Storage::disk('tenant_private')->get($settings->establishment_signature));
        self::assertSame($expected, $first['signing_assets']['signature']);
        self::assertNotEmpty($first['signing_assets']['stamp']);
        Storage::disk('tenant_private')->put($settings->establishment_signature, UploadedFile::fake()->image('changed.png', 40, 20)->getContent());
        $second = $builder->build($auction, 'establishment');
        self::assertNotSame($first['signing_assets']['signature'], $second['signing_assets']['signature']);
        self::assertSame($original, $auction->establishmentRequest->data);
        self::assertSame($second['signing_assets'], $builder->build($auction, 'closing')['signing_assets']);
        Storage::disk('tenant_private')->delete([$settings->establishment_signature, $settings->establishment_stamp]);
        self::assertArrayNotHasKey('signing_assets', $builder->build($auction, 'establishment'));
        self::assertSame($expected, $first['signing_assets']['signature']);
    }

    public function test_failed_settings_save_removes_new_uploads_and_preserves_previous_signing_assets(): void
    {
        $action = app(UpdateTenantSettingsAction::class);
        $settings = $action->execute([], null, UploadedFile::fake()->image('signature.png'), UploadedFile::fake()->image('stamp.png'));
        $previousFiles = Storage::disk('tenant_private')->allFiles();
        Event::listen('eloquent.saving: '.Settings::class, function (Settings $settings): void {
            if ($settings->office_name === 'Fail this save') {
                throw new \RuntimeException('Settings persistence failed.');
            }
        });
        try {
            $action->execute(['office_name' => 'Fail this save'], null, UploadedFile::fake()->image('replacement.png'), UploadedFile::fake()->image('replacement-stamp.png'));
            self::fail('A failed save must propagate the failure.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Settings persistence failed.', $exception->getMessage());
        }
        self::assertSame($previousFiles, Storage::disk('tenant_private')->allFiles());
        self::assertSame($settings->establishment_signature, $settings->fresh()->establishment_signature);
        self::assertSame($settings->establishment_stamp, $settings->fresh()->establishment_stamp);
    }

    public function test_missing_or_invalid_private_images_are_omitted_from_fresh_snapshots(): void
    {
        tenancy()->initialized = true;
        $settings = app(UpdateTenantSettingsAction::class)->execute([], null, UploadedFile::fake()->image('signature.png'));
        Storage::disk('tenant_private')->put($settings->establishment_signature, '<svg></svg>');
        self::assertArrayNotHasKey('signing_assets', app(AuctionRegulatoryFormDataBuilder::class)->build($this->approvedAuction(), 'establishment'));
        $settings->update(['establishment_signature' => '../another-tenant/signature.png']);
        self::assertArrayNotHasKey('signing_assets', app(AuctionRegulatoryFormDataBuilder::class)->build($this->approvedAuction(), 'establishment'));
    }

    public function test_signing_preview_requires_settings_permission_and_only_serves_known_assets(): void
    {
        $settings = app(UpdateTenantSettingsAction::class)->execute([], null, UploadedFile::fake()->image('signature.png'));
        Route::middleware('web')->get('/test-settings/signing/{asset}', [SettingsController::class, 'signingAsset']);
        Gate::swap(new \Illuminate\Auth\Access\Gate(app(), fn () => auth('tenant')->user()));
        Gate::define('system.settings', fn (User $user): bool => $user->getKey() === 7);
        $user = new User;
        $user->setRawAttributes(['id' => 8]);
        $this->actingAs($user, 'tenant')->get('/test-settings/signing/signature')->assertForbidden();
        $user->setRawAttributes(['id' => 7]);
        $response = $this->actingAs($user, 'tenant')->get('/test-settings/signing/signature');
        $response->assertOk()->assertHeader('Content-Type', 'image/png');
        self::assertStringContainsString('private', $response->headers->get('Cache-Control'));
        self::assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->get('/test-settings/signing/image')->assertNotFound();
        $this->get('/test-settings/signing/stamp')->assertNotFound();
        Storage::disk('tenant_private')->delete($settings->establishment_signature);
        $this->get('/test-settings/signing/signature')->assertNotFound();
    }

    public function test_retry_of_saved_approved_request_creates_new_immutable_snapshot_with_current_signing_assets(): void
    {
        Schema::create('auctions', function (Blueprint $table): void {
            $table->id();
            $table->softDeletes();
        });
        Schema::create('auction_establishment_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('auction_id');
            $table->json('data');
            $table->string('approval_number');
            $table->timestamp('approved_at');
            $table->timestamps();
        });
        (require database_path('migrations/tenant/2026_11_09_150200_create_auction_form_generations_table.php'))->up();
        DB::table('auctions')->insert(['id' => 1]);
        DB::table('auction_establishment_requests')->insert(['auction_id' => 1, 'data' => json_encode(['auction' => ['name' => 'Approved business data']]), 'approval_number' => '000123', 'approved_at' => now()]);
        tenancy()->initialized = true;
        $action = app(UpdateTenantSettingsAction::class);
        $action->execute([], null, UploadedFile::fake()->image('signature.png'));
        $auction = Auction::query()->findOrFail(1);
        $service = app(AuctionRegulatoryFormService::class);
        $first = $service->requestGeneration($auction, 'establishment');
        self::assertNotNull($first);
        $firstSnapshot = $first->snapshot;
        $action->execute([], null, UploadedFile::fake()->image('replacement.png', 50, 30));
        $second = $service->retryLatest($auction, 'establishment');
        self::assertNotSame($first->id, $second->id);
        self::assertNotSame($firstSnapshot['signing_assets']['signature'], $second->snapshot['signing_assets']['signature']);
        self::assertSame($firstSnapshot, $first->fresh()->snapshot);
        self::assertSame(['auction' => ['name' => 'Approved business data']], $auction->establishmentRequest->fresh()->data);
        self::assertSame('000123', $auction->establishmentRequest->fresh()->approval_number);
        self::assertSame(2, AuctionFormGeneration::query()->count());
    }

    public function test_images_are_contained_only_in_establishment_signature_and_stamp_cells_on_both_forms(): void
    {
        $image = 'data:image/png;base64,'.base64_encode(UploadedFile::fake()->image('signature.png')->getContent());
        foreach (['establishment', 'closing'] as $type) {
            $html = app(AuctionRegulatoryForms::class)->html(['signing_assets' => ['signature' => $image, 'stamp' => $image]], $type);
            $document = new \DOMDocument;
            @$document->loadHTML($html);
            $xpath = new \DOMXPath($document);
            $tables = $xpath->query('//table[contains(@class,"signatures")]');
            self::assertGreaterThan(0, $tables->length);
            foreach ($tables as $table) {
                self::assertSame(1, $xpath->query('.//tbody/tr/td[1]/img', $table)->length);
                self::assertSame(1, $xpath->query('.//tbody/tr/td[2]/img', $table)->length);
                self::assertSame(0, $xpath->query('.//tbody/tr/td[3]/img', $table)->length);
            }
            self::assertSame($tables->length * 2, $xpath->query('//img[@class="establishment-signing-image"]')->length);
            self::assertStringContainsString('object-fit:contain', $html);
        }
    }

    public function test_renderer_rejects_remote_or_non_png_signing_sources(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(AuctionRegulatoryForms::class)->html(['signing_assets' => ['signature' => 'https://example.test/signature.png']], 'closing');
    }

    private function approvedAuction(): Auction
    {
        $request = new AuctionEstablishmentRequest(['data' => ['auction' => ['name' => 'Saved business data'], 'properties' => [['property_id' => 12]]]]);
        $request->forceFill(['approval_number' => '123', 'approved_at' => now()]);
        $request->exists = true;
        $closing = new AuctionClosing(['closing_date' => now(), 'saved_at' => now()]);
        $closing->exists = true;
        $property = new Property;
        $property->setRawAttributes(['id' => 12]);
        $property->setRelation('pivot', new Pivot(['result_status' => 'unsold', 'unsold_reason' => 'No bids']));
        $auction = new Auction;
        $auction->setRelation('establishmentRequest', $request);
        $auction->setRelation('closing', $closing);
        $auction->setRelation('properties', new Collection([$property]));

        return $auction;
    }
}
