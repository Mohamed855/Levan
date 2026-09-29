<?php

namespace App\Services\Admin\SubscriptionManagement;

use App\Models\Admin\Tenants\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Persists wizard progress only; it never calls tenant provisioning services. */
class CompanyDraftService
{
    public function forAdmin(int $adminId): ?Tenant
    {
        return Tenant::query()->where('status', 'draft')->where('drafted_by', $adminId)->latest('updated_at')->first();
    }

    public function save(int $adminId, array $data, int $step): Tenant
    {
        return DB::connection('mysql')->transaction(function () use ($adminId, $data, $step) {
            $draft = $this->forAdmin($adminId);
            $payload = array_merge($draft?->draft_data ?? [], $this->safeData($data));

            if (! $draft) {
                $draft = Tenant::create([
                    'id' => 'draft_' . Str::uuid(),
                    'folderName' => 'draft',
                    'db_database' => 'draft',
                    'db_username' => 'draft',
                    'db_password' => 'draft',
                    'company_name' => $payload['company_name'] ?? 'مسودة شركة',
                    'commercial_number' => $payload['commercial_number'] ?? (string) random_int(1000000000, 9999999999),
                    'status' => 'draft',
                    'drafted_by' => $adminId,
                ]);
            }

            $draft->update([
                'company_name' => $payload['company_name'] ?? $draft->company_name,
                'commercial_number' => $payload['commercial_number'] ?? $draft->commercial_number,
                'tax_number' => $payload['tax_number'] ?? null,
                'city_id' => $payload['city_id'] ?? null,
                'address' => $payload['address'] ?? null,
                'draft_step' => max(1, min(4, $step)),
                'draft_data' => $payload,
            ]);

            return $draft->fresh();
        });
    }

    public function discardForAdmin(int $adminId): void
    {
        $this->forAdmin($adminId)?->forceDelete();
    }

    private function safeData(array $data): array
    {
        return collect($data)->except(['receipt_upload', '_token', '_method', 'draft_step', 'password', 'password_confirmation'])->all();
    }
}
