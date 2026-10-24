<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Admin\AdminUser;
use App\Models\Admin\Bill;
use Illuminate\Support\Facades\Gate;
use Mockery;
use Tests\TestCase;

final class GatePolicyBoundaryTest extends TestCase
{
    public function test_module_permission_without_a_record_uses_the_permission_guard(): void
    {
        $admin = Mockery::mock(AdminUser::class)->makePartial();
        $admin->shouldReceive('hasPermissionTo')->with('bills.view')->once()->andReturn(true);

        $this->assertTrue(Gate::forUser($admin)->allows('bills.view'));
    }

    public function test_module_permission_denial_is_preserved(): void
    {
        $admin = Mockery::mock(AdminUser::class)->makePartial();
        $admin->shouldReceive('hasPermissionTo')->with('bills.view')->once()->andReturn(false);

        $this->assertFalse(Gate::forUser($admin)->allows('bills.view'));
    }

    public function test_record_ability_reaches_the_policy_and_cannot_bypass_ownership(): void
    {
        Gate::policy(Bill::class, GateTestBillPolicy::class);
        $admin = Mockery::mock(AdminUser::class)->makePartial();
        $admin->id = 1;
        $admin->shouldNotReceive('hasPermissionTo');

        $bill = new Bill(['tenant_id' => 'another-owner']);
        $this->assertFalse(Gate::forUser($admin)->allows('view', $bill));
    }

    public function test_standard_policy_ability_is_not_denied_as_a_permission_name(): void
    {
        Gate::policy(Bill::class, GateTestBillPolicy::class);
        $admin = Mockery::mock(AdminUser::class)->makePartial();
        $admin->id = 1;
        $admin->shouldNotReceive('hasPermissionTo');

        $bill = new Bill(['tenant_id' => '1']);
        $this->assertTrue(Gate::forUser($admin)->allows('view', $bill));
    }

    public function test_record_argument_prevents_a_dotted_permission_from_bypassing_a_policy(): void
    {
        $admin = Mockery::mock(AdminUser::class)->makePartial();
        $admin->shouldNotReceive('hasPermissionTo');
        Gate::define('bills.view', static fn (AdminUser $user, Bill $bill): bool => false);

        $this->assertFalse(Gate::forUser($admin)->allows('bills.view', new Bill));
    }
}

final class GateTestBillPolicy
{
    public function view(AdminUser $user, Bill $bill): bool
    {
        return (string) $user->id === (string) $bill->tenant_id;
    }
}
