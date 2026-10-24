<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Shared\WizardDraft;
use App\Models\Tenant\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class WizardDraftLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_draft_keeps_uploaded_file_and_deletes_it_when_discarded(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $resource = 'tenant.auctions.create';

        $this->actingAs($user, 'tenant')->post(route('tenant.wizard-drafts.store'), [
            'resource_key' => $resource,
            'name' => 'نص تجريبي نص تجريبي',
            'cover_image' => UploadedFile::fake()->image('cover.png'),
        ])->assertOk()->assertJsonPath('success', true);

        $draft = WizardDraft::query()->where('resource_key', $resource)->firstOrFail();
        $path = $draft->draft_files['cover_image']['path'];
        Storage::disk('local')->assertExists($path);

        $this->actingAs($user, 'tenant')->getJson(route('tenant.wizard-drafts.index', ['resource_key' => $resource]))
            ->assertOk()->assertJsonPath('data.name', 'نص تجريبي نص تجريبي')->assertJsonPath('files.cover_image.name', 'cover.png');

        $this->actingAs($user, 'tenant')->delete(route('tenant.wizard-drafts.destroy'), ['resource_key' => $resource])->assertOk();
        $this->assertDatabaseMissing('wizard_drafts', ['id' => $draft->id]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_tenant_can_remove_one_file_without_discarding_its_draft(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $resource = 'tenant.auctions.create';

        $this->actingAs($user, 'tenant')->post(route('tenant.wizard-drafts.store'), [
            'resource_key' => $resource,
            'cover_image' => UploadedFile::fake()->image('cover.png'),
        ])->assertOk();

        $draft = WizardDraft::query()->where('resource_key', $resource)->firstOrFail();
        $path = $draft->draft_files['cover_image']['path'];

        $this->actingAs($user, 'tenant')->deleteJson(route('tenant.wizard-drafts.file.destroy'), [
            'resource_key' => $resource,
            'file_key' => 'cover_image',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('wizard_drafts', ['id' => $draft->id]);
        Storage::disk('local')->assertMissing($path);
        $this->assertSame([], $draft->fresh()->draft_files);
    }
}
