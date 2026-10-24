<?php

use App\Models\Access\Role;
use App\Models\Admin\AdminUser;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;
use App\Models\Admin\CouponRedemption;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('admin:create-user {email} {--password=} {--name=مدير المنصة}', function (string $email): int {
    $password = (string) ($this->option('password') ?: $this->secret('كلمة مرور المدير'));
    $name = (string) $this->option('name');

    if (mb_strlen($password) < 12) {
        $this->error('يجب أن تتكون كلمة مرور المدير من 12 حرفاً على الأقل.');

        return self::FAILURE;
    }

    $superAdminRole = Role::query()->firstOrCreate(
        ['guard' => 'admin', 'name' => 'SuperAdmin'],
        ['display_name' => 'المدير العام']
    );
    $managerRole = Role::query()->firstOrCreate(
        ['guard' => 'admin', 'name' => 'manager'],
        ['display_name' => 'مدير المنصة']
    );

    $admin = AdminUser::query()->updateOrCreate(
        ['email' => $email],
        [
            'name'              => $name,
            'status'            => 'active',
            'password'          => Hash::make($password),
            'email_verified_at' => now(),
        ]
    );

    $admin->assignRole($superAdminRole);
    $admin->assignRole($managerRole);

    $this->info("Admin user created or updated successfully: {$email}");

    return self::SUCCESS;
})->purpose('Create or update an admin user with an explicit strong password');

Artisan::command('coupons:release-expired-reservations', function (): int {
    $released = CouponRedemption::query()
        ->where('status', 'reserved')
        ->where('expires_at', '<=', now())
        ->update(['status' => 'released', 'released_at' => now()]);

    $this->info("Released {$released} expired coupon reservations.");

    return self::SUCCESS;
})->purpose('Release expired checkout coupon reservations that were never paid');

Schedule::command('coupons:release-expired-reservations')->everyFiveMinutes()->withoutOverlapping();
