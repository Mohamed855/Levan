<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Notifications;

use App\Models\Tenant\User;
use Illuminate\Support\Facades\DB;

final class ManageUserNotificationsAction
{
    public function markAsRead(User $user, string $notificationId): void
    {
        DB::transaction(fn () => $user->notifications()->where('id', $notificationId)->update(['read_at' => now()]));
    }

    public function markAllAsRead(User $user): void
    {
        DB::transaction(fn () => $user->unreadNotifications()->update(['read_at' => now()]));
    }

    public function delete(User $user, string $notificationId): void
    {
        DB::transaction(fn () => $user->notifications()->where('id', $notificationId)->delete());
    }

    public function deleteMany(User $user, array $notificationIds): int
    {
        return DB::transaction(fn (): int => $user->notifications()->whereIn('id', $notificationIds)->delete());
    }
}
