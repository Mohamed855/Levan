<?php

declare(strict_types=1);

namespace App\Services\Shared\Wizard;

use App\Models\Shared\WizardDraft;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class WizardDraftService
{
    public function save(Request $request, string $resourceKey, int $step = 1): WizardDraft
    {
        $identity = $this->identity($request);
        $draft = WizardDraft::query()->firstOrNew($identity + ['resource_key' => $resourceKey]);
        $draft->id ??= (string) Str::uuid();
        $draft->draft_data = array_replace_recursive($draft->draft_data ?? [], $this->withoutSecrets($request->except(array_merge(['_token', '_method', 'resource_key', 'step'], array_keys($request->allFiles())))));
        $draft->draft_files = $this->mergeFiles($draft->draft_files ?? [], $this->storeFiles($request->allFiles(), $draft->id));
        $draft->last_step = $step;
        $draft->save();
        return $draft;
    }

    public function find(Request $request, string $resourceKey, ?string $draftId = null): ?WizardDraft
    {
        return WizardDraft::query()->where($this->identity($request) + ['resource_key' => $resourceKey])->when($draftId, fn ($query) => $query->whereKey($draftId))->first();
    }

    public function discard(Request $request, string $resourceKey): bool
    {
        $draft = $this->find($request, $resourceKey);
        return $draft ? (bool) $draft->delete() : false;
    }

    /** @return array{array<string,mixed>,array<string,mixed>} */
    public function mergeIntoRequest(Request $request, string $draftId, string $resourceKey): array
    {
        $draft = $this->find($request, $resourceKey, $draftId);
        if (! $draft) return [$request->all(), $request->allFiles()];
        return [array_replace_recursive($draft->draft_data ?? [], $request->all()), array_replace_recursive($this->restoreFiles($draft->draft_files ?? []), $request->allFiles())];
    }

    /** @return array{guard:string,user_id:int} */
    private function identity(Request $request): array
    {
        $guard = auth('admin')->check() ? 'admin' : 'tenant';
        abort_unless(auth($guard)->check(), 401);
        return ['guard' => $guard, 'user_id' => (int) auth($guard)->id()];
    }

    private function withoutSecrets(array $data): array
    {
        foreach ($data as $key => $value) { if (preg_match('/password/i', (string) $key)) unset($data[$key]); elseif (is_array($value)) $data[$key] = $this->withoutSecrets($value); }
        return $data;
    }

    private function storeFiles(array $files, string $draftId): array
    {
        $stored = [];
        foreach ($files as $key => $file) {
            if (is_array($file)) { $stored[$key] = $this->storeFiles($file, $draftId); continue; }
            if (! $file instanceof UploadedFile || ! $file->isValid()) continue;
            $path = $file->storeAs('wizard-drafts/'.$draftId, Str::uuid().'.'.$file->getClientOriginalExtension(), 'local');
            $stored[$key] = ['path' => $path, 'name' => $file->getClientOriginalName(), 'mime' => $file->getClientMimeType(), 'size' => $file->getSize()];
        }
        return $stored;
    }

    private function mergeFiles(array $existing, array $incoming): array
    {
        foreach ($incoming as $key => $value) {
            if (is_array($value) && ! array_key_exists('path', $value)) { $existing[$key] = $this->mergeFiles($existing[$key] ?? [], $value); continue; }
            if (isset($existing[$key]['path']) && $existing[$key]['path'] !== ($value['path'] ?? null)) Storage::disk('local')->delete($existing[$key]['path']);
            $existing[$key] = $value;
        }
        return $existing;
    }

    private function restoreFiles(array $files): array
    {
        $restored = [];
        foreach ($files as $key => $file) {
            if (is_array($file) && ! array_key_exists('path', $file)) { $restored[$key] = $this->restoreFiles($file); continue; }
            if (! is_array($file) || empty($file['path']) || ! Storage::disk('local')->exists($file['path'])) continue;
            $restored[$key] = new UploadedFile(Storage::disk('local')->path($file['path']), $file['name'], $file['mime'] ?? null, null, true);
        }
        return $restored;
    }
}