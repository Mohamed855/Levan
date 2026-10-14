<?php

declare(strict_types=1);

namespace App\Actions\Tenants\Notification;

use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Support\Facades\DB;

final class ManageUserNotificationsAction
{
    public function markAsRead(User $user, string $notificationId): void
    {
        DB::transaction(fn () => $user->notifications()->findOrFail($notificationId)->markAsRead());
    }

    public function markAllAsRead(User $user): void
    {
        DB::transaction(fn () => $user->unreadNotifications()->update(['read_at' => now()]));
    }

    public function delete(User $user, string $notificationId): void
    {
        DB::transaction(fn () => $user->notifications()->findOrFail($notificationId)->delete());
    }

    public function deleteMany(User $user, array $notificationIds): int
    {
        return DB::transaction(fn (): int => $user->notifications()->whereIn('id', $notificationIds)->delete());
    }
}
