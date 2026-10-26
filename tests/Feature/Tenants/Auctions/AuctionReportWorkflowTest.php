<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Actions\Tenants\Auctions\CreateAuctionAction;
use App\Actions\Tenants\Auctions\UpdateAuctionAction;
use App\Http\Requests\Tenants\Auctions\StoreAuctionRequest;
use App\Http\Requests\Tenants\Auctions\UpdateAuctionRequest;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Tests\Support\TenantAuctionReportTestCase;

final class AuctionReportWorkflowTest extends TenantAuctionReportTestCase
{
    public function test_final_create_requires_an_independent_report_image(): void
    {
        $assignment = $this->assignment('CREATE');
        $request = StoreAuctionRequest::create('/auctions', 'POST', $this->validPayload($assignment));

        $validator = Validator::make($request->all(), $request->rules());

        self::assertTrue($validator->errors()->has('report_image'));
    }

    public function test_legacy_update_requires_report_image_but_a_retained_image_is_accepted(): void
    {
        $assignment = $this->assignment('UPDATE');
        $auction = $this->auction($assignment);

        $missingImageValidator = $this->updateValidator($auction, $this->validPayload($assignment));
        self::assertTrue($missingImageValidator->errors()->has('report_image'));

        $auction->setAttribute('report_image', 'auctions/reports/existing.webp');
        $retainedImageValidator = $this->updateValidator($auction, $this->validPayload($assignment));
        self::assertFalse($retainedImageValidator->errors()->has('report_image'));
    }

    public function test_template_selection_is_not_part_of_the_active_auction_payload(): void
    {
        $assignment = $this->assignment('NO-TEMPLATES');
        $payload = $this->validPayload($assignment) + [
            'report_image' => UploadedFile::fake()->image('report.webp'),
            'template_versions' => ['brochure' => 10],
            'brochure_template_id' => 10,
            'billboard_template_id' => 11,
            'social_template_id' => 12,
        ];
        $request = StoreAuctionRequest::create('/auctions', 'POST', $payload);
        $validator = Validator::make($request->all(), $request->rules());

        self::assertTrue($validator->passes(), (string) $validator->errors());
        self::assertSame([], array_intersect_key($validator->validated(), array_flip([
            'template_versions',
            'brochure_template_id',
            'billboard_template_id',
            'social_template_id',
        ])));
    }

    public function test_property_results_reject_a_property_outside_the_selected_assignment(): void
    {
        $assignment = $this->assignment('RESULTS-A');
        $otherAssignment = $this->assignment('RESULTS-B');
        $linkedProperty = $this->property($assignment, 'LINKED');
        $foreignProperty = $this->property($otherAssignment, 'FOREIGN');
        $payload = $this->validPayload($assignment) + [
            'report_image' => UploadedFile::fake()->image('report.webp'),
            'property_ids' => [$linkedProperty->id],
            'property_results' => [
                $foreignProperty->id => [
                    'status' => 'sold',
                    'opening_price' => 100000,
                    'sale_price' => 125000,
                    'participants_count' => 4,
                    'bids_count' => 9,
                ],
            ],
        ];
        $request = StoreAuctionRequest::create('/auctions', 'POST', $payload);
        $validator = Validator::make($request->all(), $request->rules());

        self::assertTrue($validator->fails());
        self::assertTrue($validator->errors()->has('property_results'));
    }

    public function test_only_the_two_new_report_routes_are_exposed(): void
    {
        self::assertFalse(RouteFacade::has('path.auctions.preview'));
        self::assertFalse(RouteFacade::has('path.auctions.brochure'));
        self::assertFalse(RouteFacade::has('path.auctions.billboard'));
        self::assertFalse(RouteFacade::has('path.auctions.social'));
        self::assertFalse(RouteFacade::has('path.auctions.social-image'));
        self::assertTrue(RouteFacade::has('path.auctions.reports.download'));
        self::assertTrue(RouteFacade::has('path.auctions.reports.retry'));
    }

