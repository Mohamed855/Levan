<?php

declare(strict_types=1);

namespace App\Actions\Admin\Settings;

use App\Models\Admin\Bank\Bank;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ManageBankAction
{
    public function create(array $data, ?UploadedFile $icon): Bank
    {
        $storedPath = $icon?->store('banks/icons', 'public');

        try {
            return DB::transaction(fn (): Bank => Bank::query()->create($storedPath ? $data + ['icon' => $storedPath] : $data));
        } catch (Throwable $exception) {
            if ($storedPath) {
                Storage::disk('public')->delete($storedPath);
            }
            throw $exception;
        }
    }

    public function update(Bank $bank, array $data, ?UploadedFile $icon): Bank
    {
        $oldPath = $bank->icon;
        $storedPath = $icon?->store('banks/icons', 'public');

        try {
            $updated = DB::transaction(function () use ($bank, $data, $storedPath): Bank {
                $bank->update($storedPath ? $data + ['icon' => $storedPath] : $data);

                return $bank->refresh();
            });
        } catch (Throwable $exception) {
            if ($storedPath) {
                Storage::disk('public')->delete($storedPath);
            }
            throw $exception;
        }

        if ($storedPath && $oldPath && $oldPath !== $storedPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return $updated;
    }

    public function toggleStatus(Bank $bank): bool
    {
        return DB::transaction(function () use ($bank): bool {
            $status = ! $bank->is_active;
            $bank->update(['is_active' => $status]);

            return $status;
        });
    }

    public function delete(Bank $bank): void
    {
        $path = $bank->icon;
        DB::transaction(fn () => $bank->delete());
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
