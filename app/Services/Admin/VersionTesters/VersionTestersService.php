<?php

namespace App\Services\Admin\VersionTesters;

use App\Models\Admin\Version\VersionTester;
use Illuminate\Support\Facades\DB;

class VersionTestersService
{
    // Create VersionTester
    public function create(array $data): VersionTester
    {
        return DB::transaction(function () use ($data) {

            return VersionTester::create([
                ...$data,
                'created_by' => auth()->id(),
            ]);
        });
    }

    // Update VersionTester
    public function update(VersionTester $tester, array $data): VersionTester
    {
        return DB::transaction(function () use ($tester, $data) {
            // تحديث الكوبون
            $tester->update([
                ...$data,
                'updated_by' => auth()->id(),
            ]);

            return $tester;
        });
    }

    // Delete VersionTester
    public function delete(VersionTester $tester): bool
    {
        return DB::transaction(function () use ($tester) {
            return $tester->delete();
        });
    }

    // Get VersionTester statistics
    public function getVersionTesterStatistics(): array
    {
        $baseQuery = VersionTester::query();

        return [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('is_active', 1)->count(),
            'inactive' => (clone $baseQuery)->where('is_active', 0)->count(),
        ];
    }
}