    public function test_create_action_persists_report_inputs_and_scoped_evidence(): void
    {
        Storage::fake('tenant_public');

        $assignment = $this->assignment('PERSIST');
        $property = $this->property($assignment, 'PERSIST');
        $payload = $this->validPayload($assignment) + [
            'property_ids' => [$property->id],
            'property_results' => [
                $property->id => [
                    'status' => 'sold',
                    'opening_price' => 100000,
                    'sale_price' => 125000,
                    'participants_count' => 4,
                    'bids_count' => 9,
                    'board_location' => 'واجهة العقار',
                    'board_notes' => 'لوحة رئيسية',
                ],
            ],
            'marketing' => [
                'campaigns' => [[
                    'platform' => 'instagram',
                    'reach' => 1200,
                    'impressions' => 2400,
                    'engagements' => 130,
                    'clicks' => 45,
                    'url' => 'https://example.com/campaign',
                ]],
                'links' => [[
                    'label' => 'صفحة الحملة',
                    'url' => 'https://example.com/landing',
                ]],
            ],
        ];

        try {
            $auction = app(CreateAuctionAction::class)->execute(
                $payload,
                null,
                null,
                [],
                UploadedFile::fake()->image('report.webp'),
                [UploadedFile::fake()->image('marketing.webp')],
                [$property->id => [UploadedFile::fake()->image('before.webp')]],
                [$property->id => [UploadedFile::fake()->image('after.webp')]],
            );
        } catch (\Throwable $exception) {
            self::fail('The create action did not accept the report input contract: '.$exception->getMessage());
        }

        self::assertNotNull($auction->report_image);
        Storage::disk('tenant_public')->assertExists($auction->report_image);
        self::assertSame('instagram', $auction->marketing_data['campaigns'][0]['platform']);

        $pivot = $auction->properties()->whereKey($property->id)->firstOrFail()->pivot;
        self::assertSame('sold', $pivot->result_status);
        self::assertSame('125000.00', $pivot->sale_price);
        self::assertSame('واجهة العقار', json_decode((string) $pivot->board_data, true)['location']);

        self::assertSame(
            ['board_after', 'board_before', 'marketing_evidence'],
            $auction->attachments()->orderBy('report_section')->pluck('report_section')->all(),
        );
        self::assertSame(
            [$property->id, $property->id],
            $auction->attachments()->whereIn('report_section', ['board_before', 'board_after'])
                ->orderBy('report_section')->pluck('property_id')->all(),
        );
    }

    public function test_http_create_stores_the_required_report_image(): void
    {
        Storage::fake('tenant_public');

        $owner = User::query()->where('email', 'demo@levan.com')->firstOrFail();
        $property = Property::query()->whereNotNull('assignment_id')->firstOrFail();
        $assignment = Assignment::query()->findOrFail($property->assignment_id);
        $payload = $this->validPayload($assignment) + [
            'name' => 'مزاد HTTP '.uniqid(),
            'report_image' => UploadedFile::fake()->image('http-report.webp'),
            'property_ids' => [$property->id],
        ];

        $response = $this->actingAs($owner, 'tenant')
            ->post('http://test.localhost/auctions', $payload);

        $response->assertSessionHasNoErrors();
        $auction = Auction::query()->where('name', $payload['name'])->latest('id')->firstOrFail();
        $response->assertRedirect('http://test.localhost/auctions/'.$auction->id);
        self::assertNotNull($auction->report_image);
        Storage::disk('tenant_public')->assertExists($auction->report_image);
    }

    public function test_report_queue_failure_does_not_rollback_a_successful_auction_save(): void
    {
        Storage::fake('tenant_public');
        config(['auction-renderer.queue_connection' => 'missing-auction-report-connection']);

        $assignment = $this->assignment('QUEUE-FAILURE');

        $auction = app(CreateAuctionAction::class)->execute(
            $this->validPayload($assignment),
            null,
            null,
            [],
            UploadedFile::fake()->image('report.webp'),
        );

        self::assertTrue($auction->exists);
        self::assertDatabaseHas('auctions', ['id' => $auction->id, 'report_image' => $auction->report_image]);
        Storage::disk('tenant_public')->assertExists($auction->report_image);
    }

    public function test_user_with_view_permission_cannot_retry_report_generation(): void
    {
        $assignment = $this->assignment('RETRY-AUTH');
        $auction = $this->auction($assignment);
        $owner = User::query()->where('email', 'demo@levan.com')->firstOrFail();
        $permission = Permission::query()->firstOrCreate([
            'name' => 'auctions.view',
            'guard_name' => 'tenant',
        ]);
        $viewer = User::query()->create([
            'name' => 'مشاهد تقارير المزاد',
            'employee_number' => 'REPORT-VIEWER-'.uniqid(),
            'email' => 'report-viewer-'.uniqid().'@levan.test',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
            'license_seat_id' => $owner->license_seat_id,
        ]);
        $viewer->givePermissionTo($permission);

        $this->actingAs($viewer, 'tenant')
            ->postJson('http://test.localhost/auctions/'.$auction->id.'/reports/retry')
            ->assertForbidden();
    }

