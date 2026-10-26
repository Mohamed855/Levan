<?php

declare(strict_types=1);

namespace App\Services\Shared\Notifications;

use App\Events\Dashboard\NotificationsChanged;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Notifications\NotificationDelivery;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use Throwable;

class DashboardNotificationService
{
    public function admins(string $key, array $payload): void
    {
        foreach (AdminUser::where('status', 'active')->get() as $admin) {
            if ($admin->isSuperAdmin()) {
                $this->enqueue('admin', null, (int) $admin->id, $key, $payload);
            }
        }
    }

    public function tenant(string $tenantId, string $key, array $payload, ?int $userId = null): void
    {
        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            return;
        }
        $ids = $tenant->run(function () use ($tenant, $userId): array {
            $query = User::where('status', 'active');
            if ($userId !== null) {
                return $query->whereKey($userId)->pluck('id')->all();
            }

            return $query->where(function ($query) use ($tenant) {
                $query->where('global_uid', $tenant->primary_user_global_uid)->orWhereHas('roles', fn ($roles) => $roles->where('name', 'Admin')->where('guard_name', 'tenant'));
            })->pluck('id')->all();
        });
        foreach ($ids as $id) {
            $this->enqueue('tenant', $tenantId, (int) $id, $key, $payload);
        }
    }

    public function enqueue(string $scope, ?string $tenantId, int $userId, string $key, array $payload): void
    {
        if (! config('dashboard-notifications.enabled', true)) {
            return;
        }
        $id = (string) Uuid::uuid5(Uuid::NAMESPACE_URL, "levan:$scope:$tenantId:$userId:$key");
        $delivery = NotificationDelivery::firstOrCreate(['id' => $id], [
            'scope' => $scope, 'tenant_id' => $tenantId, 'user_id' => $userId, 'payload' => $payload,
        ]);
        if (! $delivery->delivered_at) {
            $this->deliver($delivery);
        }
    }

    public function deliver(NotificationDelivery $delivery): void
    {
        try {
            $write = function ($user) use ($delivery): void {
                if (! $user) {
                    throw new \RuntimeException('Notification recipient no longer exists.');
                }
                $user->notifications()->firstOrCreate(['id' => $delivery->id], [
                    'type' => 'dashboard.event', 'data' => $delivery->payload,
                ]);
            };
            if ($delivery->scope === 'admin') {
                $write(AdminUser::find($delivery->user_id));
            } else {
                $tenant = Tenant::findOrFail($delivery->tenant_id);
                $tenant->run(fn () => $write(User::find($delivery->user_id)));
            }
            $delivery->update(['delivered_at' => now(), 'last_error' => null]);
            $this->refresh($delivery->scope, $delivery->tenant_id, (int) $delivery->user_id);
        } catch (Throwable $exception) {
            $delivery->update(['last_error' => $exception->getMessage()]);
            Log::warning('Dashboard notification delivery pending.', ['delivery_id' => $delivery->id]);
        }
    }

    public function channel(string $scope, ?string $tenantId, int $userId): string
    {
        return $scope === 'admin' ? "dashboard.admin.$userId" : "dashboard.tenant.$tenantId.user.$userId";
    }

    public function refresh(string $scope, ?string $tenantId, int $userId): void
    {
        if (! config('dashboard-notifications.broadcast', true)) {
            return;
        }
        try {
            event(new NotificationsChanged($this->channel($scope, $tenantId, $userId)));
        } catch (Throwable $exception) {
            Log::debug('Dashboard websocket unavailable; persisted notifications remain readable.');
        }
    }

    public static function payload(string $event, string $title, string $message, string $url, string $type = 'info', string $icon = 'ti ti-bell'): array
    {
        return compact('event', 'title', 'message', 'url', 'type', 'icon');
    }
}
