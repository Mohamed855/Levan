<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Tenants\Assignments\AssignmentsController;
use App\Http\Controllers\Tenants\Auctions\AuctionsController;
use App\Http\Controllers\Tenants\BusinessCenter\Company\CompanyController;
use App\Http\Controllers\Tenants\BusinessCenter\Contract\ContractController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\Attachment\CustomerAttachmentController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\BulkUpdate\CustomerBulkUpdateController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\Comment\CustomerCommentController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\CustomerController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\Distribution\CustomerDistributionController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\Follow\CustomerFollowController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\Note\CustomerNoteController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\Payment\CustomerPaymentController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\PaymentPlan\CustomerPaymentPlanController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\PhoneRequest\CustomerPhoneRequestController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\Settlement\CustomerSettlementController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\Sms\CustomerSmsController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\Letter\CompanyLetterController;
use App\Http\Controllers\Tenants\BusinessCenter\Customer\UpdateRequest\CustomerUpdateRequestController;
use App\Http\Controllers\Tenants\BusinessCenter\Task\MyTaskController;
use App\Http\Controllers\Tenants\BusinessCenter\Task\TaskController;
use App\Http\Controllers\Tenants\BusinessCenter\Portfolio\PortfolioController;
use App\Http\Controllers\Tenants\Communication\MessageLog\MessageIndividualLogController;
use App\Http\Controllers\Tenants\Communication\MessageLog\MessageLogController;
use App\Http\Controllers\Tenants\Properties\PropertiesController;
use App\Http\Controllers\Tenants\UserMangment\Team\TeamController;
use App\Http\Controllers\Tenants\UserMangment\User\UserController;
use App\Http\Requests\Tenants\BusinessCenter\Customer\BulkUpdate\BulkUpdateRequest;
use App\Policies\Tenants\Assignments\AssignmentPolicy;
use App\Policies\Tenants\Auctions\AuctionPolicy;
use App\Policies\Tenants\BusinessCenter\Company\CompanyPolicy;
use App\Policies\Tenants\BusinessCenter\Contract\ContractPolicy;
use App\Policies\Tenants\BusinessCenter\Customer\CustomerPolicy;
use App\Policies\Tenants\BusinessCenter\Customer\CompanyLetterPolicy;
use App\Policies\Tenants\BusinessCenter\Portfolio\PortfolioPolicy;
use App\Policies\Tenants\BusinessCenter\Task\TaskPolicy;
use App\Policies\Tenants\Communication\MessageLog\MessageIndividualLogPolicy;
use App\Policies\Tenants\Communication\MessageLog\MessageLogPolicy;
use App\Policies\Tenants\Properties\PropertyPolicy;
use App\Policies\Tenants\UserManagement\Team\TeamPolicy;
use App\Policies\Tenants\UserManagement\User\UserPolicy;
use App\Services\Tenants\BusinessCenter\Company\CompanyService;
use App\Services\Tenants\BusinessCenter\Customer\BulkUpdate\CustomerBulkUpdateService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class TenantAssetPolicyContractTest extends TestCase
{
    public function test_asset_policies_expose_the_resource_lifecycle_abilities(): void
    {
        foreach ([
            AssignmentPolicy::class,
            PropertyPolicy::class,
            AuctionPolicy::class,
            CompanyPolicy::class,
            ContractPolicy::class,
            PortfolioPolicy::class,
            CustomerPolicy::class,
            TeamPolicy::class,
            UserPolicy::class,
            MessageLogPolicy::class,
            MessageIndividualLogPolicy::class,
        ] as $policy) {
            $reflection = new ReflectionClass($policy);

            foreach (['viewAny', 'view', 'create', 'update', 'delete'] as $ability) {
                $this->assertTrue($reflection->hasMethod($ability), "{$policy} must define {$ability}().");
            }
        }
    }

    public function test_asset_controllers_apply_resource_authorization(): void
    {
        foreach ([
            AssignmentsController::class,
            PropertiesController::class,
            AuctionsController::class,
            CompanyController::class,
            ContractController::class,
            PortfolioController::class,
            TeamController::class,
            UserController::class,
            MessageLogController::class,
            MessageIndividualLogController::class,
        ] as $controller) {
            $content = file_get_contents((new ReflectionClass($controller))->getFileName());

            $this->assertStringContainsString('authorizeResource(', $content);
        }

        $customerController = file_get_contents((new ReflectionClass(CustomerController::class))->getFileName());
        $this->assertStringContainsString('authorize(\'view\', $customer)', $customerController);
        $this->assertStringContainsString('authorize(\'update\', $customer)', $customerController);
        $this->assertStringContainsString('authorize(\'delete\', $customer)', $customerController);

        $customerPolicy = new ReflectionClass(CustomerPolicy::class);
        $this->assertTrue($customerPolicy->hasMethod('collect'));
    }

    public function test_company_logo_storage_is_owned_by_the_company_service(): void
    {
        $controller = file_get_contents((new ReflectionClass(CompanyController::class))->getFileName());
        $service = file_get_contents((new ReflectionClass(CompanyService::class))->getFileName());

        $this->assertStringContainsString('createCompanyWithLogo(', $controller);
        $this->assertStringNotContainsString("->store('companies/logos'", $controller);
        $this->assertStringContainsString('function createCompanyWithLogo(', $service);
    }

    public function test_bulk_customer_update_temporary_upload_is_owned_by_its_service(): void
    {
        $controller = file_get_contents((new ReflectionClass(CustomerBulkUpdateController::class))->getFileName());
        $service = file_get_contents((new ReflectionClass(CustomerBulkUpdateService::class))->getFileName());

        $this->assertStringContainsString('storeTemporaryFile(', $controller);
        $this->assertStringNotContainsString("->store('temp_updates')", $controller);
        $this->assertStringContainsString('function storeTemporaryFile(', $service);
    }

    public function test_customer_payment_mutations_require_collection_capabilities_and_record_authorization(): void
    {
        $controller = file_get_contents((new ReflectionClass(CustomerPaymentController::class))->getFileName());

        foreach (['collections.create', 'collections.edit', 'collections.delete'] as $permission) {
            $this->assertStringContainsString("can:{$permission}", $controller);
        }

        $this->assertStringContainsString("authorize('update', \$collection)", $controller);
        $this->assertStringContainsString("authorize('delete', \$collection)", $controller);
    }

    public function test_customer_finance_subresources_authorize_the_parent_customer(): void
    {
        foreach ([CustomerPaymentPlanController::class, CustomerSettlementController::class] as $controller) {
            $content = file_get_contents((new ReflectionClass($controller))->getFileName());

            $this->assertStringContainsString("authorize('view', \$customer)", $content);
            $this->assertStringContainsString("authorize('update', \$customer)", $content);
            $this->assertStringContainsString("authorize('delete', \$customer)", $content);
            $this->assertStringContainsString('customer_id !== $customer->id', $content);
        }
    }

    public function test_customer_attachment_and_note_subresources_authorize_parent_and_nested_record(): void
    {
        foreach ([CustomerAttachmentController::class, CustomerNoteController::class] as $controller) {
            $content = file_get_contents((new ReflectionClass($controller))->getFileName());

            $this->assertStringContainsString("authorize('view', \$customer)", $content);
            $this->assertStringContainsString("authorize('update', \$customer)", $content);
            $this->assertStringContainsString("authorize('delete', \$customer)", $content);
            $this->assertStringContainsString('customer_id !== $customer->id', $content);
        }
    }

    public function test_customer_comment_and_phone_request_subresources_authorize_the_parent_customer(): void
    {
        foreach ([CustomerCommentController::class, CustomerPhoneRequestController::class] as $controller) {
            $content = file_get_contents((new ReflectionClass($controller))->getFileName());

            $this->assertStringContainsString("authorize('view', \$customer)", $content);
            $this->assertStringContainsString("authorize('update', \$customer)", $content);
        }

        $commentController = file_get_contents((new ReflectionClass(CustomerCommentController::class))->getFileName());
        $this->assertStringContainsString("authorize('delete', \$customer)", $commentController);
        $this->assertStringContainsString("where('customer_id', \$customer->id)", $commentController);
    }

    public function test_customer_sms_and_company_letters_apply_record_authorization(): void
    {
        $smsController = file_get_contents((new ReflectionClass(CustomerSmsController::class))->getFileName());
        $letterController = file_get_contents((new ReflectionClass(CompanyLetterController::class))->getFileName());

        $this->assertStringContainsString("authorize('update', \$customer)", $smsController);
        $this->assertStringContainsString("authorize('viewAny', CompanyLetter::class)", $letterController);
        $this->assertStringContainsString("authorize('view', \$letter)", $letterController);
        $this->assertStringContainsString("authorize('send', \$letter)", $letterController);

        $policy = new ReflectionClass(CompanyLetterPolicy::class);
        foreach (['viewAny', 'view', 'send'] as $ability) {
            $this->assertTrue($policy->hasMethod($ability), "CompanyLetterPolicy must define {$ability}().");
        }
    }

    public function test_task_queues_use_a_registered_policy_for_their_distinct_permissions(): void
    {
        $taskController = file_get_contents((new ReflectionClass(TaskController::class))->getFileName());
        $myTaskController = file_get_contents((new ReflectionClass(MyTaskController::class))->getFileName());

        $this->assertStringContainsString("authorize('viewAny', Task::class)", $taskController);
        $this->assertStringContainsString("authorize('viewOwnQueue', Task::class)", $myTaskController);

        $policy = new ReflectionClass(TaskPolicy::class);
        $this->assertTrue($policy->hasMethod('viewAny'));
        $this->assertTrue($policy->hasMethod('viewOwnQueue'));

        $taskDataTable = file_get_contents(dirname(__DIR__, 2).'/app/DataTables/Tenants/BusinessCenter/Tasks/TasksDataTable.php');
        $myTaskDataTable = file_get_contents(dirname(__DIR__, 2).'/app/DataTables/Tenants/BusinessCenter/Tasks/MyTasksDataTable.php');

        $this->assertStringNotContainsString("'data' => 'checkbox'", $taskDataTable.$myTaskDataTable);
        $this->assertStringNotContainsString('handleMassDelete', $taskDataTable.$myTaskDataTable);
    }

    public function test_task_queue_views_expose_five_lifecycle_statistics(): void
    {
        foreach ([
            dirname(__DIR__, 2).'/resources/views/tenants/business_center/tasks/index.blade.php',
            dirname(__DIR__, 2).'/resources/views/tenants/business_center/my_tasks/index.blade.php',
        ] as $view) {
            $content = file_get_contents($view);

            $this->assertSame(5, substr_count($content, 'col-sm-6 col-xl">'));
            $this->assertStringContainsString('$dueTodayTasks', $content);
        }
    }

    public function test_customer_follow_subresources_authorize_parent_and_verify_nested_ownership(): void
    {
        $controller = file_get_contents((new ReflectionClass(CustomerFollowController::class))->getFileName());

        $this->assertStringContainsString("authorize('view', \$customer)", $controller);
        $this->assertStringContainsString("authorize('update', \$customer)", $controller);
        $this->assertStringContainsString("authorize('delete', \$customer)", $controller);
        $this->assertStringContainsString('customerFollow->customer_id === $customer->getKey()', $controller);
    }

    public function test_customer_distribution_protects_every_active_mutation_with_its_create_capability(): void
    {
        $controller = file_get_contents((new ReflectionClass(CustomerDistributionController::class))->getFileName());

        $this->assertStringContainsString("can:customer_distributions.create')->only(['create', 'preview', 'calculateTargets', 'execute'])", $controller);
        $this->assertStringContainsString("can:customer_distributions.view')->only(['index', 'show', 'getPortfoliosWithUnassignedCustomers', 'getCollectorsByTeam'])", $controller);
    }

    public function test_customer_bulk_update_uses_the_existing_unscoped_edit_capability(): void
    {
        $controller = file_get_contents((new ReflectionClass(CustomerBulkUpdateController::class))->getFileName());
        $request = file_get_contents((new ReflectionClass(BulkUpdateRequest::class))->getFileName());

        $this->assertStringContainsString('can:customers.edit_all', $controller);
        $this->assertStringContainsString("can('customers.edit_all')", $request);
        $this->assertStringNotContainsString('customers.update', $controller.$request);
    }

    public function test_customer_update_request_uses_typed_requests_and_record_policy(): void
    {
        $controller = file_get_contents((new ReflectionClass(CustomerUpdateRequestController::class))->getFileName());

        foreach ([
            'ApproveCustomerUpdateRequest',
            'RejectCustomerUpdateRequest',
            'BulkCustomerUpdateRequestDecisionRequest',
            'ExportCustomerUpdateRequestsRequest',
        ] as $request) {
            $this->assertStringContainsString($request, $controller);
        }

        foreach (['view', 'approve', 'reject'] as $ability) {
            $this->assertStringContainsString("authorize('{$ability}',", $controller);
        }

        $this->assertStringContainsString("can:customer_update_requests.view')->only(['index', 'show', 'getPendingCount', 'exportFields', 'exportExcel'])", $controller);
    }
}
