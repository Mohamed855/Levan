<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<User> */
final class TenantUserFactory extends Factory
{
    protected $model = User::class;

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