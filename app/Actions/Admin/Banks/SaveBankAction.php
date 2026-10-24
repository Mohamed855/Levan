<?php

declare(strict_types=1);

namespace App\Actions\Admin\Banks;

use App\Models\Admin\Bank;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class SaveBankAction
{
    public function create(array $data, ?UploadedFile $logo = null): Bank
    {
        $logoPath = $logo?->store('banks', 'public');

        try {
            return DB::transaction(function () use ($data, $logoPath): Bank {
            if ($logoPath !== null) {
                $data['logo_path'] = $logoPath;
            }
            $data['is_active'] = (bool) ($data['is_active'] ?? true);

            return Bank::create($data);
            });
        } catch (\Throwable $exception) {
            if ($logoPath !== null && Storage::disk('public')->exists($logoPath)) {
                Storage::disk('public')->delete($logoPath);
            }

            throw $exception;
        }
    }

    public function update(Bank $bank, array $data, ?UploadedFile $logo = null): Bank
    {
        $logoPath = $logo?->store('banks', 'public');

        try {
            return DB::transaction(function () use ($bank, $data, $logoPath): Bank {
            $lockedBank = Bank::query()->lockForUpdate()->findOrFail($bank->getKey());
            $previousLogoPath = $lockedBank->logo_path;

            if ($logoPath !== null) {
                $data['logo_path'] = $logoPath;
            }

            if (array_key_exists('is_active', $data)) {
                $data['is_active'] = (bool) $data['is_active'];
            }

            $lockedBank->update($data);

            if ($logoPath !== null && $previousLogoPath !== null && $previousLogoPath !== $logoPath) {
                DB::afterCommit(function () use ($previousLogoPath): void {
                    if (Storage::disk('public')->exists($previousLogoPath)) {
                        Storage::disk('public')->delete($previousLogoPath);
                    }
                });
            }

            return $lockedBank->refresh();
            });
        } catch (\Throwable $exception) {
            if ($logoPath !== null && Storage::disk('public')->exists($logoPath)) {
                Storage::disk('public')->delete($logoPath);
            }

            throw $exception;
        }
    }

    public function updateStatus(Bank $bank, bool $isActive): bool
    {
        $bank->update(['is_active' => $isActive]);

        return $isActive;
    }
}
