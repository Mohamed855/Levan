<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Enums\Tenants\Auctions\AuctionStatus;
use App\Http\Controllers\Tenants\Auctions\AuctionRegulatoryFormsController;
use App\Http\Requests\Tenants\AuctionClosings\SaveAuctionClosingRequest;
use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\Auctions\Forms\AuctionRegulatoryFormService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class AuctionClosingPartialStagedTest extends TestCase
{
    public function test_auction_closing_request_allows_partial_or_pending_results(): void
    {
        $request = new SaveAuctionClosingRequest;
        $rules = $request->rules();
        $file = UploadedFile::fake()->image('evidence.png');

        $data = [
            'closing_date' => '2026-11-12',
            'board_removal_date' => '2026-11-12',
            'results_narrative' => 'تفاصيل نتائج المزاد المنعقد',
            'board_images_after' => [
                1 => [$file],
            ],
            'results' => [
                1 => [
                    'status' => 'sold',
                    'buyer_name' => 'محمد أحمد',
                    'sale_price' => 500000,
                    'result_notes' => 'تمت الترسية بنجاح',
                    'opening_price' => 450000,
                    'participants_count' => 3,
                    'bids_count' => 10,
                ],
                2 => [
                    'status' => '',
                ],
            ],
        ];

        $request->files->set('board_images_after', [1 => [$file]]);
        $request->merge($data);
        $validator = Validator::make($data, $rules, $request->messages(), $request->attributes());
        foreach ($request->after() as $hook) {
            $validator->after($hook);
        }

        $this->assertFalse($validator->fails(), 'Validation should pass with partial/pending property results when at least one property is completed');
    }

    public function test_auction_closing_request_validates_board_removal_date_is_after_or_equal_closing_date(): void
    {
        $request = new SaveAuctionClosingRequest;
        $file = UploadedFile::fake()->image('evidence.png');

        $dataInvalid = [
            'closing_date' => '2026-11-15',
            'board_removal_date' => '2026-11-14', // before closing_date
            'results_narrative' => 'تفاصيل نتائج المزاد',
            'board_images_after' => [1 => [$file]],
            'results' => [
                1 => [
                    'status' => 'sold',
                    'buyer_name' => 'محمد أحمد',
                    'sale_price' => 500000,
                    'result_notes' => 'تمت الترسية',
                    'opening_price' => 450000,
                    'participants_count' => 3,
                    'bids_count' => 10,
                ],
            ],
        ];

        $request->merge($dataInvalid);
        $request->files->set('board_images_after', [1 => [$file]]);
        $validatorInvalid = Validator::make($dataInvalid, $request->rules(), $request->messages(), $request->attributes());
        foreach ($request->after() as $hook) {
            $validatorInvalid->after($hook);
        }

        $this->assertTrue($validatorInvalid->fails());
        $this->assertTrue($validatorInvalid->errors()->has('board_removal_date'));
        $this->assertSame('تاريخ فك اللوحات يجب أن يكون في نفس يوم تاريخ الإقفال أو بعده.', $validatorInvalid->errors()->first('board_removal_date'));

        $dataValid = $dataInvalid;
        $dataValid['board_removal_date'] = '2026-11-15'; // equal to closing_date
        $requestValid = new SaveAuctionClosingRequest;
        $requestValid->merge($dataValid);
        $requestValid->files->set('board_images_after', [1 => [$file]]);
        $validatorValid = Validator::make($dataValid, $requestValid->rules(), $requestValid->messages(), $requestValid->attributes());
        foreach ($requestValid->after() as $hook) {
            $validatorValid->after($hook);
        }

        $this->assertFalse($validatorValid->errors()->has('board_removal_date'));
    }

    public function test_auction_closing_request_validates_closing_date_is_between_auction_start_and_end_date(): void
    {
        $auction = new Auction;
        $auction->start_date = '2026-11-10';
        $auction->end_date = '2026-11-20';

        $route = new \Illuminate\Routing\Route('PUT', 'auction-closings/{auction}', []);
        $route->bind(request());
        $route->setParameter('auction', $auction);

        $file = UploadedFile::fake()->image('evidence.png');
        $baseData = [
            'board_removal_date' => '2026-11-25',
            'results_narrative' => 'تفاصيل نتائج المزاد',
            'board_images_after' => [1 => [$file]],
            'results' => [
                1 => [
                    'status' => 'sold',
                    'buyer_name' => 'محمد أحمد',
                    'sale_price' => 500000,
                    'result_notes' => 'تمت الترسية',
                    'opening_price' => 450000,
                    'participants_count' => 3,
                    'bids_count' => 10,
                ],
            ],
        ];

        // 1. Before start_date
        $dataBefore = array_merge($baseData, ['closing_date' => '2026-11-09']);
        $reqBefore = new SaveAuctionClosingRequest;
        $reqBefore->setRouteResolver(fn () => $route);
        $reqBefore->merge($dataBefore);
        $reqBefore->files->set('board_images_after', [1 => [$file]]);
        $valBefore = Validator::make($dataBefore, $reqBefore->rules(), $reqBefore->messages(), $reqBefore->attributes());
        foreach ($reqBefore->after() as $hook) {
            $valBefore->after($hook);
        }
        $this->assertTrue($valBefore->fails());
        $this->assertTrue($valBefore->errors()->has('closing_date'));
        $this->assertSame('تاريخ الإقفال يجب أن يكون في نفس يوم تاريخ بداية المزاد أو بعده.', $valBefore->errors()->first('closing_date'));

        // 2. After end_date
        $dataAfter = array_merge($baseData, ['closing_date' => '2026-11-21', 'board_removal_date' => '2026-11-25']);
        $reqAfter = new SaveAuctionClosingRequest;
        $reqAfter->setRouteResolver(fn () => $route);
        $reqAfter->merge($dataAfter);
        $reqAfter->files->set('board_images_after', [1 => [$file]]);
        $valAfter = Validator::make($dataAfter, $reqAfter->rules(), $reqAfter->messages(), $reqAfter->attributes());
        foreach ($reqAfter->after() as $hook) {
            $valAfter->after($hook);
        }
        $this->assertTrue($valAfter->fails());
        $this->assertTrue($valAfter->errors()->has('closing_date'));
        $this->assertSame('تاريخ الإقفال يجب ألا يتجاوز تاريخ نهاية المزاد.', $valAfter->errors()->first('closing_date'));

        // 3. Exactly on end_date (max allowable)
        $dataValidEnd = array_merge($baseData, ['closing_date' => '2026-11-20', 'board_removal_date' => '2026-11-20']);
        $reqValidEnd = new SaveAuctionClosingRequest;
        $reqValidEnd->setRouteResolver(fn () => $route);
        $reqValidEnd->merge($dataValidEnd);
        $reqValidEnd->files->set('board_images_after', [1 => [$file]]);
        $valValidEnd = Validator::make($dataValidEnd, $reqValidEnd->rules(), $reqValidEnd->messages(), $reqValidEnd->attributes());
        foreach ($reqValidEnd->after() as $hook) {
            $valValidEnd->after($hook);
        }
        $this->assertFalse($valValidEnd->errors()->has('closing_date'));
    }

    public function test_auction_closing_request_requires_board_removal_date_and_results_narrative(): void
    {
        $request = new SaveAuctionClosingRequest;
        $rules = $request->rules();

        $data = [
            'closing_date' => '2026-11-12',
            // board_removal_date omitted
            // results_narrative omitted
            'results' => [
                1 => [
                    'status' => 'sold',
                    'buyer_name' => 'محمد أحمد',
                    'sale_price' => 500000,
                    'result_notes' => 'ملاحظات',
                ],
            ],
        ];

        $validator = Validator::make($data, $rules, $request->messages(), $request->attributes());
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('board_removal_date'));
        $this->assertTrue($validator->errors()->has('results_narrative'));
        $this->assertSame('حقل تاريخ فك اللوحات مطلوب.', $validator->errors()->first('board_removal_date'));
        $this->assertSame('حقل تفاصيل نتائج المزاد مطلوب.', $validator->errors()->first('results_narrative'));
    }

    public function test_auction_closing_request_requires_result_notes_when_status_is_decided(): void
    {
        $request = new SaveAuctionClosingRequest;
        $rules = $request->rules();

        $dataSold = [
            'closing_date' => '2026-11-12',
            'board_removal_date' => '2026-11-12',
            'results_narrative' => 'تفاصيل نتائج المزاد',
            'results' => [
                1 => [
                    'status' => 'sold',
                    'buyer_name' => 'محمد أحمد',
                    'sale_price' => 500000,
                    'result_notes' => '',
                ],
            ],
        ];

        $validator = Validator::make($dataSold, $rules, $request->messages(), $request->attributes());
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('results.1.result_notes'));
        $this->assertSame('حقل ملاحظات النتيجة مطلوب للعقار المحدد نتيجته.', $validator->errors()->first('results.1.result_notes'));

        $dataUnsold = [
            'closing_date' => '2026-11-12',
            'board_removal_date' => '2026-11-12',
            'results_narrative' => 'تفاصيل نتائج المزاد',
            'results' => [
                1 => [
                    'status' => 'unsold',
                    'unsold_reason' => 'عدم وصول السعر للقيمة العادلة',
                    'result_notes' => '',
                ],
            ],
        ];

        $validator2 = Validator::make($dataUnsold, $rules, $request->messages(), $request->attributes());
        $this->assertTrue($validator2->fails());
        $this->assertTrue($validator2->errors()->has('results.1.result_notes'));
    }

    public function test_auction_closing_request_fails_if_no_properties_are_completed(): void
    {
        $request = new SaveAuctionClosingRequest;
        $data = [
            'closing_date' => '2026-11-12',
            'board_removal_date' => '2026-11-12',
            'results_narrative' => 'تفاصيل نتائج المزاد',
            'results' => [
                1 => ['status' => ''],
                2 => ['status' => 'pending'],
            ],
        ];

        $request->merge($data);
        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());
        foreach ($request->after() as $hook) {
            $validator->after($hook);
        }

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('results'));
    }

    public function test_auction_closing_request_requires_board_images_for_completed_property(): void
    {
        $request = new SaveAuctionClosingRequest;
        $data = [
            'closing_date' => '2026-11-12',
            'board_removal_date' => '2026-11-12',
            'results_narrative' => 'تفاصيل نتائج المزاد',
            // No board_images_after provided
            'results' => [
                1 => [
                    'status' => 'sold',
                    'buyer_name' => 'محمد أحمد',
                    'sale_price' => 500000,
                    'result_notes' => 'تم البيع',
                    'opening_price' => 450000,
                    'participants_count' => 3,
                    'bids_count' => 10,
                ],
            ],
        ];

        $request->merge($data);
        $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());
        foreach ($request->after() as $hook) {
            $validator->after($hook);
        }

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('board_images_after.1'));
        $this->assertSame('صور فك اللوحات مطلوبة للعقار.', $validator->errors()->first('board_images_after.1'));
        $this->assertTrue($validator->errors()->has('results'));
    }

    public function test_auction_closing_request_provides_arabic_attributes_and_messages(): void
    {
        $request = new SaveAuctionClosingRequest;
        $attributes = $request->attributes();

        $this->assertSame('تاريخ فك اللوحات', $attributes['board_removal_date']);
        $this->assertSame('تاريخ الإقفال', $attributes['closing_date']);
        $this->assertSame('صور فك اللوحات', $attributes['board_images_after']);
        $this->assertSame('قيمة الترسية', $attributes['results.*.sale_price']);

        // Check validation failure message format in Arabic
        $validator = Validator::make(
            ['closing_date' => 'invalid-date', 'board_removal_date' => 'invalid-date'],
            $request->rules(),
            $request->messages(),
            $request->attributes()
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('تاريخ فك اللوحات', $validator->errors()->first('board_removal_date'));
        $this->assertStringNotContainsString('board removal date', $validator->errors()->first('board_removal_date'));
    }

    public function test_auction_closing_request_requires_sold_fields_when_status_is_sold(): void
    {
        $request = new SaveAuctionClosingRequest;
        $rules = $request->rules();

        $data = [
            'closing_date' => '2026-11-12',
            'board_removal_date' => '2026-11-12',
            'results' => [
                1 => [
                    'status' => 'sold',
                    'buyer_name' => '',
                    'sale_price' => null,
                ],
            ],
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('results.1.buyer_name'));
        $this->assertTrue($validator->errors()->has('results.1.sale_price'));
    }

    public function test_auction_closing_request_requires_unsold_reason_when_status_is_unsold(): void
    {
        $request = new SaveAuctionClosingRequest;
        $rules = $request->rules();

        $data = [
            'closing_date' => '2026-11-12',
            'board_removal_date' => '2026-11-12',
            'results' => [
                1 => [
                    'status' => 'unsold',
                    'unsold_reason' => '',
                ],
            ],
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('results.1.unsold_reason'));
    }

    public function test_auction_status_only_transitions_to_closed_when_all_properties_have_decided_outcomes(): void
    {
        $auction = $this->makeAuctionWithResults(['sold', null]);
        $this->assertSame(AuctionStatus::APPROVED, $auction->status);
        $this->assertFalse($auction->isClosed());

        $auction = $this->makeAuctionWithResults(['sold', 'pending']);
        $this->assertSame(AuctionStatus::APPROVED, $auction->status);
        $this->assertFalse($auction->isClosed());

        $auction = $this->makeAuctionWithResults(['sold', 'unsold']);
        $this->assertSame(AuctionStatus::CLOSED, $auction->status);
        $this->assertTrue($auction->isClosed());

        $auction = $this->makeAuctionWithResults(['unsold', 'unsold']);
        $this->assertSame(AuctionStatus::CLOSED, $auction->status);
        $this->assertTrue($auction->isClosed());

        $auction = $this->makeAuctionWithResults(['sold', 'sold']);
        $this->assertSame(AuctionStatus::CLOSED, $auction->status);
        $this->assertTrue($auction->isClosed());
    }

    public function test_regulatory_forms_view_always_renders_both_cards_with_appropriate_actionable_states_and_alerts(): void
    {
        $mockService = $this->createMock(AuctionRegulatoryFormService::class);
        $mockService->method('latest')->willReturn(null);
        $mockService->method('published')->willReturn(null);
        $this->app->instance(AuctionRegulatoryFormService::class, $mockService);

        $auction = new Auction;
        $auction->id = 99;
        $auction->setRelation('properties', new Collection);
        $auction->setRelation('establishmentRequest', null);
        $auction->setRelation('closing', null);

        // 1. Missing establishment request: both cards rendered, establishment disabled with save alert
        $html = view('tenant.auctions.partials.regulatory-forms', ['auction' => $auction])->render();
        $this->assertStringContainsString('data-regulatory-form="establishment"', $html);
        $this->assertStringContainsString('data-regulatory-form="closing"', $html);
        $this->assertStringContainsString('data-actionable="0"', $html);
        $this->assertStringContainsString('يرجى إكمال طلب إقامة المزاد لتوليد النموذج.', $html);
        $this->assertStringContainsString('يرجى اعتماد طلب إقامة المزاد أولاً لتتمكن من إقفال المزاد وتوليد النموذج.', $html);

        // 2. Unapproved establishment request: establishment is actionable, closing disabled with approval alert
        $pendingRequest = new class extends Model
        {
            public array $data = ['properties' => []];

            public function isApproved(): bool
            {
                return false;
            }
        };
        $pendingRequest->exists = true;
        $auction->setRelation('establishmentRequest', $pendingRequest);

        $html2 = view('tenant.auctions.partials.regulatory-forms', ['auction' => $auction])->render();
        $this->assertStringContainsString('data-regulatory-form="establishment"', $html2);
        $this->assertStringContainsString('يرجى اعتماد طلب إقامة المزاد أولاً لتتمكن من إقفال المزاد وتوليد النموذج.', $html2);

        // 3. Approved request, but closing not complete: closing card has 'برجاء إكمال الإقفال لتوليد النموذج.' alert
        $approvedRequest = new class extends Model
        {
            public array $data = ['properties' => []];

            public function isApproved(): bool
            {
                return true;
            }
        };
        $approvedRequest->exists = true;
        $auction->setRelation('establishmentRequest', $approvedRequest);

        $html3 = view('tenant.auctions.partials.regulatory-forms', ['auction' => $auction])->render();
        $this->assertStringContainsString('برجاء إكمال الإقفال لتوليد النموذج.', $html3);

        // 4. Fully closed auction with saved closing: closing becomes actionable (data-actionable="1")
        $closing = new class extends Model
        {
            public string $saved_at = '2026-11-12 10:00:00';
        };
        $auction->setRelation('closing', $closing);
        $auction->setRelation('properties', new Collection(array_map(static function ($status): Model {
            $property = new class extends Model {};
            $property->setRelation('pivot', new class(['result_status' => $status]) extends Model
            {
                protected $guarded = [];
            });

            return $property;
        }, ['sold'])));

        $html4 = view('tenant.auctions.partials.regulatory-forms', ['auction' => $auction])->render();
        $this->assertStringNotContainsString('برجاء إكمال الإقفال لتوليد النموذج.', $html4);
        $this->assertStringContainsString('data-actionable="1"', $html4);
    }

    public function test_auction_regulatory_forms_controller_blocks_unclosed_closing_generation(): void
    {
        $controller = app(AuctionRegulatoryFormsController::class);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('authorizeForm');
        $method->setAccessible(true);

        $user = new class extends User {};
        $this->actingAs($user);
        Gate::before(fn () => true);

        $auction = new Auction;
        $auction->id = 1;
        $request = new class extends Model
        {
            public array $data = ['properties' => []];

            public function isApproved(): bool
            {
                return true;
            }
        };
        $request->exists = true;
        $auction->setRelation('establishmentRequest', $request);
        $auction->setRelation('closing', new class extends Model
        {
            public string $saved_at = '2026-11-12 10:00:00';
        });
        // Auction properties empty or pending outcome -> isClosed() is false
        $auction->setRelation('properties', new Collection);

        $this->assertFalse($auction->isClosed());

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('');
        $method->invoke($controller, $auction, 'closing', 'view');
    }

    private function makeAuctionWithResults(array $results): Auction
    {
        $auction = new Auction;
        $request = new class extends Model
        {
            public bool $approved = true;

            public function isApproved(): bool
            {
                return $this->approved;
            }
        };
        $auction->setRelation('establishmentRequest', $request);
        $auction->setRelation('properties', new Collection(array_map(static function ($status): Model {
            $property = new class extends Model {};
            $property->setRelation('pivot', new class(['result_status' => $status]) extends Model
            {
                protected $guarded = [];
            });

            return $property;
        }, $results)));

        return $auction;
    }
}
