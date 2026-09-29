<?php

namespace Tests\Feature;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Tenants\Tenant;
use App\Services\Admin\SubscriptionManagement\CompanyDraftService;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CompanyDraftServiceTest extends TestCase
{

    public function test_an_admin_has_one_reusable_draft_without_tenant_provisioning(): void
    {
        $admin = AdminUser::firstOrCreate(
            ['email' => 'draft-admin@example.test'],
            [
                'name' => 'Draft Admin',
                'password' => Hash::make('password'),
                'phone' => '0500000000',
                'status' => 'active',
            ]
        );
        $service = app(CompanyDraftService::class);

        $first = $service->save($admin->id, ['company_name' => 'First company'], 1);
        $second = $service->save($admin->id, ['tax_number' => '300000000000003'], 2);

        $this->assertSame($first->id, $second->id);
        $this->assertSame('draft', $second->status);
        $this->assertSame(2, $second->draft_step);
        $this->assertNull($second->version_id);
        $this->assertSame(1, Tenant::where('status', 'draft')->where('drafted_by', $admin->id)->count());
    }


}
