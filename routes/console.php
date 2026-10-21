<?php

use App\Models\Access\Role;
use App\Models\Admin\AdminUser;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('admin:create-user {email=admin@levan.test} {--password=password} {--name=مدير المنصة}', function (string $email): void {
    $password = (string) $this->option('password');
    $name = (string) $this->option('name');

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

    $this->info("Admin user created/updated successfully: {$email} (Password: {$password})");
})->purpose('Create or update an admin user with all permissions');
