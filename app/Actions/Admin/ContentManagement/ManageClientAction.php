<?php

declare(strict_types=1);

namespace App\Actions\Admin\ContentManagement;

use App\Models\Admin\ContentManagement\Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ManageClientAction
{
    public function create(array $data, ?UploadedFile $logo): Client
    {
        $path = $logo?->store('clients', 'public');
        try {
            return DB::transaction(fn (): Client => Client::query()->create($path ? $data + ['logo' => $path] : $data));
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $exception;
        }
    }

    public function update(Client $client, array $data, ?UploadedFile $logo): Client
    {
        $oldPath = $client->logo;
        $path = $logo?->store('clients', 'public');
        try {
            $updated = DB::transaction(function () use ($client, $data, $path): Client {
                $client->update($path ? $data + ['logo' => $path] : $data);

                return $client->refresh();
            });
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $exception;
        }
        if ($path && $oldPath && $oldPath !== $path) {
            Storage::disk('public')->delete($oldPath);
        }

        return $updated;
    }

    public function toggleStatus(Client $client): bool
    {
        return DB::transaction(function () use ($client): bool {
            $status = ! $client->status;
            $client->update(['status' => $status]);

            return $status;
        });
    }

    public function delete(Client $client): void
    {
        $path = $client->logo;
        DB::transaction(fn () => $client->delete());
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
