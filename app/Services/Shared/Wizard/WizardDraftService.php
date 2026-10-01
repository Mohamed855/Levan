<?php

declare(strict_types=1);

namespace App\Services\Shared\Wizard;

use App\Models\Shared\WizardDraft;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WizardDraftService
{
    private const DISK = 'local';

    public function save(Request $request, string $resourceKey, int $step): WizardDraft
    {
        $identity = $this->identity($request);
        $draft = WizardDraft::firstOrNew([
            'guard' => $identity['guard'],
            'user_id' => $identity['user_id'],
            'tenant_id' => $identity['tenant_id'],
            'resource_key' => $resourceKey,
        ]);

        if (! $draft->getKey()) {
            $draft->id = (string) Str::uuid();
        }

        $draft->draft_data = array_replace_recursive(
            $draft->draft_data ?? [],
            $this->withoutSecrets($request->except([
                '_token', '_method', '_wizard_draft_id', '_wizard_resource_key', '_wizard_step', 'fields', 'target_path',
                'resource_key', 'step',
            ])),
        );
        $draft->draft_files = array_replace_recursive(
            $draft->draft_files ?? [],
            $this->storeFiles($request->allFiles(), $draft->getKey()),
        );
        $draft->last_step = $step;
        $draft->save();

        return $draft;
    }

    public function find(Request $request, string $resourceKey, ?string $draftId = null): ?WizardDraft
    {
        $identity = $this->identity($request);

        return WizardDraft::query()
            ->where([
                'guard' => $identity['guard'],
                'user_id' => $identity['user_id'],
                'tenant_id' => $identity['tenant_id'],
                'resource_key' => $resourceKey,
            ])
            ->when($draftId, fn ($query) => $query->whereKey($draftId))
            ->latest('updated_at')
            ->first();
    }

    public function discard(Request $request, string $resourceKey): bool
    {
        $draft = $this->find($request, $resourceKey);

        if (! $draft) {
            return false;
        }

        return (bool) $draft->delete();
    }

    public function mergeIntoRequest(Request $request, string $draftId, string $resourceKey): array
    {
        $draft = $this->find($request, $resourceKey, $draftId);

        if (! $draft) {
            return [$request->all(), $request->allFiles()];
        }

        return [
            array_replace_recursive($draft->draft_data ?? [], $request->all()),
            array_replace_recursive($this->restoreFiles($draft->draft_files ?? []), $request->allFiles()),
        ];
    }

    private function identity(Request $request): array
    {
        $guard = auth('admin')->check() ? 'admin' : 'tenant';
        $userId = auth($guard)->id();

        abort_unless($userId, 401);

        return [
            'guard' => $guard,
            'user_id' => (int) $userId,
            'tenant_id' => tenant()?->getKey() ?? '',
        ];
    }

    private function withoutSecrets(array $data): array
    {
        foreach ($data as $key => $value) {
            if (preg_match('/(?:password|password_confirmation|current_password)/i', (string) $key)) {
                unset($data[$key]);
                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->withoutSecrets($value);
            }
        }

        return $data;
    }

    private function storeFiles(array $files, string $draftId): array
    {
        $stored = [];

        foreach ($files as $key => $file) {
            if (is_array($file)) {
                $stored[$key] = $this->storeFiles($file, $draftId);
                continue;
            }

            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $path = $file->storeAs(
                'wizard-drafts/'.$draftId,
                Str::uuid()->toString().'.'.$file->getClientOriginalExtension(),
                self::DISK,
            );

            $stored[$key] = [
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
            ];
        }

        return $stored;
    }

    private function restoreFiles(array $files): array
    {
        $restored = [];

        foreach ($files as $key => $file) {
            if (is_array($file) && ! array_key_exists('path', $file)) {
                $restored[$key] = $this->restoreFiles($file);
                continue;
            }

            if (! is_array($file) || empty($file['path']) || ! Storage::disk(self::DISK)->exists($file['path'])) {
                continue;
            }

            $restored[$key] = new UploadedFile(
                Storage::disk(self::DISK)->path($file['path']),
                $file['name'] ?? basename($file['path']),
                $file['mime'] ?? null,
                null,
                true,
            );
        }

        return $restored;
    }
}
