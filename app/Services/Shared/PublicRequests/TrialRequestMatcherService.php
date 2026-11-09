<?php

declare(strict_types=1);

namespace App\Services\Shared\PublicRequests;

use App\Enums\Admin\PublicRequests\TrialRequestStatus;
use App\Models\Admin\PublicRequests\TrialRequest;
use App\Models\Admin\Tenants\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class TrialRequestMatcherService
{
    /**
     * Normalize Arabic digits to western digits.
     */
    public static function normalizeDigits(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return str_replace(
            ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            $value
        );
    }

    /**
     * Get candidate phone variations (05... and 9665...).
     *
     * @return list<string>
     */
    public static function phoneCandidates(?string $phone): array
    {
        $normalized = self::normalizeDigits($phone);
        $clean = preg_replace('/\D+/', '', (string) $normalized);
        if ($clean === '') {
            return [];
        }

        $candidates = [$clean];
        if (str_starts_with($clean, '05') && strlen($clean) === 10) {
            $candidates[] = '966' . substr($clean, 1);
        } elseif (str_starts_with($clean, '9665') && strlen($clean) === 12) {
            $candidates[] = '0' . substr($clean, 3);
        }

        return array_values(array_unique($candidates));
    }

    /**
     * Find an existing tenant that matches any of the unique client data of the given trial request.
     * Unique client data: commercial_number, tax_number, company_name, customer_email, customer_phone.
     */
    public function findMatchingTenant(TrialRequest $trialRequest): ?Tenant
    {
        $baseQuery = Tenant::query();
        if (! empty($trialRequest->tenant_id)) {
            $baseQuery->where('id', '!=', $trialRequest->tenant_id);
        }

        $commercialNumber = self::normalizeDigits(trim((string) $trialRequest->commercial_number));
        if (! empty($commercialNumber)) {
            $tenant = (clone $baseQuery)->where('commercial_number', $commercialNumber)->first();
            if ($tenant) {
                return $tenant;
            }
        }

        $taxNumber = self::normalizeDigits(trim((string) $trialRequest->tax_number));
        if (! empty($taxNumber)) {
            $tenant = (clone $baseQuery)->where('tax_number', $taxNumber)->first();
            if ($tenant) {
                return $tenant;
            }
        }

        $companyName = trim((string) $trialRequest->company_name);
        if (! empty($companyName) && $companyName !== 'غير محدد') {
            $tenant = (clone $baseQuery)->where('company_name', $companyName)->first();
            if ($tenant) {
                return $tenant;
            }
        }

        $email = strtolower(trim((string) $trialRequest->customer_email));
        if (! empty($email)) {
            $tenant = (clone $baseQuery)->whereRaw('LOWER(primary_contact_email) = ?', [$email])->first();
            if ($tenant) {
                return $tenant;
            }
        }

        $candidates = self::phoneCandidates($trialRequest->customer_phone);
        if (! empty($candidates)) {
            $tenant = (clone $baseQuery)->whereIn('primary_contact_phone', $candidates)->first();
            if ($tenant) {
                return $tenant;
            }
        }

        return null;
    }

    /**
     * Mark a trial request as already subscribed and link it to the tenant if provided.
     */
    public function markAsAlreadySubscribed(TrialRequest $trialRequest, ?string $tenantId = null, ?int $adminId = null): TrialRequest
    {
        $trialRequest->update([
            'status' => TrialRequestStatus::ALREADY_SUBSCRIBED,
            'tenant_id' => $tenantId ?? $trialRequest->tenant_id,
            'processed_at' => now(),
            'processed_by' => $adminId ?? $trialRequest->processed_by,
            'provisioning_status' => 'ready',
            'provisioning_error' => null,
            'password_hash' => null,
            'plain_password' => null,
        ]);

        Cache::forget('admin:pending_trial_requests_count');

        return $trialRequest;
    }

    /**
     * Find all pending trial requests matching the newly created tenant's unique data,
     * and update their status to "already_subscribed" (مشترك بالفعل).
     *
     * @return Collection<int, TrialRequest>
     */
    public function syncTrialRequestsForTenant(Tenant $tenant, ?int $excludeTrialRequestId = null): Collection
    {
        $commercialNumber = self::normalizeDigits(trim((string) $tenant->commercial_number));
        $taxNumber = self::normalizeDigits(trim((string) $tenant->tax_number));
        $companyName = trim((string) $tenant->company_name);
        $email = strtolower(trim((string) $tenant->primary_contact_email));
        $phoneCandidates = self::phoneCandidates($tenant->primary_contact_phone);

        $query = TrialRequest::query()
            ->where('status', TrialRequestStatus::PENDING);

        if ($excludeTrialRequestId) {
            $query->where('id', '!=', $excludeTrialRequestId);
        }

        $query->where(function ($q) use ($commercialNumber, $taxNumber, $companyName, $email, $phoneCandidates) {
            $hasCondition = false;

            if (! empty($commercialNumber)) {
                $q->orWhere('commercial_number', $commercialNumber);
                $hasCondition = true;
            }

            if (! empty($taxNumber)) {
                $q->orWhere('tax_number', $taxNumber);
                $hasCondition = true;
            }

            if (! empty($companyName) && $companyName !== 'غير محدد') {
                $q->orWhere('company_name', $companyName);
                $hasCondition = true;
            }

            if (! empty($email)) {
                $q->orWhereRaw('LOWER(customer_email) = ?', [$email]);
                $hasCondition = true;
            }

            if (! empty($phoneCandidates)) {
                $q->orWhereIn('customer_phone', $phoneCandidates);
                $hasCondition = true;
            }

            if (! $hasCondition) {
                $q->whereRaw('1 = 0');
            }
        });

        $matchingRequests = $query->get();

        foreach ($matchingRequests as $req) {
            $this->markAsAlreadySubscribed($req, $tenant->id);
        }

        return $matchingRequests;
    }
}
