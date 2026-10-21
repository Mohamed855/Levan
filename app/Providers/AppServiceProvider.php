<?php

namespace App\Providers;

use App\Traits\HasRolesAndPermissions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::before(function (object $user, string $ability): ?bool {
            return in_array(HasRolesAndPermissions::class, class_uses_recursive($user), true)
                ? $user->hasPermissionTo($ability)
                : null;
        });
    }
}