    public function test_update_retains_or_replaces_report_image_without_losing_results(): void
    {
        Storage::fake('tenant_public');

        $assignment = $this->assignment('REPLACE');
        $property = $this->property($assignment, 'REPLACE');
        $auction = $this->auction($assignment);
        $oldReportPath = 'auctions/report-images/old.webp';
        Storage::disk('tenant_public')->put($oldReportPath, 'old-report');
        $auction->update(['report_image' => $oldReportPath, 'project_manager_name' => 'المدير القديم']);
        $auction->properties()->sync([
            $property->id => [
                'sort_order' => 1,
                'result_status' => 'sold',
                'opening_price' => 100000,
                'sale_price' => 125000,
                'participants_count' => 4,
                'bids_count' => 9,
            ],
        ]);

        $payload = $this->validPayload($assignment) + ['property_ids' => [$property->id]];
        $retained = app(UpdateAuctionAction::class)->execute($auction, $payload, null, []);
        self::assertSame($oldReportPath, $retained->report_image);
        Storage::disk('tenant_public')->assertExists($oldReportPath);
        self::assertSame('sold', $retained->properties->firstOrFail()->pivot->result_status);

        $replaced = app(UpdateAuctionAction::class)->execute(
            auction: $retained,
            userOrData: $payload,
            dataOrCover: null,
            coverOrAttachments: [],
            reportImage: UploadedFile::fake()->image('new-report.webp'),
        );

        self::assertNotSame($oldReportPath, $replaced->report_image);
        Storage::disk('tenant_public')->assertMissing($oldReportPath);
        Storage::disk('tenant_public')->assertExists($replaced->report_image);
        self::assertSame('sold', $replaced->properties->firstOrFail()->pivot->result_status);
    }

    private function updateValidator(Auction $auction, array $payload): \Illuminate\Contracts\Validation\Validator
    {
        $request = UpdateAuctionRequest::create('/auctions/'.$auction->id, 'PUT', $payload);
        $route = new Route('PUT', 'auctions/{auction}', static fn (): null => null);
        $route->bind($request);
        $route->setParameter('auction', $auction);
        $request->setRouteResolver(static fn (): Route => $route);

        return Validator::make($request->all(), $request->rules());
    }

    private function validPayload(Assignment $assignment): array
    {
        return [
            'name' => 'مزاد اختبار التقارير',
            'description' => 'وصف حقيقي لاختبار مسار تقارير المزاد.',
            'start_date' => now()->addDay()->toDateString(),
            'start_time' => '16:00',
            'end_date' => now()->addDays(2)->toDateString(),
            'end_time' => '18:00',
            'type' => 'electronic',
            'category' => 'lands',
            'city' => 'الرياض',
            'expected_properties_count' => 1,
            'assignment_id' => $assignment->id,
            'project_manager_name' => 'مدير المشروع',
            'platform_name' => 'منصة المزاد',
        ];
    }

    private function assignment(string $suffix): Assignment
    {
        return Assignment::query()->create([
            'assignment_number' => 'REPORT-'.$suffix.'-'.uniqid(),
            'name' => 'إسناد '.$suffix,
            'issuing_entity' => 'جهة اختبار',
            'is_active' => true,
        ]);
    }

    private function property(Assignment $assignment, string $suffix): Property
    {
        return Property::query()->create([
            'assignment_id' => $assignment->id,
            'property_number' => 'PROP-'.$suffix.'-'.uniqid(),
            'name' => 'عقار '.$suffix,
            'type' => 'residential_land',
            'city' => 'الرياض',
            'area' => 100,
            'estimated_value' => 100000,
        ]);
    }

    private function auction(Assignment $assignment): Auction
    {
        return Auction::query()->create([
            'assignment_id' => $assignment->id,
            'name' => 'مزاد قديم بلا صورة تقرير',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'type' => 'electronic',
            'category' => 'lands',
            'city' => 'الرياض',
            'status' => 'in_preparation',
            'is_active' => false,
        ]);
    }
}
