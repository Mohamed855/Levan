<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Actions\Tenants\GeneralSetting\GeneralSettingAction;
use App\Actions\Tenants\GeneralSetting\MessageTemplateAction;
use App\Http\Controllers\Tenants\GeneralSetting\MessageTemplate\MessageTemplateController;
use App\Http\Controllers\Tenants\Traits\CommonSettingController;
use App\Models\Tenants\UserMangment\User\User;
use App\Policies\Tenants\GeneralSetting\CountryPolicy;
use App\Policies\Tenants\GeneralSetting\CustomerStatusPolicy;
use App\Policies\Tenants\GeneralSetting\LookupDeletePolicy;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class GeneralSettingsGoldenPatternContractTest extends TestCase
{
    public function test_customer_status_and_message_template_requests_enforce_their_registered_policy_capability(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        foreach ([
            'app/Http/Requests/Tenants/GeneralSetting/CustomerStatus/SaveSettingsCustomerStatusRequest.php' => 'customer_statuses.view',
            'app/Http/Requests/Tenants/GeneralSetting/MessageTemplate/StoreMessageTemplateRequest.php' => 'message_templates.view',
            'app/Http/Requests/Tenants/GeneralSetting/MessageTemplate/UpdateMessageTemplateRequest.php' => 'message_templates.view',
        ] as $path => $permission) {
            $request = file_get_contents($projectRoot.'/'.$path);

            $this->assertStringContainsString("'{$permission}", $request);
            $this->assertStringContainsString('->can(', $request);
            $this->assertStringNotContainsString('return true;', $request);
        }
    }

    public function test_shared_setting_flow_authorizes_records_and_delegates_mutations(): void
    {
        $trait = file_get_contents((new ReflectionClass(CommonSettingController::class))->getFileName());

        foreach (['viewAny', 'view', 'create', 'update', 'toggleStatus', 'delete'] as $ability) {
            $this->assertStringContainsString("authorize('{$ability}'", $trait);
        }
        $this->assertStringContainsString(GeneralSettingAction::class, $trait);
        $this->assertStringNotContainsString('->save()', $trait);
        $this->assertStringNotContainsString('->delete()', $trait);
    }

    public function test_lookup_policy_exposes_the_complete_record_lifecycle(): void
    {
        $policy = new ReflectionClass(LookupDeletePolicy::class);

        foreach (['viewAny', 'view', 'create', 'update', 'toggleStatus', 'delete'] as $ability) {
            $this->assertTrue($policy->hasMethod($ability), "Lookup policy must define {$ability}().");
        }
    }

    public function test_each_lookup_model_has_a_dedicated_permission_policy(): void
    {
        $provider = file_get_contents(dirname(__DIR__, 2).'/app/Providers/AuthServiceProvider.php');

        foreach ([
            'SettingsCountry::class => CountryPolicy::class',
            'SettingsPaymentMethod::class => PaymentMethodPolicy::class',
            'SettingsCustomerStatus::class => CustomerStatusPolicy::class',
            'SettingsContactMethod::class => ContactMethodPolicy::class',
            'ContactResult::class => ContactResultPolicy::class',
            'SettingsDebtType::class => DebtTypePolicy::class',
            'CustomerDistribution::class => CustomerDistributionPolicy::class',
            'SettingsDocumentRequestReason::class => DocumentRequestReasonPolicy::class',
            'MessageTemplate::class => MessageTemplatePolicy::class',
        ] as $registration) {
            $this->assertStringContainsString($registration, $provider);
        }
    }

    public function test_lookup_permissions_do_not_grant_access_to_another_lookup_administration(): void
    {
        $user = $this->createMock(User::class);
        $user->method('can')->willReturnCallback(fn (string $ability): bool => $ability === 'countries.view');

        $this->assertTrue((new CountryPolicy)->create($user));
        $this->assertFalse((new CustomerStatusPolicy)->create($user));
    }

    public function test_message_templates_use_policy_action_and_form_request(): void
    {
        $controller = file_get_contents((new ReflectionClass(MessageTemplateController::class))->getFileName());

        $this->assertStringContainsString(MessageTemplateAction::class, $controller);
        $this->assertStringContainsString('PreviewMessageTemplateRequest $request', $controller);
        $this->assertStringNotContainsString('$request->validate(', $controller);
        $this->assertStringNotContainsString('MessageTemplate::create(', $controller);
    }
}
