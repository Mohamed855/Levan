<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Companies;

use App\Models\Tenant\Company;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class SaveCompanyAction
{
    public function create(array $data, ?UploadedFile $logo = null): Company
    {
        return DB::transaction(function () use ($data, $logo): Company {
            $logoPath = null;
            if ($logo) {
                $logoPath = $logo->store('companies/logos', 'public');
            }

            $company = Company::query()->create([
                'name'      => $data['name'],
                'logo_path' => $logoPath,
                'status'    => $data['status'] ?? 'active',
            ]);

            if (! empty($data['authorized_people']) && is_array($data['authorized_people'])) {
                foreach ($data['authorized_people'] as $person) {
                    if (! empty($person['name'])) {
                        $company->authorizedPeople()->create([
                            'name'       => $person['name'],
                            'position'   => $person['position'] ?? null,
                            'phone'      => $person['phone'] ?? null,
                            'email'      => $person['email'] ?? null,
                            'is_default' => (bool) ($person['is_default'] ?? false),
                        ]);
                    }
                }
            }

            return $company;
        });
    }

    public function update(Company $company, array $data, ?UploadedFile $logo = null): Company
    {
        return DB::transaction(function () use ($company, $data, $logo): Company {
            $updateData = [
                'name'   => $data['name'],
                'status' => $data['status'] ?? $company->status,
            ];

            if ($logo) {
                if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
                    Storage::disk('public')->delete($company->logo_path);
                }
                $updateData['logo_path'] = $logo->store('companies/logos', 'public');
            }

            $company->update($updateData);

            if (isset($data['authorized_people']) && is_array($data['authorized_people'])) {
                $company->authorizedPeople()->delete();
                foreach ($data['authorized_people'] as $person) {
                    if (! empty($person['name'])) {
                        $company->authorizedPeople()->create([
                            'name'       => $person['name'],
                            'position'   => $person['position'] ?? null,
                            'phone'      => $person['phone'] ?? null,
                            'email'      => $person['email'] ?? null,
                            'is_default' => (bool) ($person['is_default'] ?? false),
                        ]);
                    }
                }
            }

            return $company->refresh();
        });
    }

    public function toggleStatus(Company $company): string
    {
        return DB::transaction(function () use ($company): string {
            $newStatus = $company->status === 'active' ? 'inactive' : 'active';
            $company->update(['status' => $newStatus]);

            return $newStatus;
        });
    }

    public function delete(Company $company): void
    {
        DB::transaction(fn () => $company->delete());
    }
}
