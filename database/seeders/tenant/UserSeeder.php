<?php

namespace Database\Seeders\tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Tenants\UserMangment\User\User;
use App\Models\Tenants\Branch\Branch;

class UserSeeder extends Seeder
{
    /*
    |--------------------------------------------------------------------------
    | Run the database seeds.
    |--------------------------------------------------------------------------
    */
    public function run(): void
    {
        $branch = Branch::where('code', 'MAIN')->first();

        $user = User::updateOrCreate(
            ['email' => 'demo@levan.com'],
            [
            'name'                  => 'الحساب التجريبي',
            'employee_number'       => 'EMP001',
            'email_verified_at'     => now(),
            'password'              => Hash::make('123456789'),
            'phone'                 => '0123456789',
            'status'                => 'active',
            'image'                 => '',
            'branch_id'             => $branch?->id,
            'global_uid'            => (string) Str::uuid(),
            'created_at'            => now(),
            'updated_at'            => now(),
            ]
        );

        $user->syncRoles(['Admin']);
    }
}
