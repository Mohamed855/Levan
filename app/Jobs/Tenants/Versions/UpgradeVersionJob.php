<?php

namespace App\Jobs\Tenants\Versions;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Tenants\Settings\Settings;

class UpgradeVersionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout         = 1800;
    public int $tries           = 1;
    public int $maxExceptions   = 1;

    public function __construct(public string $tenantId, public int $currentVersion, public int $targetVersion) {}

    public function handle(): void
    {
        try {
            $this->enableMaintenance();

            $this->runMigrations();

            $this->updateVersion();

            $this->clearCache();

            $this->disableMaintenance();
        } catch (\Throwable $e) {
            $this->handleFailure($e);
            throw $e;
        } finally {
            Cache::forget("tenant:{$this->tenantId}:upgrading");
        }
    }

    /*
    |============================================================================
    |============================================================================
    |                             Private Methods
    |============================================================================
    |============================================================================
    */
    private function enableMaintenance(): void
    {
        Settings::query()->update([
            'maintenance_mode' => true,
            'updated_at'       => now(),
        ]);

        Cache::forget('app_settings');
    }

    private function disableMaintenance(): void
    {
        Settings::query()->update([
            'maintenance_mode' => false,
            'updated_at'       => now(),
        ]);

        Cache::forget('app_settings');
    }

    private function runMigrations(): void
    {
        $nextVersionUrl = config('app.next_version_url');
        $upgradeSecret  = config('app.upgrade_secret');

        $endpoint = "{$nextVersionUrl}/api/tenants/{$this->tenantId}/upgrade";

        $response = Http::timeout(1200)
            ->retry(2, 1000)
            ->withHeaders([
                'X-Secret-Key' => $upgradeSecret,
            ])->post($endpoint);

        if ($response->failed()) {
            throw new \RuntimeException("فشل الـ migrations: " . $response->body());
        }

        $data = $response->json();
        if (!($data['success'] ?? false)) {
            throw new \RuntimeException("خطأ من الـ endpoint: " . ($data['message'] ?? 'unknown'));
        }
    }

    private function updateVersion(): void
    {
        DB::connection('mysql')->table('tenants')->where('id', $this->tenantId)->update([
            'version_id' => $this->targetVersion,
            'updated_at' => now(),
        ]);
    }

    private function clearCache(): void
    {
        $cacheKeys = [
            "ten:{$this->tenantId}:ver_id",
            "tenant:{$this->tenantId}:current_version",
            'latest_stable_active_version_obj',
            'app_settings',
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    private function handleFailure(\Throwable $e): void
    {
        try {
            $this->disableMaintenance();
        } catch (\Throwable $ignored) {
            Log::error("فشل إلغاء وضع الصيانة بعد الخطأ");
        }

        // يمكنك إرسال إشعار للمسؤولين هنا
        // $this->notifyAdmins($e);
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical("💥 فشلت عملية الترقية نهائياً", [
            'tenant_id' => $this->tenantId,
            'error'     => $exception->getMessage(),
        ]);
    }

    /**
     * إرسال إشعار للمسؤولين (اختياري)
     */
    // private function notifyAdmins(\Throwable $e): void
    // {
    //     Mail::to(config('app.admin_email'))->send(
    //         new UpgradeFailedMail($this->tenantId, $e)
    //);
    // }
}