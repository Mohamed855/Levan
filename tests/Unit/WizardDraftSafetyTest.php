<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Requests\Concerns\WizardFormRequest;
use App\Http\Requests\Shared\Wizard\StoreWizardDraftRequest;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Shared\WizardDraft;
use App\Services\Shared\Wizard\WizardDraftService;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\GenericUser;
use Illuminate\Auth\RequestGuard;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class WizardDraftSafetyTest extends TestCase
{
    private Application $app;

    private WizardDraftService $drafts;

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application(dirname(__DIR__, 2));
        $this->directory = sys_get_temp_dir().'/lot-draft-test-'.bin2hex(random_bytes(8));
        $this->app->instance('config', new Repository([
            'filesystems' => ['default' => 'local', 'disks' => ['local' => ['driver' => 'local', 'root' => $this->directory, 'throw' => true]]],
            'tenancy' => ['database' => ['connection' => 'tenant']],
        ]));
        $events = new Dispatcher($this->app);
        $this->app->instance('events', $events);
        $this->app->instance('db.transactions', new DatabaseTransactionsManager);
        $capsule = new Manager($this->app);
        foreach (['mysql', 'tenant'] as $connection) {
            $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''], $connection);
        }
        $this->app['config']->set('database.default', 'mysql');
        $capsule->setEventDispatcher($events);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        $this->app->instance('db', $capsule->getDatabaseManager());
        $this->app->instance('filesystem', new FilesystemManager($this->app));
        $router = new Router($events, $this->app);
        $router->post('/draft-fixture', [DraftUploadFixtureController::class, 'store'])->middleware('auth:admin')->name('admin.fixture.store');
        $router->post('/users', [DraftUploadFixtureController::class, 'store'])->middleware('auth:admin')->name('admin.users.store');
        $router->getRoutes()->refreshNameLookups();
        $this->app->instance('router', $router);
        $this->app->instance('validator', new Factory(new Translator(new ArrayLoader, 'en'), $this->app));
        $this->app['config']->set('auth', ['defaults' => ['guard' => 'admin'], 'guards' => ['admin' => ['driver' => 'fixture'], 'tenant' => ['driver' => 'fixture']]]);
        $auth = new AuthManager($this->app);
        $auth->extend('fixture', fn ($app, $name) => new RequestGuard(fn () => new DraftTestActor(['id' => $name === 'admin' ? 71 : 22]), Request::create('/')));
        $this->app->instance('auth', $auth);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->app);
        WizardDraft::clearBootedModels();
        foreach (['mysql', 'tenant'] as $connection) {
            $capsule->getConnection($connection)->getSchemaBuilder()->create('wizard_drafts', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('guard');
                $table->unsignedBigInteger('user_id');
                $table->string('tenant_id');
                $table->string('resource_key');
                $table->unsignedInteger('last_step');
                $table->json('draft_data')->nullable();
                $table->json('draft_files')->nullable();
                $table->timestamps();
                $table->unique(['guard', 'user_id', 'tenant_id', 'resource_key']);
            });
        }
        $this->drafts = $this->app->make(WizardDraftService::class);
    }

    protected function tearDown(): void
    {
        WizardDraft::flushEventListeners();
        WizardDraft::clearBootedModels();
        (new Filesystem)->deleteDirectory($this->directory);
        Facade::clearResolvedInstances();
        parent::tearDown();
    }

    private function request(array $input = [], array $files = [], string $guard = 'admin'): Request
    {
        $request = Request::create('/wizard-drafts', 'POST', $input, [], $files);
        $request->setRouteResolver(fn () => (new Route('POST', 'wizard-drafts', fn () => null))->middleware('auth:'.$guard)->name($guard === 'admin' ? 'admin.wizard-drafts.store' : 'path.wizard-drafts.store'));
        $request->setUserResolver(fn ($requestedGuard = null) => new DraftTestActor(['id' => ($requestedGuard ?? $guard) === 'admin' ? 71 : 22]));

        return $request;
    }

    public function test_tenant_route_uses_tenant_user_when_both_guards_are_authenticated(): void
    {
        $tenant = new Tenant;
        $tenant->setKeyType('string');
        $tenant->id = 'tenant-a';
        $this->app->instance(\Stancl\Tenancy\Contracts\Tenant::class, $tenant);
        $draft = $this->drafts->save($this->request(['name' => 'Tenant draft'], guard: 'tenant'), 'tenant.property-create', 1);
        self::assertSame('tenant', $draft->guard);
        self::assertSame(22, (int) $draft->user_id);
        self::assertSame('tenant-a', $draft->tenant_id);
        self::assertSame(0, Manager::connection('mysql')->table('wizard_drafts')->count());
    }

    public function test_shortened_and_cleared_lists_do_not_resurrect_removed_rows(): void
    {
        $this->drafts->save($this->request(['owners' => [['name' => 'A'], ['name' => 'B']], 'notes' => 'Keep']), 'fixture', 1);
        $draft = $this->drafts->save($this->request(['owners' => [['name' => 'A']]]), 'fixture', 2);
        self::assertSame([['name' => 'A']], $draft->draft_data['owners']);
        self::assertSame('Keep', $draft->draft_data['notes']);
        $draft = $this->drafts->save($this->request(['owners' => []]), 'fixture', 2);
        self::assertSame([], $draft->draft_data['owners']);
    }

    public function test_failed_save_preserves_old_file_and_cleans_new_upload(): void
    {
        $draft = $this->drafts->save($this->request(files: ['document' => UploadedFile::fake()->create('old.pdf', 1)]), 'fixture', 1);
        $oldPath = $draft->draft_files['document']['path'];
        WizardDraft::saving(fn () => throw new RuntimeException('Simulated database failure'));
        try {
            $this->drafts->save($this->request(files: ['document' => UploadedFile::fake()->create('new.pdf', 1)]), 'fixture', 1);
            self::fail('The write must fail');
        } catch (RuntimeException $exception) {
            self::assertSame('Simulated database failure', $exception->getMessage());
        }
        self::assertTrue(Storage::disk('local')->exists($oldPath));
        self::assertSame([$oldPath], Storage::disk('local')->allFiles());
        self::assertSame($oldPath, $draft->fresh()->draft_files['document']['path']);
    }

    public function test_successful_replacement_removes_old_file_after_commit(): void
    {
        $draft = $this->drafts->save($this->request(files: ['document' => UploadedFile::fake()->create('old.pdf', 1)]), 'fixture', 1);
        $oldPath = $draft->draft_files['document']['path'];
        WizardDraft::saving(fn () => self::assertTrue(Storage::disk('local')->exists($oldPath)));
        $draft = $this->drafts->save($this->request(files: ['document' => UploadedFile::fake()->create('new.pdf', 1)]), 'fixture', 1);
        self::assertFalse(Storage::disk('local')->exists($oldPath));
        self::assertTrue(Storage::disk('local')->exists($draft->draft_files['document']['path']));
    }

    public function test_different_files_with_same_name_size_and_mime_are_preserved(): void
    {
        $draft = $this->drafts->save($this->request(files: ['attachments' => [UploadedFile::fake()->createWithContent('invoice.pdf', 'content-A')]]), 'fixture', 1);
        $draft = $this->drafts->save($this->request(files: ['attachments' => [UploadedFile::fake()->createWithContent('invoice.pdf', 'content-B')]]), 'fixture', 1);
        self::assertCount(2, $draft->draft_files['attachments']);
        self::assertCount(2, Storage::disk('local')->allFiles());
    }

    public function test_draft_upload_uses_resource_file_limits_without_required_scalar_fields(): void
    {
        $request = StoreWizardDraftRequest::createFrom($this->request(['resource_key' => 'fixture', 'step' => 1, 'target_path' => '/draft-fixture'], ['document' => UploadedFile::fake()->create('large.pdf', 3)]));
        $request->setContainer($this->app);
        $validator = $this->app['validator']->make($request->all(), $request->rules());
        self::assertTrue($validator->fails());
        self::assertTrue($validator->errors()->has('document'));
        self::assertFalse($validator->errors()->has('name'));
    }

    public function test_unrecognized_upload_field_is_rejected(): void
    {
        $request = StoreWizardDraftRequest::createFrom($this->request(['resource_key' => 'fixture', 'step' => 1, 'target_path' => '/draft-fixture'], ['unexpected' => UploadedFile::fake()->create('payload.html', 1)]));
        $request->setContainer($this->app);
        $validator = $this->app['validator']->make($request->all(), $request->rules());
        self::assertTrue($validator->fails());
        self::assertTrue($validator->errors()->has('unexpected'));
    }

    public function test_uploaded_scalar_field_cannot_be_used_as_a_file_slot(): void
    {
        $request = StoreWizardDraftRequest::createFrom($this->request(['resource_key' => 'fixture', 'step' => 1, 'target_path' => '/draft-fixture'], ['name' => UploadedFile::fake()->create('payload.html', 1)]));
        $request->setContainer($this->app);
        self::assertTrue($this->app['validator']->make($request->all(), $request->rules())->fails());
    }

    public function test_outer_transaction_rollback_cleans_new_upload_and_keeps_old_file(): void
    {
        $draft = $this->drafts->save($this->request(files: ['document' => UploadedFile::fake()->create('old.pdf', 1)]), 'fixture', 1);
        $old = $draft->draft_files['document']['path'];
        $connection = Manager::connection('mysql');
        $connection->beginTransaction();
        $this->drafts->save($this->request(files: ['document' => UploadedFile::fake()->create('new.pdf', 1)]), 'fixture', 1);
        self::assertCount(2, Storage::disk('local')->allFiles());
        $connection->rollBack();
        self::assertSame([$old], Storage::disk('local')->allFiles());
        self::assertSame($old, $draft->fresh()->draft_files['document']['path']);
    }

    public function test_known_resource_key_accepts_partial_upload_without_target_path(): void
    {
        $request = StoreWizardDraftRequest::createFrom($this->request(['resource_key' => 'admin.user-create', 'step' => 1], ['document' => UploadedFile::fake()->create('small.pdf', 1)]));
        $request->setContainer($this->app);
        self::assertFalse($this->app['validator']->make($request->all(), $request->rules())->fails());
    }

    public function test_cross_guard_upload_target_is_rejected(): void
    {
        $request = StoreWizardDraftRequest::createFrom($this->request(['resource_key' => 'fixture', 'step' => 1, 'target_path' => '/draft-fixture'], ['document' => UploadedFile::fake()->create('small.pdf', 1)], 'tenant'));
        $request->setContainer($this->app);
        $this->expectException(HttpException::class);
        $request->rules();
    }

    public function test_cumulative_upload_limit_cannot_be_bypassed_by_multiple_saves(): void
    {
        $uploads = [];
        for ($index = 0; $index < 50; $index++) {
            $uploads[] = UploadedFile::fake()->createWithContent('file-'.$index.'.txt', 'body-'.$index);
        }
        $draft = $this->drafts->save($this->request(files: ['attachments' => $uploads]), 'fixture', 1);
        try {
            $this->drafts->save($this->request(files: ['attachments' => [UploadedFile::fake()->createWithContent('overflow.txt', 'overflow')]]), 'fixture', 1);
            self::fail('Cumulative file limit must apply');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('attachments', $exception->errors());
        }
        self::assertCount(50, $draft->fresh()->draft_files['attachments']);
        self::assertCount(50, Storage::disk('local')->allFiles());
    }

    private function failedForm(array $input = [], array $files = [], string $guard = 'admin'): array
    {
        $root = $this->request($input, $files, $guard);
        $root->setLaravelSession(new Store('draft-test', new ArraySessionHandler(120)));
        $this->app->instance('request', $root);
        $form = FailedDraftFixtureRequest::createFrom($root);
        $form->setContainer($this->app);

        return [$root, $form];
    }

    private function rejectForm(FailedDraftFixtureRequest $form): ValidationException
    {
        $validator = $this->app['validator']->make($form->all(), $form->rules());
        self::assertTrue($validator->fails());
        try {
            $form->reject($validator);
            self::fail('The original validation failure must still be thrown.');
        } catch (ValidationException $exception) {
            return $exception;
        }
    }

    public function test_failed_final_validation_preserves_only_valid_declared_uploads_and_flashes_their_draft(): void
    {
        [$root, $form] = $this->failedForm(['_wizard_resource_key' => 'fixture', '_wizard_step' => 999, 'notes' => 'Keep me'], [
            'document' => UploadedFile::fake()->create('valid.pdf', 1),
            'oversized' => UploadedFile::fake()->create('invalid.pdf', 3),
            'unexpected' => UploadedFile::fake()->create('payload.html', 1),
            'nullable_scalar' => UploadedFile::fake()->create('payload.html', 1),
            'attachments' => [UploadedFile::fake()->create('one.pdf', 1), UploadedFile::fake()->create('two.pdf', 1)],
            'nested' => [['files' => [UploadedFile::fake()->create('nested.pdf', 1), UploadedFile::fake()->create('large.pdf', 3)]]],
        ]);
        $exception = $this->rejectForm($form);
        self::assertArrayHasKey('name', $exception->errors());
        self::assertArrayHasKey('oversized', $exception->errors());
        self::assertArrayHasKey('attachments', $exception->errors());
        $draft = $this->drafts->find($root, 'fixture');
        self::assertNotNull($draft);
        self::assertSame(100, $draft->last_step);
        self::assertSame('Keep me', $draft->draft_data['notes']);
        self::assertSame(['document', 'nested'], array_keys($draft->draft_files));
        self::assertCount(1, $draft->draft_files['nested'][0]['files']);
        self::assertCount(2, Storage::disk('local')->allFiles());
        self::assertSame(['id' => $draft->id, 'resource_key' => 'fixture'], $root->session()->get('_wizard_failed_draft'));
        self::assertContains('_wizard_failed_draft', $root->session()->get('_flash.new'));
        self::assertSame($draft->id, $root->input('_wizard_draft_id'));
        self::assertSame($draft->id, $form->input('_wizard_draft_id'));
        self::assertTrue($form->hasFile('oversized'), 'Original uploads must remain available to the validation response.');
    }

    public function test_ajax_and_json_validation_failures_never_save_a_draft(): void
    {
        foreach ([['Accept', 'application/json'], ['X-Requested-With', 'XMLHttpRequest'], ['Content-Type', 'application/json']] as [$header, $value]) {
            [$root, $form] = $this->failedForm(['_wizard_resource_key' => 'fixture'], ['document' => UploadedFile::fake()->create('valid.pdf', 1)]);
            $form->headers->set($header, $value);
            if ($header === 'Content-Type') {
                $form->setJson(new InputBag(['_wizard_resource_key' => 'fixture']));
            }
            $this->rejectForm($form);
            self::assertNull($this->drafts->find($root, 'fixture'));
            self::assertFalse($root->session()->has('_wizard_failed_draft'));
        }
        self::assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_persistence_failure_is_reported_without_replacing_the_original_validation_errors(): void
    {
        $handler = $this->createMock(ExceptionHandler::class);
        $handler->expects(self::once())->method('report')->with(self::callback(fn (\Throwable $exception): bool => $exception->getMessage() === 'Draft storage failed'));
        $this->app->instance(ExceptionHandler::class, $handler);
        WizardDraft::saving(fn () => throw new RuntimeException('Draft storage failed'));
        [$root, $form] = $this->failedForm(['_wizard_resource_key' => 'fixture'], ['document' => UploadedFile::fake()->create('valid.pdf', 1)]);
        $exception = $this->rejectForm($form);
        self::assertArrayHasKey('name', $exception->errors());
        self::assertFalse($root->session()->has('_wizard_failed_draft'));
        self::assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_forms_without_a_resource_key_or_session_do_not_persist_failed_drafts(): void
    {
        [$root, $form] = $this->failedForm(files: ['document' => UploadedFile::fake()->create('valid.pdf', 1)]);
        $this->rejectForm($form);
        self::assertFalse($root->session()->has('_wizard_failed_draft'));
        $form = FailedDraftFixtureRequest::createFrom($this->request(['_wizard_resource_key' => 'fixture'], ['document' => UploadedFile::fake()->create('valid.pdf', 1)]));
        $form->setContainer($this->app);
        $this->rejectForm($form);
        self::assertSame(0, Manager::connection('mysql')->table('wizard_drafts')->count());
        self::assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_failed_final_drafts_keep_the_active_tenant_and_user_identity(): void
    {
        $tenant = new Tenant;
        $tenant->setKeyType('string');
        $tenant->id = 'tenant-a';
        $this->app->instance(\Stancl\Tenancy\Contracts\Tenant::class, $tenant);
        [$root, $form] = $this->failedForm(['_wizard_resource_key' => 'fixture', '_wizard_step' => -4], ['document' => UploadedFile::fake()->create('valid.pdf', 1)], 'tenant');
        $this->rejectForm($form);
        $draft = $this->drafts->find($root, 'fixture');
        self::assertNotNull($draft);
        self::assertSame('tenant', $draft->guard);
        self::assertSame(22, (int) $draft->user_id);
        self::assertSame('tenant-a', $draft->tenant_id);
        self::assertSame(1, $draft->last_step);
        self::assertNull($this->drafts->find($this->request(guard: 'admin'), 'fixture'));
        $otherUser = $this->request(guard: 'tenant');
        $otherUser->setUserResolver(fn () => new DraftTestActor(['id' => 99]));
        self::assertNull($this->drafts->find($otherUser, 'fixture'));
        $tenant->id = 'tenant-b';
        self::assertNull($this->drafts->find($root, 'fixture'));
    }

    private function renderValidationCallbacks(Request $root, ValidationException $exception): mixed
    {
        $configuredApp = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $handler = $configuredApp->make(ExceptionHandler::class);
        Application::setInstance($this->app);

        return (new \ReflectionMethod($handler, 'renderViaCallbacks'))->invoke($handler, $root, $exception);
    }

    public function test_domain_validation_after_form_validation_preserves_uploads_and_original_errors(): void
    {
        [$root, $form] = $this->failedForm(['_wizard_resource_key' => 'fixture', 'name' => 'Valid name', 'notes' => 'Keep'], [
            'document' => UploadedFile::fake()->create('valid.pdf', 1),
            'oversized' => UploadedFile::fake()->create('domain-rejected.pdf', 1),
        ]);
        $form->validateResolved();
        self::assertSame($form, $root->attributes->get('wizard_validated_request'));
        $exception = ValidationException::withMessages(['oversized' => 'The document does not match this property.', 'name' => 'The operation cannot be completed.']);
        $errors = $exception->errors();
        self::assertNull($this->renderValidationCallbacks($root, $exception));
        $draft = $this->drafts->find($root, 'fixture');
        self::assertNotNull($draft);
        self::assertSame(['document'], array_keys($draft->draft_files));
        self::assertSame('Keep', $draft->draft_data['notes']);
        self::assertSame($errors, $exception->errors());
        self::assertSame(['id' => $draft->id, 'resource_key' => 'fixture'], $root->session()->get('_wizard_failed_draft'));
        self::assertSame($draft->id, $root->input('_wizard_draft_id'));
    }

    public function test_domain_validation_of_json_forms_does_not_persist_uploads(): void
    {
        [$root, $form] = $this->failedForm(['_wizard_resource_key' => 'fixture', 'name' => 'Valid name'], ['document' => UploadedFile::fake()->create('valid.pdf', 1)]);
        $root->headers->set('Accept', 'application/json');
        $form->headers->set('Accept', 'application/json');
        $form->validateResolved();
        self::assertNull($this->renderValidationCallbacks($root, ValidationException::withMessages(['name' => 'Rejected by the action.'])));
        self::assertNull($this->drafts->find($root, 'fixture'));
        self::assertFalse($root->session()->has('_wizard_failed_draft'));
        self::assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_normal_failed_validation_is_not_saved_twice_by_the_exception_renderer(): void
    {
        [$root, $form] = $this->failedForm(['_wizard_resource_key' => 'fixture'], ['document' => UploadedFile::fake()->create('valid.pdf', 1)]);
        $saves = 0;
        WizardDraft::saved(function () use (&$saves): void {
            $saves++;
        });
        $exception = $this->rejectForm($form);
        self::assertNull($this->renderValidationCallbacks($root, $exception));
        self::assertSame(1, $saves);
        self::assertCount(1, Storage::disk('local')->allFiles());
    }
}

final class FailedDraftFixtureRequest extends WizardFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'], 'document' => ['nullable', 'file', 'max:2'],
            'oversized' => ['nullable', 'file', 'max:2'], 'nullable_scalar' => ['nullable'],
            'attachments' => ['array', 'max:1'], 'attachments.*' => ['file', 'max:2'],
            'nested.*.files.*' => ['file', 'max:2'],
        ];
    }

    public function reject(Validator $validator): void
    {
        $this->failedValidation($validator);
    }

    protected function getRedirectUrl(): string
    {
        return '/create';
    }
}

final class DraftTestActor extends GenericUser
{
    public function can($ability, $arguments = []): bool
    {
        return true;
    }
}

final class DraftUploadFixtureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string'], 'document' => ['required', 'file', 'max:2']];
    }
}

final class DraftUploadFixtureController
{
    public function store(DraftUploadFixtureRequest $request): void {}
}
