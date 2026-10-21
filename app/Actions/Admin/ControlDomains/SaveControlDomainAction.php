<?php

declare(strict_types=1);

namespace App\Actions\Admin\ControlDomains;

use App\Models\Admin\ControlDomain;
use Illuminate\Support\Facades\DB;

final class SaveControlDomainAction
{
    public function create(array $data): ControlDomain
    {
        return DB::transaction(fn (): ControlDomain => ControlDomain::query()->create([
            'domain'    => $data['domain'],
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]));
    }

    public function update(ControlDomain $controlDomain, array $data): ControlDomain
    {
        return DB::transaction(function () use ($controlDomain, $data): ControlDomain {
            $controlDomain->update([
                'domain'    => $data['domain'],
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : $controlDomain->is_active,
            ]);

            return $controlDomain->refresh();
        });
    }

    public function toggleStatus(ControlDomain $controlDomain): bool
    {
        return DB::transaction(function () use ($controlDomain): bool {
            $status = ! $controlDomain->is_active;
            $controlDomain->update(['is_active' => $status]);

            return $status;
        });
    }

    public function delete(ControlDomain $controlDomain): void
    {
        DB::transaction(fn () => $controlDomain->delete());
    }
}
