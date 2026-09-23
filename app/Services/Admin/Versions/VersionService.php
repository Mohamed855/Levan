<?php

namespace App\Services\Admin\Versions;

use App\Models\Admin\Version\Version;
use Illuminate\Support\Facades\DB;

class VersionService
{
    // Create version
    public function create(array $data): Version
    {
        return DB::transaction(function () use ($data) {

            return Version::create([
                ...$data,
                'created_by'    => auth()->id(),
            ]);
        });
    }


    // Update Version
    public function update(Version $version, array $data): Version
    {
        return DB::transaction(function () use ($version, $data) {
            // تحديث الكوبون
            $version->update([
                ...$data,
                'updated_by'    => auth()->id(),
            ]);

            return $version;
        });
    }


    // Delete Version
    public function delete(Version $version): bool
    {
        return DB::transaction(function () use ($version) {
            return $version->delete();
        });
    }


    // Get Version statistics
    public function getVersionStatistics(): array
    {
        $baseQuery = Version::query();

        return [
            'total'    => (clone $baseQuery)->count(),
            'active'   => (clone $baseQuery)->where('is_active', 1)->count(),
            'inactive' => (clone $baseQuery)->where('is_active', 0)->count(),
        ];
    }
}
