<?php

declare(strict_types=1);

namespace Tests\Unit\Tenants;

use App\Actions\Tenants\Properties\CreatePropertyAction;
use App\Actions\Tenants\Properties\UpdatePropertyAction;
use App\Http\Requests\Tenants\Assignments\StoreAssignmentRequest;
use App\Http\Requests\Tenants\Assignments\UpdateAssignmentRequest;
use App\Http\Requests\Tenants\Auctions\StoreAuctionRequest;
use App\Http\Requests\Tenants\Auctions\UpdateAuctionRequest;
use App\Http\Requests\Tenants\Properties\StorePropertyRequest;
use App\Http\Requests\Tenants\Properties\UpdatePropertyRequest;
use App\Models\Tenants\Assignments\IssuingEntity;
use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\Auctions\AuctionTemplateRenderer;
use App\Services\Tenants\Auctions\Reports\AuctionReportDataBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class TenantAssetInputTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'asset_inputs', 'database.connections.asset_inputs' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
        ]]);
        DB::setDefaultConnection('asset_inputs');
        Schema::create('issuing_entities', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_assignment_letter_accepts_pdf_and_raster_images_on_create_and_update(): void
    {
        foreach ([new StoreAssignmentRequest, new UpdateAssignmentRequest] as $request) {
            $rules = Arr::only($request->rules(), ['assignment_letter']);
            foreach ([
                UploadedFile::fake()->create('letter.pdf', 10, 'application/pdf'),
                UploadedFile::fake()->image('letter.jpg'),
                UploadedFile::fake()->image('letter.png'),
                UploadedFile::fake()->image('letter.webp'),
            ] as $file) {
                $validator = Validator::make(['assignment_letter' => $file], $rules);
                self::assertTrue($validator->passes(), $request::class.': '.$validator->errors()->toJson());
            }

            self::assertFalse(Validator::make([
                'assignment_letter' => UploadedFile::fake()->create('letter.exe', 10, 'application/x-msdownload'),
            ], $rules)->passes());
            self::assertFalse(Validator::make([
                'assignment_letter' => UploadedFile::fake()->create('oversized.pdf', 10241, 'application/pdf'),
            ], $rules)->passes());
        }
    }

    public function test_property_estimated_value_is_validated_and_retained_on_create_and_update(): void
    {
        foreach ([new StorePropertyRequest, new UpdatePropertyRequest] as $request) {
            $rules = Arr::only($request->rules(), ['estimated_value']);
            foreach (['0', '1250000.75'] as $value) {
                $validator = Validator::make(['estimated_value' => $value], $rules);
                self::assertTrue($validator->passes(), $validator->errors()->toJson());
                self::assertSame($value, $validator->validated()['estimated_value'] ?? null);
            }
            foreach (['-1', 'invalid', '10000000000000', '5.001'] as $invalid) {
                self::assertFalse(Validator::make(['estimated_value' => $invalid], $rules)->passes(), $invalid);
            }
            foreach ([[], ['estimated_value' => null], ['estimated_value' => '']] as $optional) {
                $request->replace($optional);
                $validator = Validator::make($request->validationData(), $rules);
                self::assertTrue($validator->passes(), $validator->errors()->toJson());
                self::assertArrayNotHasKey('estimated_value', $validator->validated());
            }
        }
    }

    public function test_auction_platform_requires_an_existing_non_deleted_issuing_entity(): void
    {
        $entity = IssuingEntity::query()->create(['name' => 'Independent auction platform']);
        foreach ([new StoreAuctionRequest, new UpdateAuctionRequest] as $request) {
            $rules = Arr::only($request->rules(), ['platform_entity_id']);
            $validator = Validator::make(['platform_entity_id' => $entity->id], $rules);
            self::assertTrue($validator->passes(), $validator->errors()->toJson());
            self::assertSame($entity->id, $validator->validated()['platform_entity_id'] ?? null);
            self::assertFalse(Validator::make(['platform_entity_id' => 99999], $rules)->passes());
            self::assertFalse(Validator::make(['platform_entity_id' => 'Free text'], $rules)->passes());
        }
        $entity->delete();
        foreach ([new StoreAuctionRequest, new UpdateAuctionRequest] as $request) {
            self::assertFalse(Validator::make(['platform_entity_id' => $entity->id], Arr::only($request->rules(), ['platform_entity_id']))->passes());
        }
    }

    public function test_platform_relation_persists_independently_and_supplies_template_display_name(): void
    {
        (require database_path('migrations/tenant/2026_10_06_000003_create_auctions_table.php'))->up();
        $entity = IssuingEntity::query()->create(['name' => 'Selected platform']);
        $auction = Auction::query()->create([
            'assignment_id' => 12, 'name' => 'Auction', 'start_date' => '2026-11-12',
            'end_date' => '2026-11-13', 'city' => 'Riyadh', 'supervising_entity' => 'Supervising authority',
            'platform_entity_id' => $entity->id,
        ])->fresh();

        self::assertSame($entity->id, $auction->platform_entity_id);
        self::assertSame('Supervising authority', $auction->supervising_entity);
        self::assertSame('Selected platform', $auction->platform_name);
        self::assertSame('Selected platform', app(AuctionTemplateRenderer::class)->bindings($auction)['auction.platform_name']);

        Schema::create('settings', fn (Blueprint $table) => $table->id());
        $auction->setRelation('assignment', null);
        $auction->setRelation('properties', new Collection);
        $auction->setRelation('marketingCampaigns', new Collection);
        $auction->setRelation('attachments', new Collection);
        $auction->setRelation('closing', null);
        $auction->setRelation('establishmentRequest', null);
        $snapshot = app(AuctionReportDataBuilder::class)->build($auction);
        self::assertSame('Selected platform', $snapshot['auction']['platform_name']);
        self::assertSame('Supervising authority', $snapshot['auction']['supervising_entity']);
    }

    public function test_auction_update_preserves_its_deleted_platform_but_rejects_other_deleted_entities(): void
    {
        $current = IssuingEntity::query()->create(['name' => 'Historical platform']);
        $other = IssuingEntity::query()->create(['name' => 'Other historical platform']);
        $active = IssuingEntity::query()->create(['name' => 'Active platform']);
        $current->delete();
        $other->delete();

        $auction = new Auction(['platform_entity_id' => $current->id]);
        $auction->id = 25;
        $auction->exists = true;
        $request = UpdateAuctionRequest::create('/auctions/25', 'PUT');
        $route = new Route('PUT', '/auctions/{auction}', fn () => null);
        $route->bind($request);
        $route->setParameter('auction', $auction);
        $request->setRouteResolver(fn () => $route);

        foreach ([$current->id => true, $other->id => false, $active->id => true] as $selected => $accepted) {
            $request->replace(['platform_entity_id' => (string) $selected]);
            $validator = Validator::make($request->all(), Arr::only($request->rules(), ['platform_entity_id']));
            self::assertSame($accepted, $validator->passes(), $validator->errors()->toJson());
        }
    }

    public function test_validated_estimated_value_is_saved_and_updated_by_property_actions(): void
    {
        foreach ([
            '2026_10_06_000002_create_properties_table.php',
            '2026_10_06_000003_create_auctions_table.php',
            '2026_10_06_000004_create_auction_properties_table.php',
            '2026_10_06_000007_create_property_boundaries_table.php',
            '2026_10_06_000008_create_property_owners_table.php',
            '2026_10_06_000009_create_property_units_table.php',
            '2026_10_11_000003_create_attachments_table.php',
        ] as $migration) {
            (require database_path('migrations/tenant/'.$migration))->up();
        }
        $rules = Arr::only((new StorePropertyRequest)->rules(), ['estimated_value']);
        $value = Validator::make(['estimated_value' => '1250000.75'], $rules)->validated();
        $property = app(CreatePropertyAction::class)->execute(array_merge($value, [
            'property_number' => 'ESTIMATED-1', 'name' => 'Property', 'type' => 'residential_land', 'city' => 'Riyadh',
        ]), 1);
        self::assertSame('1250000.75', $property->fresh()->estimated_value);

        $rules = Arr::only((new UpdatePropertyRequest)->rules(), ['estimated_value']);
        $value = Validator::make(['estimated_value' => '950000.50'], $rules)->validated();
        app(UpdatePropertyAction::class)->execute($property, $value);
        self::assertSame('950000.50', $property->fresh()->estimated_value);

        $request = new UpdatePropertyRequest(['estimated_value' => null]);
        $value = Validator::make($request->validationData(), $rules)->validated();
        app(UpdatePropertyAction::class)->execute($property, $value);
        self::assertSame('950000.50', $property->fresh()->estimated_value);
    }
}
