<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Admin\AuctionTemplate;
use App\Models\Tenant\Auction;
use App\Models\Tenant\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuctionManagementPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_the_template_listing_and_designer(): void
    {
        $this->seed(\Database\Seeders\AccessControlSeeder::class);
        $admin = AdminUser::factory()->create();
        $admin->assignRole(\App\Models\Access\Role::query()->where('guard', 'admin')->where('name', 'SuperAdmin')->firstOrFail());
        AuctionTemplate::query()->create(['name' => 'قالب تجريبي', 'type' => 'brochure', 'status' => 'draft', 'created_by' => $admin->id]);
        $this->actingAs($admin, 'admin')->get(route('admin.auction-templates.index'))->assertOk()->assertSee('قوالب المزادات');
        $this->actingAs($admin, 'admin')->get(route('admin.auction-templates.create'))->assertOk()->assertSee('مصمم القالب');
    }

    public function test_tenant_can_open_auctions_listing_and_create_form(): void
    {
        $user = User::factory()->create();
        Auction::query()->create(['assignment_id' => 1, 'name' => 'مزاد تجريبي', 'start_date' => '2026-11-01', 'end_date' => '2026-11-02', 'type' => 'electronic', 'category' => 'lands', 'city' => 'الرياض', 'created_by' => $user->id]);
        $this->actingAs($user, 'tenant')->get(route('tenant.auctions.index'))->assertOk()->assertSee('المزادات');
        $this->actingAs($user, 'tenant')->get(route('tenant.auctions.create'))->assertOk()->assertSee('بيانات المزاد');
    }
}