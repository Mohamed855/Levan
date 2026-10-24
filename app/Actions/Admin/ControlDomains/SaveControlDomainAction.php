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

    public function updateStatus(ControlDomain $controlDomain, bool $isActive): bool
    {
        return DB::transaction(function () use ($controlDomain, $isActive): bool {
            $controlDomain->update(['is_active' => $isActive]);

            return $isActive;
        });
    }

    public function delete(ControlDomain $controlDomain): void
    {
        DB::transaction(fn () => $controlDomain->delete());
    }
}
