<?php

declare(strict_types=1);

namespace App\Actions\Admin\Settings;

use App\Models\Admin\ControlDomain\ControlDomain;
use Illuminate\Support\Facades\DB;

final class ManageControlDomainAction
{
    public function create(array $data): ControlDomain
    {
        return DB::transaction(fn (): ControlDomain => ControlDomain::query()->create($data));
    }

    public function update(ControlDomain $domain, array $data): ControlDomain
    {
        return DB::transaction(function () use ($domain, $data): ControlDomain {
            $domain->update($data);

            return $domain->refresh();
        });
    }

    public function toggleStatus(ControlDomain $domain): bool
    {
        return DB::transaction(function () use ($domain): bool {
            $status = ! $domain->is_active;
            $domain->update(['is_active' => $status]);

            return $status;
        });
    }

    public function delete(ControlDomain $domain): void
    {
        DB::transaction(fn () => $domain->delete());
    }
}
