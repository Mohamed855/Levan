<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Admin\AdminUser;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminBootstrapCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_bootstrap_command_refuses_a_weak_password(): void
    {
        $exitCode = Artisan::call('admin:create-user', [
            'email' => 'admin@example.test',
            '--password' => 'weak',
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertFalse(AdminUser::query()->where('email', 'admin@example.test')->exists());
    }
}
