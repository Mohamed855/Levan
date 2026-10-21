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
        return DB::transaction(function () use ($data, $logo): Bank {
            if ($logo) {
                $data['logo_path'] = $logo->store('banks', 'public');
            }

            $data['is_active'] = (bool) ($data['is_active'] ?? true);

            return Bank::create($data);
        });
    }

    public function update(Bank $bank, array $data, ?UploadedFile $logo = null): Bank
    {
        return DB::transaction(function () use ($bank, $data, $logo): Bank {
            if ($logo) {
                if ($bank->logo_path) {
                    Storage::disk('public')->delete($bank->logo_path);
                }
                $data['logo_path'] = $logo->store('banks', 'public');
            }

            if (array_key_exists('is_active', $data)) {
                $data['is_active'] = (bool) $data['is_active'];
            }

            $bank->update($data);

            return $bank;
        });
    }

    public function toggleStatus(Bank $bank): bool
    {
        $newStatus = ! $bank->is_active;
        $bank->update(['is_active' => $newStatus]);
        return $newStatus;
    }
}
