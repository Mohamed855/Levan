<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Actions\Tenants\Auctions\CreateAuctionAction;
use App\Http\Controllers\Shared\Wizard\WizardDraftController;
use App\Http\Controllers\Tenants\Auctions\AuctionsController;
use App\Http\Requests\Tenants\Auctions\StoreAuctionRequest;
use App\Http\Requests\Tenants\Properties\StorePropertyRequest;
use App\Services\Shared\Wizard\WizardDraftService;
use DOMDocument;
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
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
}
