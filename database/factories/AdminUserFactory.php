<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Admin\AdminUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<AdminUser> */
final class AdminUserFactory extends Factory
{
    protected $model = AdminUser::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '05'.fake()->unique()->numerify('########'),
            'status' => 'active',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ];
    }
}