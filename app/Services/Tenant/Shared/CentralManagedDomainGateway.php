<?php

declare(strict_types=1);

namespace App\Services\Tenant\Shared;

use App\Models\Admin\ControlDomain;
use App\Models\Admin\ManagedDomain;
use App\Models\Admin\Subscriptions\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Stancl\Tenancy\Exceptions\TenancyNotInitializedException;

final class CentralManagedDomainGateway
{
    public function queryForCurrentTenant(): Builder
    {
        return $this->domains()->where('tenant_id', $this->tenantId());
    }

    public function findForCurrentTenant(int|string $id): ManagedDomain
    {
        return $this->queryForCurrentTenant()->whereKey($id)->firstOrFail();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, ControlDomain> */
    public function activePlatformDomains()
    {
        return (new ControlDomain())->setConnection($this->connection())->newQuery()->where('is_active', true)->orderBy('domain')->get();
    }

    /** @return array{total:int,active:int,pending:int,expiring:int} */
    public function stats(): array
    {
        $query = $this->queryForCurrentTenant();

        return [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->where('is_active', true)->count(),
            'pending' => (clone $query)->whereIn('status', ['pending_dns', 'pending_transfer', 'pending_registration'])->count(),
            'expiring' => (clone $query)->whereNotNull('expires_at')->whereBetween('expires_at', [now(), now()->addDays(30)])->count(),
        ];
    }

    /** @return array{domain:ManagedDomain,verification_token:string} */
    public function linkExternalDomain(string $domain): array
    {
        $this->assertDomainFeature();
        $domain = $this->normalizeDomain($domain);
        $token = Str::lower(Str::random(32));

        $model = DB::connection($this->connection())->transaction(function () use ($domain, $token): ManagedDomain {
            $existing = $this->domains()->where('domain', $domain)->lockForUpdate()->first();
            if ($existing !== null && $existing->tenant_id !== $this->tenantId()) {
                throw new RuntimeException('هذا النطاق مرتبط بمنشأة أخرى.');
            }
            if ($existing !== null) {
                $existing->update([
                    'type' => 'linked', 'status' => 'pending_dns', 'is_active' => false,
                    'verification_token' => $token, 'verified_at' => null,
                ]);

                return $existing->fresh();
            }

            return $this->domains()->create([
                'tenant_id' => $this->tenantId(), 'domain' => $domain, 'type' => 'linked',
                'status' => 'pending_dns', 'verification_token' => $token,
            ]);
        });

        return ['domain' => $model, 'verification_token' => $token];
    }

    public function createPlatformSubdomain(string $label, int $controlDomainId): ManagedDomain
    {
        $this->assertDomainFeature();
        $label = Str::lower(trim($label));
        if (! preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $label)) {
            throw new RuntimeException('اسم النطاق الفرعي غير صالح.');
        }
        $controlDomain = (new ControlDomain())->setConnection($this->connection())->newQuery()
            ->whereKey($controlDomainId)->where('is_active', true)->firstOrFail();
        $domain = $label.'.'.Str::lower($controlDomain->domain);

        return DB::connection($this->connection())->transaction(function () use ($domain): ManagedDomain {
            $existing = $this->domains()->where('domain', $domain)->lockForUpdate()->first();
            if ($existing !== null) {
                if ($existing->tenant_id === $this->tenantId()) {
                    return $existing;
                }
                throw new RuntimeException('اسم النطاق الفرعي مستخدم بالفعل.');
            }

            return $this->domains()->create([
                'tenant_id' => $this->tenantId(), 'domain' => $domain, 'type' => 'platform_subdomain',
                'status' => 'pending_provisioning', 'is_active' => false,
            ]);
        });
    }

    public function verifyExternalDomain(ManagedDomain $domain): ManagedDomain
    {
        if ($domain->tenant_id !== $this->tenantId() || $domain->type !== 'linked') {
            abort(404);
        }
        $token = $domain->verification_token;
        if (! is_string($token) || $token === '') {
            throw new RuntimeException('لا يوجد رمز تحقق صالح لهذا النطاق. أعد طلب الربط.');
        }
        if (! $this->hasVerificationRecord($domain->domain, $token)) {
            throw new RuntimeException('لم يُعثر على سجل DNS المطلوب بعد. أضف TXT بالقيمة levan-verification='.$token.' ثم أعد المحاولة.');
        }

        return DB::connection($this->connection())->transaction(function () use ($domain): ManagedDomain {
            $locked = $this->domains()->whereKey($domain->getKey())->where('tenant_id', $this->tenantId())->lockForUpdate()->firstOrFail();
            $this->domains()->where('tenant_id', $this->tenantId())->whereKeyNot($locked->getKey())->update(['is_active' => false]);
            $locked->update(['status' => 'active', 'is_active' => true, 'verified_at' => now()]);

            return $locked->fresh();
        });
    }

    public function delete(ManagedDomain $domain): void
    {
        if ($domain->tenant_id !== $this->tenantId()) {
            abort(404);
        }
        if (in_array($domain->status, ['pending_registration', 'pending_transfer'], true)) {
            throw new RuntimeException('لا يمكن حذف طلب نطاق لدى مزود التسجيل وهو قيد المعالجة.');
        }
        $this->domains()->whereKey($domain->getKey())->where('tenant_id', $this->tenantId())->delete();
    }

    private function assertDomainFeature(): void
    {
        $eligible = (new Subscription())->setConnection($this->connection())->newQuery()->with('plan')
            ->where('tenant_id', $this->tenantId())->whereIn('status', ['active', 'trial'])->latest('id')->first()?->plan?->domain_management_enabled;
        if (! $eligible) {
            throw new RuntimeException('إدارة النطاقات غير مفعلة في باقة المنشأة الحالية.');
        }
    }

    private function hasVerificationRecord(string $domain, string $token): bool
    {
        $records = @dns_get_record($domain, DNS_TXT);
        if (! is_array($records)) {
            return false;
        }
        $expected = 'levan-verification='.$token;

        foreach ($records as $record) {
            if (isset($record['txt']) && hash_equals($expected, (string) $record['txt'])) {
                return true;
            }
        }

        return false;
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = Str::lower(rtrim(trim($domain), '.'));
        if (! preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $domain)) {
            throw new RuntimeException('اسم النطاق غير صالح.');
        }
        return $domain;
    }

    private function domains(): Builder { return (new ManagedDomain())->setConnection($this->connection())->newQuery(); }
    private function connection(): string
    {
        $connection = (string) config('tenancy.database.central_connection');
        if ($connection === '') { throw new RuntimeException('The central database connection must be configured.'); }
        return $connection;
    }
    private function tenantId(): string
    {
        $id = tenant('id');
        if ($id === null || $id === '') { throw new TenancyNotInitializedException(); }
        return (string) $id;
    }
}
