<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants;

use App\Actions\Tenants\Assignments\UpdateAssignmentAction;
use App\Actions\Tenants\MassDelete\DeleteTenantRecordsAction;
use App\Actions\Tenants\Properties\UpdatePropertyAction;
use App\Http\Controllers\Tenants\Assignments\AssignmentsController;
use App\Http\Controllers\Tenants\DeleteBulk\MassDeleteController;
use App\Http\Requests\Tenants\Assignments\UpdateAssignmentRequest;
use App\Enums\Tenants\Properties\PropertyAttachmentCategory;
use App\Models\Shared\Attachment;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

final class AssetMutationSafetyTest extends TestCase
{
    private FilesystemAdapter $disk;

    protected function setUp(): void
    {
        parent::setUp();

        // This suite never uses a configured MySQL/tenant database or migrations.
        config(['database.default' => 'tenant', 'activitylog.enabled' => false, 'database.connections.asset_safety' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        foreach (['mysql', 'tenant'] as $connection) {
            config(["database.connections.$connection" => config('database.connections.asset_safety')]);
            DB::purge($connection);
        }
        DB::purge('asset_safety');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        Schema::create('assignments', function (Blueprint $table): void {
            $table->id();
            $table->string('assignment_number')->nullable();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('properties', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('assignment_id')->nullable();
            $table->string('name');
            $table->string('main_image')->nullable();
            $table->string('aerial_image')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('auctions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('auction_properties', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('auction_id');
            $table->timestamps();
        });
        Schema::create('attachments', function (Blueprint $table): void {
            $table->id();
            $table->morphs('attachable');
            $table->string('disk');
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->string('extension');
            $table->unsignedBigInteger('file_size');
            $table->string('category');
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
        });
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('model_id');
            $table->string('model_type');
        });
        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('model_id');
            $table->string('model_type');
        });
        foreach (['property_boundaries', 'property_owners', 'property_units'] as $name) {
            Schema::create($name, function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('property_id');
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        $this->disk = Storage::build([
            'driver' => 'local',
            'root' => storage_path('framework/testing/disks/asset-safety-'.Str::uuid()),
            'throw' => true,
        ]);
        Storage::set('tenant_public', $this->disk);
        Route::post('/asset-safety/mass-delete', [MassDeleteController::class, 'massDelete']);
        $this->withoutMiddleware();
    }

    protected function tearDown(): void
    {
        Attachment::flushEventListeners();
        if (isset($this->disk)) {
            $this->disk->deleteDirectory('');
        }
        DB::purge('asset_safety');
        parent::tearDown();
    }

    public function test_failed_document_replacement_keeps_old_bytes_and_rolls_back_new_files(): void
    {
        $property = Property::create(['name' => 'Original']);
        $old = $this->attachDeed($property, 'old.pdf');
        Attachment::creating(function (Attachment $attachment): void {
            if ($attachment->category === PropertyAttachmentCategory::CADASTRAL_PLAN->value) {
                throw new RuntimeException('Simulated second attachment failure');
            }
        });

        try {
            app(UpdatePropertyAction::class)->execute($property, [
                'name' => 'Changed',
                'deed_document' => UploadedFile::fake()->createWithContent('replacement.pdf', 'new deed'),
                'cadastral_plan' => UploadedFile::fake()->createWithContent('plan.pdf', 'new plan'),
            ]);
            $this->fail('The second attachment must interrupt this update.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated second attachment failure', $exception->getMessage());
        }

        $this->assertSame('Original', $property->fresh()->name);
        $this->assertSame($old->id, $property->attachments()->sole()->id);
        $this->assertSame('old deed', $this->disk->get('old.pdf'));
        $this->assertSame(['old.pdf'], $this->disk->allFiles());
    }

    public function test_successful_document_replacement_removes_old_file_only_after_commit(): void
    {
        $property = Property::create(['name' => 'Original']);
        $this->attachDeed($property, 'old.pdf');

        $updated = app(UpdatePropertyAction::class)->execute($property, [
            'deed_document' => UploadedFile::fake()->createWithContent('replacement.pdf', 'new deed'),
        ]);

        $current = $updated->attachments->sole();
        $this->assertSame('new deed', $this->disk->get($current->file_path));
        $this->assertFalse($this->disk->exists('old.pdf'));
        $this->assertCount(1, $this->disk->allFiles());
    }

    public function test_cleanup_failure_after_commit_does_not_delete_the_committed_new_file(): void
    {
        $property = Property::create(['name' => 'Original', 'main_image' => 'old.png']);
        $this->disk->put('old.png', 'old image');
        $realDisk = $this->disk;
        $diskWithFailingCleanup = Mockery::mock($realDisk);
        $diskWithFailingCleanup->shouldReceive('delete')->andReturnUsing(
            function (string|array $paths) use ($realDisk): bool {
                if (in_array('old.png', (array) $paths, true)) {
                    throw new RuntimeException('Old file is temporarily locked');
                }

                return $realDisk->delete($paths);
            },
        );
        Storage::set('tenant_public', $diskWithFailingCleanup);

        app(UpdatePropertyAction::class)->execute($property, [
            'name' => 'Committed',
            'main_image' => UploadedFile::fake()->createWithContent('new.png', 'new image'),
        ]);

        $current = $property->fresh();
        $this->assertSame('Committed', $current->name);
        $this->assertSame('new image', $realDisk->get($current->main_image));
        $this->assertSame($current->main_image, $current->attachments()->sole()->file_path);
    }

    public function test_assignment_update_rejects_property_claimed_after_request_validation(): void
    {
        $target = $this->assignment('Target');
        $other = $this->assignment('Other');
        $property = Property::create(['name' => 'Already claimed', 'assignment_id' => $other->id]);

        try {
            app(UpdateAssignmentAction::class)->execute($target, ['property_ids' => [$property->id]]);
            $this->fail('The action must recheck ownership inside its transaction.');
        } catch (ValidationException) {
            $this->assertSame($other->id, $property->fresh()->assignment_id);
        }
    }

    public function test_assignment_update_cannot_detach_property_used_by_an_auction(): void
    {
        $assignment = $this->assignment('Target');
        $property = Property::create(['name' => 'Auction property', 'assignment_id' => $assignment->id]);
        $auction = Auction::create(['name' => 'Historic auction', 'assignment_id' => $assignment->id]);
        DB::table('auction_properties')->insert(['auction_id' => $auction->id, 'property_id' => $property->id]);
        $auction->delete();

        try {
            app(UpdateAssignmentAction::class)->execute($assignment, ['property_ids' => []]);
            $this->fail('Even an archived auction must retain its assignment/property relationship.');
        } catch (ValidationException) {
            $this->assertSame($assignment->id, $property->fresh()->assignment_id);
        }
    }

    public function test_assignment_update_can_attach_available_property_and_detach_unused_property(): void
    {
        $assignment = $this->assignment('Target');
        $old = Property::create(['name' => 'Unused old', 'assignment_id' => $assignment->id]);
        $new = Property::create(['name' => 'Available']);

        app(UpdateAssignmentAction::class)->execute($assignment, ['property_ids' => [$new->id]]);

        $this->assertNull($old->fresh()->assignment_id);
        $this->assertSame($assignment->id, $new->fresh()->assignment_id);
    }

    public function test_mass_delete_rolls_back_earlier_records_when_an_assignment_has_children(): void
    {
        $allowed = $this->assignment('Allowed');
        $blocked = $this->assignment('Blocked');
        Property::create(['name' => 'Child', 'assignment_id' => $blocked->id]);

        try {
            app(DeleteTenantRecordsAction::class)->execute(collect([$allowed, $blocked]));
            $this->fail('Mass deletion must honor the same relationship guard as single deletion.');
        } catch (LogicException) {
            $this->assertFalse($allowed->fresh()->trashed());
            $this->assertFalse($blocked->fresh()->trashed());
        }
    }

    public function test_mass_delete_cannot_delete_a_property_in_an_active_auction(): void
    {
        $assignment = $this->assignment('Parent');
        $property = Property::create(['name' => 'Auction property', 'assignment_id' => $assignment->id]);
        $auction = Auction::create(['name' => 'Auction', 'assignment_id' => $assignment->id]);
        DB::table('auction_properties')->insert(['auction_id' => $auction->id, 'property_id' => $property->id]);

        try {
            app(DeleteTenantRecordsAction::class)->execute(collect([$property]));
            $this->fail('Mass deletion must reject a property used by an active auction.');
        } catch (LogicException) {
            $this->assertFalse($property->fresh()->trashed());
        }
    }

    public function test_mass_delete_soft_deletes_available_records_and_returns_the_count(): void
    {
        $first = $this->assignment('First');
        $second = $this->assignment('Second');

        $this->assertSame(2, app(DeleteTenantRecordsAction::class)->execute(collect([$first, $second])));
        $this->assertSame(2, Assignment::onlyTrashed()->count());
    }

    public function test_mass_delete_rejects_central_model_input_before_database_queries(): void
    {
        $this->authorizeDeletion();
        $queries = [];
        DB::listen(function ($query) use (&$queries): void { $queries[] = $query->sql; });

        $this->postJson('/asset-safety/mass-delete', ['model' => 'Plan', 'ids' => [1]])->assertForbidden();
        $this->assertSame([], $queries);
    }

    public function test_mass_delete_requires_the_resource_delete_capability(): void
    {
        $this->authorizeDeletion(false);
        $assignment = $this->assignment('Retained');

        $this->postJson('/asset-safety/mass-delete', ['model' => 'Assignment', 'ids' => [$assignment->id]])->assertForbidden();
        $this->assertFalse($assignment->fresh()->trashed());
    }

    public function test_mass_delete_rejects_missing_ids_without_deleting_the_available_subset(): void
    {
        $this->authorizeDeletion();
        $assignment = $this->assignment('Retained');

        $this->postJson('/asset-safety/mass-delete', ['model' => 'Assignment', 'ids' => [$assignment->id, 999]])
            ->assertUnprocessable()->assertJsonValidationErrors('ids');
        $this->assertFalse($assignment->fresh()->trashed());
    }

    public function test_mass_delete_returns_422_for_a_domain_conflict_and_keeps_every_record(): void
    {
        $this->authorizeDeletion();
        $allowed = $this->assignment('Allowed');
        $blocked = $this->assignment('Blocked');
        Property::create(['name' => 'Child', 'assignment_id' => $blocked->id]);

        $this->postJson('/asset-safety/mass-delete', ['model' => 'Assignment', 'ids' => [$allowed->id, $blocked->id]])
            ->assertUnprocessable()->assertJsonPath('success', false);
        $this->assertFalse($allowed->fresh()->trashed());
        $this->assertFalse($blocked->fresh()->trashed());
    }

    public function test_mass_delete_rejects_duplicate_ids(): void
    {
        $this->authorizeDeletion();
        $assignment = $this->assignment('Retained');

        $this->postJson('/asset-safety/mass-delete', ['model' => 'Assignment', 'ids' => [$assignment->id, $assignment->id]])
            ->assertUnprocessable()->assertJsonValidationErrors('ids.0');
        $this->assertFalse($assignment->fresh()->trashed());
    }

    public function test_mass_delete_accepts_the_existing_comma_separated_ids_contract(): void
    {
        $this->authorizeDeletion();
        $first = $this->assignment('First');
        $second = $this->assignment('Second');

        $this->postJson('/asset-safety/mass-delete', ['model' => 'Assignment', 'ids' => $first->id.','.$second->id])
            ->assertOk()->assertJsonPath('success', true);
        $this->assertSame(2, Assignment::onlyTrashed()->count());
    }

    public function test_mass_delete_cannot_delete_the_primary_user(): void
    {
        $user = User::withoutEvents(fn (): User => User::create(['name' => 'Primary']));
        $this->authorizeDeletion();

        try {
            app(DeleteTenantRecordsAction::class)->execute(collect([$user]));
            $this->fail('The primary-user guard must also apply to mass deletion.');
        } catch (LogicException) {
            $this->assertFalse($user->fresh()->trashed());
        }
    }

    public function test_mass_delete_cannot_delete_the_authenticated_user(): void
    {
        User::withoutEvents(fn (): User => User::create(['name' => 'Primary']));
        $user = User::withoutEvents(fn (): User => User::create(['name' => 'Current']));
        $this->actingAs($user, 'tenant');

        try {
            app(DeleteTenantRecordsAction::class)->execute(collect([$user]));
            $this->fail('The self-deletion guard must also apply to mass deletion.');
        } catch (LogicException) {
            $this->assertFalse($user->fresh()->trashed());
        }
    }

    public function test_mass_delete_cannot_delete_a_protected_role(): void
    {
        $role = \Spatie\Permission\Models\Role::create(['name' => 'Admin', 'guard_name' => 'tenant']);

        try {
            app(DeleteTenantRecordsAction::class)->execute(collect([$role]));
            $this->fail('The protected-role guard must also apply to mass deletion.');
        } catch (LogicException) {
            $this->assertNotNull($role->fresh());
        }
    }

    public function test_assignment_controller_preserves_action_validation_errors(): void
    {
        $assignment = $this->assignment('Target');
        $other = $this->assignment('Other');
        $property = Property::create(['name' => 'Claimed', 'assignment_id' => $other->id]);
        $request = UpdateAssignmentRequest::create('/assignment', 'PUT', ['property_ids' => [$property->id]]);
        $request->setValidator(Validator::make($request->all(), ['property_ids' => ['array']]));

        $this->expectException(ValidationException::class);
        app(AssignmentsController::class)->update($request, $assignment, app(UpdateAssignmentAction::class));
    }

    private function authorizeDeletion(bool $allowed = true): void
    {
        $this->actingAs((new User)->forceFill(['id' => 99, 'name' => 'Actor']), 'tenant');
        Gate::before(static fn ($actor, string $ability): bool => $allowed);
    }

    private function assignment(string $name): Assignment
    {
        return Assignment::create(['name' => $name]);
    }

    private function attachDeed(Property $property, string $path): Attachment
    {
        $this->disk->put($path, 'old deed');

        return $property->attachments()->create([
            'disk' => 'tenant_public', 'file_path' => $path, 'original_name' => 'old.pdf',
            'mime_type' => 'application/pdf', 'extension' => 'pdf', 'file_size' => 8,
            'category' => PropertyAttachmentCategory::DEED_DOCUMENT->value,
        ]);
    }
}
