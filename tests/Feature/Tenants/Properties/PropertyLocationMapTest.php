<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Properties;

use App\Actions\Tenants\Properties\CreatePropertyAction;
use App\Actions\Tenants\Properties\UpdatePropertyAction;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PropertyLocationMapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        tenancy()->initialize(Tenant::findOrFail('test'));
        DB::connection('tenant')->beginTransaction();
        $this->actingAs(User::where('email', 'demo@lot.com')->firstOrFail(), 'tenant');
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            DB::connection('tenant')->rollBack();
            tenancy()->end();
        }
        parent::tearDown();
    }

    public function test_create_and_edit_put_the_drawing_tools_and_map_in_their_respective_steps(): void
    {
        $property = Property::query()->firstOrFail();
        foreach (['properties/create', "properties/{$property->id}/edit"] as $path) {
            $html = $this->get('http://test.localhost/'.$path)->assertOk()->getContent();
            $document = new DOMDocument;
            @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
            $xpath = new DOMXPath($document);
            $this->assertSame(1, $xpath->query('//*[@id="property-basic-validation"]//*[@id="property-drawing-canvas"]')->length);
            $this->assertSame(1, $xpath->query('//*[@id="property-basic-validation"]//*[contains(@class,"assignment-properties-table-card")]//*[@name="name"]')->length);
            $this->assertSame(1, $xpath->query('//*[@id="property-location-validation"]//*[@id="property-location-map"]')->length);
            $this->assertSame(0, $xpath->query('//*[@id="property-location-validation"]//*[@id="property-drawing-canvas"]')->length);
            $this->assertSame(1, $xpath->query('//input[@name="google_maps_url" and @type="hidden"]')->length);
            $fields = json_decode(html_entity_decode($xpath->query('//*[@id="property-basic-validation"]')->item(0)->getAttribute('data-validation-fields')), true);
            $this->assertContains('aerial_image', $fields);
            $this->assertContains('boundary_geojson', $fields);
        }
    }

    public function test_draft_round_trip_keeps_location_image_and_drawn_boundary_and_updates_the_location(): void
    {
        $data = [
            'resource_key' => 'tenant.property-create', 'step' => 2,
            'latitude' => '24.7136000', 'longitude' => '46.6753000',
            'google_maps_url' => 'https://www.google.com/maps/search/?api=1&query=24.7136000,46.6753000',
            'aerial_image' => 'data:image/png;base64,annotated', 'raw_aerial_image' => 'data:image/png;base64,original',
            'boundary_geojson' => '{"type":"Polygon","coordinates":[[[10,10],[90,10],[90,90],[10,10]]]}',
        ];
        $draftId = $this->postJson('http://test.localhost/wizard-drafts', $data)->assertOk()->json('draft_id');
        $response = $this->getJson('http://test.localhost/wizard-drafts?resource_key=tenant.property-create')->assertOk();
        foreach (array_diff_key($data, array_flip(['resource_key', 'step'])) as $key => $value) {
            $response->assertJsonPath('data.'.$key, $value);
        }
        $this->postJson('http://test.localhost/wizard-drafts', [
            ...$data, 'latitude' => '0.0000000', 'longitude' => '47.0000000',
            'google_maps_url' => 'https://www.google.com/maps/search/?api=1&query=0.0000000,47.0000000',
        ])->assertOk()->assertJsonPath('draft_id', $draftId);
        $this->getJson('http://test.localhost/wizard-drafts?resource_key=tenant.property-create')
            ->assertOk()->assertJsonPath('data.latitude', '0.0000000')->assertJsonPath('data.longitude', '47.0000000');
    }

    public function test_short_link_endpoint_expands_google_links_and_rejects_other_hosts(): void
    {
        Http::fake(['https://maps.app.goo.gl/example' => Http::response('', 302, ['Location' => 'https://www.google.com/maps/?q=24.5,46.5'])]);
        $this->postJson('http://test.localhost/properties/resolve-maps-url', ['url' => 'https://maps.app.goo.gl/example'])
            ->assertOk()->assertJsonPath('data.url', 'https://www.google.com/maps/?q=24.5,46.5');
        $this->postJson('http://test.localhost/properties/resolve-maps-url', ['url' => 'https://evil.example/maps'])
            ->assertUnprocessable();
        Http::assertSentCount(1);
    }

    public function test_location_step_rejects_coordinates_outside_the_map(): void
    {
        $this->postJson('http://test.localhost/wizard-drafts/validate-step', [
            'target_path' => '/properties', 'target_method' => 'POST',
            'fields' => ['latitude', 'longitude'], 'latitude' => '91', 'longitude' => '181',
        ])->assertUnprocessable()->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    public function test_property_forms_allow_google_map_resources_in_their_content_security_policy(): void
    {
        $response = $this->get('http://test.localhost/properties/create')->assertOk();
        $policy = $response->headers->get('Content-Security-Policy');
        $directives = [];
        foreach (explode(';', $policy) as $directive) {
            [$name, $sources] = array_pad(explode(' ', trim($directive), 2), 2, '');
            $directives[$name] = $sources;
        }
        $this->assertStringContainsString('https://*.googleapis.com', $directives['connect-src']);
        $this->assertStringContainsString('https://*.googleapis.com', $directives['img-src']);
        $this->assertStringContainsString('blob:', $directives['worker-src'] ?? '');
    }

    public function test_an_unchanged_saved_image_can_be_submitted_when_only_the_location_is_edited(): void
    {
        $property = Property::query()->whereNotNull('aerial_image')->firstOrFail();
        $html = $this->get("http://test.localhost/properties/{$property->id}/edit")->assertOk()->getContent();
        $document = new DOMDocument;
        @$document->loadHTML($html);
        $image = (new DOMXPath($document))->query('//*[@id="property-aerial-image"]')->item(0)->getAttribute('value');
        $this->assertSame($property->aerial_image, $image);
        $this->postJson('http://test.localhost/wizard-drafts/validate-step', [
            'target_path' => '/properties/'.$property->id, 'target_method' => 'PUT',
            'fields' => ['aerial_image'], 'aerial_image' => $image,
        ])->assertOk();
    }

    public function test_edit_preserves_an_original_image_for_future_boundary_changes(): void
    {
        Storage::fake('tenant_public');
        $property = Property::query()->firstOrFail();
        $bytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        $updated = app(UpdatePropertyAction::class)->execute($property, [
            'latitude' => '25.1234567', 'longitude' => '47.1234567',
            'google_maps_url' => 'https://www.google.com/maps/search/?api=1&query=25.1234567,47.1234567',
            'raw_aerial_image' => 'data:image/png;base64,'.base64_encode($bytes),
        ]);
        $original = $updated->attachments->firstWhere('category', 'property_aerial_original');
        $this->assertNotNull($original);
        $this->assertSame($bytes, Storage::disk('tenant_public')->get($original->file_path));
        $this->assertSame('25.1234567', $updated->fresh()->latitude);
        $this->assertSame('47.1234567', $updated->fresh()->longitude);

        $html = view('tenant.properties._location-map-card', ['property' => $updated])->render();
        $document = new DOMDocument;
        @$document->loadHTML($html);
        $this->assertSame(tasset($original->file_path), (new DOMXPath($document))->query('//*[@id="property-raw-aerial-image"]')->item(0)->getAttribute('value'));
    }

    public function test_creation_saves_the_original_image_separately_from_the_annotation(): void
    {
        Storage::fake('tenant_public');
        $source = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=';
        $data = Property::query()->firstOrFail()->getAttributes();
        unset($data['id'], $data['created_at'], $data['updated_at'], $data['deleted_at'], $data['boundary_geojson']);
        $data['property_number'] = 'MAP-TEST-'.Str::uuid();
        $data['aerial_image'] = $source;
        $data['raw_aerial_image'] = $source;
        $property = app(CreatePropertyAction::class)->execute($data, (int) auth('tenant')->id());
        $original = $property->attachments->firstWhere('category', 'property_aerial_original');
        $this->assertNotNull($original);
        $this->assertNotSame($property->aerial_image, $original->file_path);
        Storage::disk('tenant_public')->assertExists($original->file_path);
        Storage::disk('tenant_public')->assertExists($property->aerial_image);
    }
}
