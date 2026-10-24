<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Archive;

use App\Models\Tenant\Assignment;
use App\Models\Tenant\Auction;
use App\Models\Tenant\Company;
use App\Models\Tenant\Property;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class ManageTenantArchiveAction
{
    public function restore(Model $model): void
    {
        DB::transaction(fn () => $model->restore());
    }

    public function forceDelete(Model $model): void
    {
        $files = $this->filesForDeletion($model);

        DB::transaction(function () use ($model): void {
            if (method_exists($model, 'attachments')) {
                $model->attachments()->delete();
            }

            $model->forceDelete();
        });

        foreach ($files as $file) {
            try {
                Storage::disk($file['disk'])->delete($file['path']);
            } catch (\Throwable $exception) {
                Log::warning('Unable to remove tenant archive file after database purge.', [
                    'model' => $model::class,
                    'model_id' => $model->getKey(),
                    'disk' => $file['disk'],
                    'path' => $file['path'],
                    'exception' => $exception->getMessage(),
                ]);
            }
        }
    }

    /** @return array<int, array{disk:string,path:string}> */
    private function filesForDeletion(Model $model): array
    {
        $files = [];
        if (method_exists($model, 'attachments')) {
            foreach ($model->attachments()->get(['disk', 'file_path']) as $attachment) {
                $files[] = ['disk' => (string) ($attachment->disk ?: 'tenant_public'), 'path' => (string) $attachment->file_path];
            }
        }

        foreach (match ($model::class) {
            Property::class => ['main_image', 'aerial_image'],
            Auction::class => ['cover_image'],
            Assignment::class => ['entity_logo'],
            Company::class => ['logo_path'],
            default => [],
        } as $attribute) {
            $files[] = ['disk' => 'tenant_public', 'path' => (string) $model->getAttribute($attribute)];
        }

        $valid = array_filter($files, fn (array $file): bool => $this->isSafeTenantFile($file['disk'], $file['path']));

        return array_values(array_unique($valid, SORT_REGULAR));
    }

    private function isSafeTenantFile(string $disk, string $path): bool
    {
        return $disk === 'tenant_public'
            && $path !== ''
            && ! str_starts_with($path, '/')
            && ! str_contains(str_replace('\\', '/', $path), '../');
    }
}
