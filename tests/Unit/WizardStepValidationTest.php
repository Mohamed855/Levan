<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Actions\Tenants\Auctions\CreateAuctionAction;
use App\Http\Controllers\Shared\Wizard\WizardDraftController;
use App\Http\Controllers\Tenants\Auctions\AuctionsController;
use App\Http\Requests\Admin\Clients\UpdateClientRequest;
use App\Http\Requests\Admin\Subscriptions\StoreSubscriptionRequest;
use App\Http\Requests\Tenants\Auctions\StoreAuctionRequest;
use App\Http\Requests\Tenants\Auctions\UpdateAuctionRequest;
use App\Http\Requests\Tenants\Properties\StorePropertyRequest;
use App\Http\Requests\Tenants\Properties\UpdatePropertyRequest;
use App\Services\Shared\Wizard\WizardDraftService;
use DOMDocument;
use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator as ValidationValidator;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

final class WizardStepValidationTest extends TestCase
{
    public function test_step_field_contract_is_json_in_the_browser(): void
    {
        $html = Blade::render('<x-form.wizard.step id="ownership" :fields="[\'owners\']">Fields</x-form.wizard.step>');
        $document = new DOMDocument;
        @$document->loadHTML($html);
        $fields = $document->getElementsByTagName('div')->item(0)->getAttribute('data-validation-fields');

        $this->assertSame(['owners'], json_decode($fields, true));
    }

    public function test_owner_step_validates_each_owner_name_and_identity(): void
    {
        $controller = new WizardDraftController(app(WizardDraftService::class));
        $method = new ReflectionMethod($controller, 'rulesForFields');
        $rules = $method->invoke($controller, (new StorePropertyRequest)->rules(), ['owners[0][owner_name]', 'owners[0][national_id_or_cr]']);
        $validator = Validator::make(['owners' => [['owner_name' => '', 'national_id_or_cr' => '']]], $rules);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('owners.0.owner_name', $validator->errors()->toArray());
        $this->assertArrayHasKey('owners.0.national_id_or_cr', $validator->errors()->toArray());
    }

    public function test_stepper_exposes_nested_server_errors_and_only_the_failed_draft_marker(): void
    {
        $errors = (new ViewErrorBag)->put('default', new MessageBag(['owners.0.owner_name' => ['Required']]));
        view()->share('errors', $errors);
        session()->flash('_wizard_failed_draft', ['id' => 'recovery-id', 'resource_key' => 'tenant.property-create']);
        $html = Blade::render('<x-form.wizard.stepper id="recovery" :steps="$steps">Form</x-form.wizard.stepper>', [
            'steps' => [['id' => 'one'], ['id' => 'two']],
        ]);
        $document = new DOMDocument;
        @$document->loadHTML($html);
        $wizard = $document->getElementById('recovery');

        $this->assertSame(['owners.0.owner_name' => ['Required']], json_decode($wizard->getAttribute('data-validation-errors'), true));
        $this->assertSame('recovery-id', $wizard->getAttribute('data-failed-draft-id'));
        $this->assertSame('tenant.property-create', $wizard->getAttribute('data-failed-draft-resource'));
    }

    public function test_auction_action_validation_keeps_field_errors_for_wizard_recovery(): void
    {
        $request = Mockery::mock(StoreAuctionRequest::class);
        $request->shouldReceive('validated')->andReturn(['property_results' => [['property_id' => 1]]]);
        $request->shouldReceive('user')->with('tenant')->andReturn(new GenericUser(['id' => 1]));
        $request->shouldReceive('file')->andReturn(null, [], null, [], [], []);
        $this->expectException(ValidationException::class);
        app(AuctionsController::class)->store($request, app(CreateAuctionAction::class));
    }

