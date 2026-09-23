<?php

namespace Database\Seeders\tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Tenants\UserMangment\User\User;

class UserSeeder extends Seeder
{
    /*
    |--------------------------------------------------------------------------
    | Run the database seeds.
    |--------------------------------------------------------------------------
    */
    public function run(): void
    {
        $user = User::create([
            'name'                  => 'الحساب التجريبي',
            'employee_number'       => 'EMP001',
            'email'                 => 'demo@levan.com',
            'email_verified_at'     => now(),
            'password'              => Hash::make('123456789'),
            'phone'                 => '0123456789',
            'status'                => 'active',
            'image'                 => '',
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        User::create([
            'name'                  => 'الحساب التجريبي',
            'employee_number'       => 'EMP002',
            'email'                 => 'demo1@levan.com',
            'email_verified_at'     => now(),
            'password'              => Hash::make('123456789'),
            'phone'                 => '0123456789',
            'status'                => 'active',
            'image'                 => '',
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        $user->assignRole('Admin');
    }
}
