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
                foreach ($this->authorizedPeople($data['authorized_people']) as $person) {
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
                foreach ($this->authorizedPeople($data['authorized_people']) as $person) {
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

    public function updateStatus(Company $company, string $status): string
    {
        return DB::transaction(function () use ($company, $status): string {
            $company->update(['status' => $status]);

            return $status;
        });
    }

    public function delete(Company $company): void
    {
        DB::transaction(fn () => $company->delete());
    }

    /** @param array<int, array<string, mixed>> $people @return array<int, array<string, mixed>> */
    private function authorizedPeople(array $people): array
    {
        $validPeople = array_values(array_filter($people, fn (array $person): bool => filled($person['name'] ?? null)));
        $defaultFound = false;

        foreach ($validPeople as $index => $person) {
            $isDefault = (bool) ($person['is_default'] ?? false) && ! $defaultFound;
            $validPeople[$index]['is_default'] = $isDefault;
            $defaultFound = $defaultFound || $isDefault;
        }

        if ($validPeople !== [] && ! $defaultFound) {
            $validPeople[0]['is_default'] = true;
        }

        return $validPeople;
    }
}
