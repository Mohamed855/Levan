<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Shared\Wizard\WizardDraftController;
use App\Http\Requests\Shared\Wizard\StoreWizardDraftRequest;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Shared\WizardDraft;
use App\Services\Shared\Wizard\WizardDraftService;
use Illuminate\Auth\GenericUser;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\RequestGuard;
use Illuminate\Config\Repository;
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
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class WizardDraftSafetyTest extends TestCase
{
    private Application $app;
    private WizardDraftService $drafts;
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application(dirname(__DIR__, 2));
        $this->directory = sys_get_temp_dir().'/levan-draft-test-'.bin2hex(random_bytes(8));
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
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
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
        } catch (\Illuminate\Validation\ValidationException $exception) {
            self::assertArrayHasKey('attachments', $exception->errors());
        }
        self::assertCount(50, $draft->fresh()->draft_files['attachments']);
        self::assertCount(50, Storage::disk('local')->allFiles());
    }
}

final class DraftTestActor extends GenericUser
{
    public function can($ability, $arguments = []): bool { return true; }
}

final class DraftUploadFixtureRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['name' => ['required', 'string'], 'document' => ['required', 'file', 'max:2']]; }
}

final class DraftUploadFixtureController
{
    public function store(DraftUploadFixtureRequest $request): void {}
}