    public function test_validate_step_executes_with_validator_and_after_hooks_scoped_to_requested_fields(): void
    {
        Route::put('/_test/wizard-hook-step/{id}', [WizardStepHookStubController::class, 'update']);
        Route::post('/_test/wizard-hook-validate', [WizardDraftController::class, 'validateStep']);

        // Step 1: unrelated errors from withValidator() (general_documents) and after() (data.properties / data.disclosures) must be filtered out
        $this->postJson('/_test/wizard-hook-validate', [
            'target_path' => '/_test/wizard-hook-step/10',
            'target_method' => 'PUT',
            'fields' => ['name'],
            'name' => 'Valid Name',
            'trigger_with_validator_error' => true,
            'trigger_after_property_error' => true,
            'trigger_after_disclosure_error' => true,
        ])->assertOk()->assertJsonPath('success', true);

        // Step 3 (withValidator): general_documents error must fail step validation
        $this->postJson('/_test/wizard-hook-validate', [
            'target_path' => '/_test/wizard-hook-step/10',
            'target_method' => 'PUT',
            'fields' => ['general_documents'],
            'trigger_with_validator_error' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors(['general_documents']);

        // Step 3 (after): nested property error must fail step validation without leaking disclosure errors
        $propertyResponse = $this->postJson('/_test/wizard-hook-validate', [
            'target_path' => '/_test/wizard-hook-step/10',
            'target_method' => 'PUT',
            'fields' => ['data.properties'],
            'trigger_after_property_error' => true,
            'trigger_after_disclosure_error' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors(['data.properties.0.entry_amount']);
        $this->assertArrayNotHasKey('data.disclosures.disposition_rights', $propertyResponse->json('errors'));

        // Step 4 (after): disclosure error must fail step validation without leaking property errors
        $disclosureResponse = $this->postJson('/_test/wizard-hook-validate', [
            'target_path' => '/_test/wizard-hook-step/10',
            'target_method' => 'PUT',
            'fields' => ['data.disclosures'],
            'trigger_after_property_error' => true,
            'trigger_after_disclosure_error' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors(['data.disclosures.disposition_rights']);
        $this->assertArrayNotHasKey('data.properties.0.entry_amount', $disclosureResponse->json('errors'));
    }

    public function test_property_step_three_and_subscription_step_two_declare_all_validation_fields(): void
    {
        $propertyForm = file_get_contents(resource_path('views/tenant/properties/partials/_form.blade.php'));
        $this->assertIsString($propertyForm);
        foreach (['agent_name', 'agent_national_id', 'agency_number', 'agency_date', 'units'] as $field) {
            $this->assertStringContainsString("'{$field}'", $propertyForm);
        }

        $controller = new WizardDraftController(app(WizardDraftService::class));
        $rulesForFields = new ReflectionMethod($controller, 'rulesForFields');
        foreach ([new StorePropertyRequest, new UpdatePropertyRequest] as $request) {
            $rules = $rulesForFields->invoke($controller, $request->rules(), [
                'agent_name',
                'agent_national_id',
                'agency_number',
                'agency_date',
                'units',
            ]);
            $this->assertArrayHasKey('agent_national_id', $rules);
            $this->assertArrayHasKey('agency_number', $rules);
            $this->assertArrayHasKey('units.*.area', $rules);

            $validator = Validator::make([
                'agent_national_id' => 'not-digits',
                'units' => [['area' => -5]],
            ], $rules);
            $this->assertTrue($validator->fails());
            $this->assertTrue($validator->errors()->has('agent_national_id'));
            $this->assertTrue($validator->errors()->has('units.0.area'));
        }

        $subscriptionForm = file_get_contents(resource_path('views/admin/subscriptions/partials/_form.blade.php'));
        $this->assertIsString($subscriptionForm);
        $this->assertStringContainsString("'admin_notes'", $subscriptionForm);

        $subRules = $rulesForFields->invoke($controller, (new StoreSubscriptionRequest)->rules(), ['admin_notes']);
        $this->assertArrayHasKey('admin_notes', $subRules);
        $this->assertTrue(Validator::make(['admin_notes' => str_repeat('a', 1001)], $subRules)->fails());
    }

    public function test_update_property_request_requires_boundaries_and_owners_like_create(): void
    {
        $rules = Arr::only((new UpdatePropertyRequest)->rules(), [
            'boundaries',
            'boundaries.*.direction',
            'boundaries.*.border_type',
            'boundaries.*.border_description',
            'boundaries.*.length',
            'owners',
            'owners.*.owner_name',
            'owners.*.national_id_or_cr',
        ]);

        $validator = Validator::make([
            'boundaries' => [['direction' => '', 'border_type' => '', 'border_description' => '', 'length' => '']],
            'owners' => [['owner_name' => '', 'national_id_or_cr' => '']],
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('boundaries.0.direction'));
        $this->assertTrue($validator->errors()->has('boundaries.0.border_description'));
        $this->assertTrue($validator->errors()->has('boundaries.0.length'));
        $this->assertTrue($validator->errors()->has('owners.0.owner_name'));
        $this->assertTrue($validator->errors()->has('owners.0.national_id_or_cr'));
    }

    public function test_update_auction_request_requires_step_one_fields_and_normalizes_mysql_time(): void
    {
        $request = UpdateAuctionRequest::create('/auctions/5', 'PUT', [
            'description' => '',
            'start_time' => '',
            'end_time' => '',
            'expected_properties_count' => '',
        ]);
        $rules = Arr::only($request->rules(), ['description', 'start_time', 'end_time', 'expected_properties_count']);
        $validator = Validator::make($request->all(), $rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('description'));
        $this->assertTrue($validator->errors()->has('start_time'));
        $this->assertTrue($validator->errors()->has('end_time'));
        $this->assertTrue($validator->errors()->has('expected_properties_count'));

        // MySQL TIME values (H:i:s) normalize cleanly to H:i during prepareForValidation()
        $timeRequest = UpdateAuctionRequest::create('/auctions/5', 'PUT', [
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-01',
            'start_time' => '16:00:00',
            'end_time' => '18:30:00',
        ]);
        (new ReflectionMethod($timeRequest, 'prepareForValidation'))->invoke($timeRequest);
        $this->assertSame('16:00', $timeRequest->input('start_time'));
        $this->assertSame('18:30', $timeRequest->input('end_time'));

        $timeValidator = Validator::make(
            $timeRequest->all(),
            Arr::only($timeRequest->rules(), ['start_time', 'end_time']),
        );
        $this->assertTrue($timeValidator->passes(), $timeValidator->errors()->toJson());

        // Blade step 1 matches nullable supervising_entity
        $step1Blade = file_get_contents(resource_path('views/tenant/auctions/partials/steps/step1-basic-info.blade.php'));
        $this->assertIsString($step1Blade);
        $this->assertDoesNotMatchRegularExpression('/name="supervising_entity"[^>]*\brequired\b/', $step1Blade);
    }

    public function test_update_client_request_allows_empty_readonly_tax_number_while_enforcing_format(): void
    {
        $rules = Arr::only((new UpdateClientRequest)->rules(), ['tax_number']);

        $this->assertTrue(Validator::make(['tax_number' => null], $rules)->passes());
        $this->assertTrue(Validator::make(['tax_number' => ''], $rules)->passes());
        $this->assertTrue(Validator::make(['tax_number' => '300012345600003'], $rules)->passes());
        $this->assertTrue(Validator::make(['tax_number' => '12345'], $rules)->fails());
    }
}

final class WizardStepHookStubRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'general_documents' => ['nullable', 'array'],
            'data.properties' => ['nullable', 'array'],
            'data.disclosures' => ['nullable', 'array'],
        ];
    }

    public function withValidator(ValidationValidator $validator): void
    {
        $validator->after(function (ValidationValidator $validator): void {
            if ($this->boolean('trigger_with_validator_error')) {
                $validator->errors()->add('general_documents', 'يجب الإبقاء على مستند عام واحد على الأقل أو رفع مستند جديد.');
            }
        });
    }

    public function after(?object $optionalBoundParam = null): array
    {
        return [function (ValidationValidator $validator): void {
            if ($this->boolean('trigger_after_property_error')) {
                $validator->errors()->add('data.properties.0.entry_amount', 'مبلغ الدخول لا يمكن أن يكون أكبر من القيمة التقديرية للعقار.');
            }
            if ($this->boolean('trigger_after_disclosure_error')) {
                $validator->errors()->add('data.disclosures.disposition_rights', 'لا يمكن جمع عدم وجود حقوق مع حقوق أخرى.');
            }
        }];
    }
}

final class WizardStepHookStubController
{
    public function update(WizardStepHookStubRequest $request): void {}
}
