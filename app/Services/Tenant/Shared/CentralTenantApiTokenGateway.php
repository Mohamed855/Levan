<?php

declare(strict_types=1);

namespace App\Services\Tenant\Shared;

use App\Models\Admin\TenantApiToken;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Stancl\Tenancy\Exceptions\TenancyNotInitializedException;

final class CentralTenantApiTokenGateway
{
    /** @return array{token: TenantApiToken, plain_text: string} */
    public function rotate(string $name = 'default'): array
    {
        $plainText = 'levan_'.Str::random(48);
        $tenantId = $this->tenantId();

        $token = DB::connection($this->connection())->transaction(function () use ($tenantId, $name, $plainText): TenantApiToken {
            $this->tokens()->where('tenant_id', $tenantId)->whereNull('revoked_at')->lockForUpdate()->update(['revoked_at' => now()]);

            return $this->tokens()->create([
                'tenant_id' => $tenantId,
                'name' => $name,
                'token_prefix' => substr($plainText, 0, 12),
                'token_hash' => hash('sha256', $plainText),
            ]);
        });

        return ['token' => $token, 'plain_text' => $plainText];
    }

    public function activeToken(): ?TenantApiToken
    {
        return $this->tokens()->where('tenant_id', $this->tenantId())->whereNull('revoked_at')->latest('id')->first();
    }

    public function revoke(): void
    {
        $this->tokens()->where('tenant_id', $this->tenantId())->whereNull('revoked_at')->update(['revoked_at' => now()]);
    }

    private function tokens(): Builder
    {
        return (new TenantApiToken())->setConnection($this->connection())->newQuery();
    }

    private function connection(): string
    {
        $connection = (string) config('tenancy.database.central_connection');
        if ($connection === '') {
            throw new RuntimeException('The central database connection must be configured.');
        }
        return $connection;
    }

    private function tenantId(): string
    {
        $id = tenant('id');
        if ($id === null || $id === '') {
            throw new TenancyNotInitializedException();
        }
        return (string) $id;
    }
}
