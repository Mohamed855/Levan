<?php

namespace Database\Seeders\admin;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /*
    |--------------------------------------------------------------------------
    | Run the database seeds.
    |--------------------------------------------------------------------------
    */
    public function run(): void
    {
        $superAdmin = AdminUser::create([
            'name'                  => 'مدير النظام',
            'email'                 => 'superadmin@gmail.com',
            'email_verified_at'     => now(),
            'password'              => Hash::make('123456789'),
            'phone'                 => '0123456789',
            'status'                => 'active',

            'created_at'            => now(),
            'updated_at'            => now(),
        ]);



        $superAdmin->assignRole('SuperAdmin');
    }
}
