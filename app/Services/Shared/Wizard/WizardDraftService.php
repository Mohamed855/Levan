<?php

declare(strict_types=1);

namespace App\Services\Shared\Wizard;

use App\Models\Shared\WizardDraft;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class WizardDraftService
{
    private const DISK = 'local';

    public function __construct(private readonly WizardDraftContext $context) {}

    public function save(Request $request, string $resourceKey, int $step): WizardDraft
    {
        $identity = $this->context->identity($request);
        $connection = (new WizardDraft)->setConnection($this->context->connection($request))->getConnection();
        $newPaths = [];
        if ($connection->transactionLevel() > 0) {
            $connection->afterRollBack(function () use (&$newPaths): void { $this->cleanupPaths($newPaths); });
        }

        try {
            return $connection->transaction(function () use ($request, $resourceKey, $step, $identity, $connection, &$newPaths): WizardDraft {
                $draft = WizardDraft::on($connection->getName())->where($identity)->where('resource_key', $resourceKey)->lockForUpdate()->first();
                $draft ??= (new WizardDraft)->setConnection($connection->getName())->fill($identity + ['resource_key' => $resourceKey]);
                if (! $draft->getKey()) {
                    $draft->id = (string) Str::uuid();
                }

                $connection->afterRollBack(function () use (&$newPaths): void { $this->cleanupPaths($newPaths); });
                $excluded = array_merge([
                    '_token', '_method', '_wizard_draft_id', '_wizard_resource_key', '_wizard_step', 'fields', 'target_path',
                    'target_method', 'resource_key', 'step',
                ], array_keys($request->allFiles()));
                $draft->draft_data = $this->mergeData($draft->draft_data ?? [], $this->withoutSecrets($request->except($excluded)));
                $obsoletePaths = [];
                $draft->draft_files = $this->mergeStoredFiles($draft->draft_files ?? [], $this->storeFiles($request->allFiles(), $draft->getKey(), $newPaths), $obsoletePaths);
                $this->validateStoredFileLimits($request, $draft->draft_files);
                $draft->last_step = $step;
                $draft->saveOrFail();
                $connection->afterCommit(fn () => $this->cleanupPaths($obsoletePaths));

                return $draft;
            });
        } catch (Throwable $exception) {
            $this->cleanupPaths($newPaths);
            throw $exception;
        }
    }

    public function find(Request $request, string $resourceKey, ?string $draftId = null): ?WizardDraft
    {
        $identity = $this->context->identity($request);

        return WizardDraft::on($this->context->connection($request))
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

    public function removeFile(Request $request, string $resourceKey, string $fileKey): bool
    {
        $draft = $this->find($request, $resourceKey);
        if (! $draft) {
            return false;
        }

        $files = $draft->draft_files ?? [];
        $file = Arr::get($files, $fileKey);
        if (! is_array($file) || empty($file['path'])) {
            return false;
        }

        if (! Storage::disk(self::DISK)->delete($file['path'])) {
            return false;
        }
        Arr::forget($files, $fileKey);
        $draft->draft_files = $files;
        $draft->save();

        return true;
    }

    public function mergeIntoRequest(Request $request, string $draftId, string $resourceKey): array
    {
        $draft = $this->find($request, $resourceKey, $draftId);

        if (! $draft) {
            return [$request->all(), $request->allFiles()];
        }

        return [
            $this->mergeData($draft->draft_data ?? [], $request->all()),
            $this->mergeUploadedFiles($this->restoreFiles($draft->draft_files ?? []), $request->allFiles()),
        ];
    }

    private function mergeUploadedFiles(array $stored, array $current): array
    {
        foreach ($current as $key => $value) {
            if (is_array($value) && array_is_list($value)) {
                $merged = array_merge(
                    isset($stored[$key]) && is_array($stored[$key]) ? $stored[$key] : [],
                    $value,
                );
                $unique = [];
                foreach ($merged as $file) {
                    if (! $file instanceof UploadedFile || $file->getSize() === 0) {
                        continue;
                    }
                    $unique[$file->getClientOriginalName().'|'.hash_file('sha256', $file->getRealPath())] = $file;
                }
                $stored[$key] = array_values($unique);

                continue;
            }

            if (is_array($value)) {
                $stored[$key] = $this->mergeUploadedFiles(
                    isset($stored[$key]) && is_array($stored[$key]) ? $stored[$key] : [],
                    $value,
                );

                continue;
            }

            if ($value instanceof UploadedFile && $value->getSize() === 0 && isset($stored[$key]) && $stored[$key] instanceof UploadedFile && $stored[$key]->getSize() > 0) {
                continue;
            }

            $stored[$key] = $value;
        }

        return $stored;
    }

    private function mergeData(array $stored, array $current): array
    {
        foreach ($current as $key => $value) {
            $isList = is_array($value) && ($value === [] || count(array_filter(array_keys($value), 'is_int')) === count($value));
            $stored[$key] = is_array($value) && ! $isList && is_array($stored[$key] ?? null)
                ? $this->mergeData($stored[$key], $value)
                : $value;
        }
        return $stored;
    }

    private function withoutSecrets(array $data): array
    {
        foreach ($data as $key => $value) {
            if (preg_match('/(?:password|token|secret|otp|recovery_code|api_key)/i', (string) $key)) {
                unset($data[$key]);

                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->withoutSecrets($value);
            }
        }

        return $data;
    }

    private function storeFiles(array $files, string $draftId, array &$newPaths): array
    {
        $stored = [];

        foreach ($files as $key => $file) {
            if (is_array($file)) {
                $stored[$key] = $this->storeFiles($file, $draftId, $newPaths);

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
            if (! is_string($path)) {
                throw new \RuntimeException('Unable to store the draft upload.');
            }
            $newPaths[] = $path;

            $stored[$key] = [
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'sha256' => hash_file('sha256', $file->getRealPath()),
            ];
        }

        return $stored;
    }

    private function mergeStoredFiles(array $existing, array $incoming, array &$obsoletePaths): array
    {
        foreach ($incoming as $key => $value) {
            if (is_array($value) && array_is_list($value)) {
                $current = isset($existing[$key]) && is_array($existing[$key]) ? $existing[$key] : [];
                $signatures = [];
                foreach ($current as $file) {
                    if (is_array($file)) {
                        $signatures[$this->fileSignature($file)] = true;
                    }
                }
                foreach ($value as $file) {
                    if (! is_array($file)) {
                        continue;
                    }
                    $signature = $this->fileSignature($file);
                    if (isset($signatures[$signature])) {
                        $obsoletePaths[] = $file['path'];

                        continue;
                    }
                    $current[] = $file;
                    $signatures[$signature] = true;
                }
                $existing[$key] = array_values($current);

                continue;
            }

            if (is_array($value) && ! array_key_exists('path', $value)) {
                $existing[$key] = $this->mergeStoredFiles(
                    isset($existing[$key]) && is_array($existing[$key]) ? $existing[$key] : [],
                    $value,
                    $obsoletePaths,
                );

                continue;
            }

            if (isset($existing[$key]['path']) && $existing[$key]['path'] !== ($value['path'] ?? null)) {
                $obsoletePaths[] = $existing[$key]['path'];
            }
            $existing[$key] = $value;
        }

        return $existing;
    }

    private function fileSignature(array $file): string
    {
        $hash = $file['sha256'] ?? null;
        if (! $hash && ! empty($file['path']) && Storage::disk(self::DISK)->exists($file['path'])) {
            $hash = hash_file('sha256', Storage::disk(self::DISK)->path($file['path']));
        }
        return ($file['name'] ?? '').'|'.($hash ?: ($file['path'] ?? ''));
    }

    private function cleanupPaths(array $paths): void
    {
        foreach (array_unique($paths) as $path) {
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                try {
                    if (Storage::disk(self::DISK)->delete($path)) {
                        continue 2;
                    }
                } catch (Throwable) {
                    // A committed draft remains usable even if storage cleanup must be retried.
                }
            }
            Log::warning('Draft file cleanup failed after retries.', ['disk' => self::DISK, 'path' => $path]);
        }
    }

    private function validateStoredFileLimits(Request $request, array $files): void
    {
        $rules = $request instanceof \App\Http\Requests\Shared\Wizard\StoreWizardDraftRequest ? $request->rules() : [];
        $count = 0;
        $bytes = 0;
        $visit = function (array $items) use (&$visit, &$count, &$bytes): void {
            if (isset($items['path'])) {
                $count++;
                $bytes += (int) ($items['size'] ?? 0);
                return;
            }
            foreach ($items as $item) {
                if (is_array($item)) {
                    $visit($item);
                }
            }
        };
        $visit($files);
        foreach ($files as $field => $items) {
            if (! is_array($items) || ! array_is_list($items)) {
                continue;
            }
            $maxima = array_filter($rules[$field] ?? [], fn ($rule) => is_string($rule) && str_starts_with($rule, 'max:'));
            $maximum = $maxima ? (int) substr(reset($maxima), 4) : 50;
            if (count($items) > $maximum) {
                throw \Illuminate\Validation\ValidationException::withMessages([$field => 'عدد ملفات المسودة يتجاوز الحد المسموح.']);
            }
        }
        if ($count > 100 || $bytes > 500 * 1024 * 1024) {
            throw \Illuminate\Validation\ValidationException::withMessages(['resource_key' => 'تجاوزت المسودة حد الملفات أو مساحة التخزين المسموحة.']);
        }
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
