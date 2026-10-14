<?php

declare(strict_types=1);

namespace App\Actions\Admin\ContentManagement;

use App\Models\Admin\ContentManagement\Portfolio;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ManagePortfolioAction
{
    public function create(array $data, ?UploadedFile $logo): Portfolio
    {
        $path = $logo?->store('portfolios', 'public');
        try {
            return DB::transaction(fn (): Portfolio => Portfolio::query()->create($path ? $data + ['logo' => $path] : $data));
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $exception;
        }
    }

    public function update(Portfolio $portfolio, array $data, ?UploadedFile $logo): Portfolio
    {
        $oldPath = $portfolio->logo;
        $path = $logo?->store('portfolios', 'public');
        try {
            $updated = DB::transaction(function () use ($portfolio, $data, $path): Portfolio {
                $portfolio->update($path ? $data + ['logo' => $path] : $data);

                return $portfolio->refresh();
            });
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $exception;
        }
        if ($path && $oldPath && $oldPath !== $path) {
            Storage::disk('public')->delete($oldPath);
        }

        return $updated;
    }

    public function toggleStatus(Portfolio $portfolio): bool
    {
        return DB::transaction(function () use ($portfolio): bool {
            $status = ! $portfolio->status;
            $portfolio->update(['status' => $status]);

            return $status;
        });
    }

    public function delete(Portfolio $portfolio): void
    {
        $path = $portfolio->logo;
        DB::transaction(fn () => $portfolio->delete());
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
