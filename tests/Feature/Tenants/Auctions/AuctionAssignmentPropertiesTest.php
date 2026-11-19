<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Http\Middleware\Tenancy\CheckSubscription;
use App\Http\Middleware\Tenancy\EnsureUserHasActiveLicense;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\Support\TenantAuctionReportTestCase;

final class AuctionAssignmentPropertiesTest extends TenantAuctionReportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([CheckSubscription::class, EnsureUserHasActiveLicense::class]);
        $user = User::where('email', 'demo@lot.com')->firstOrFail();
        $user->syncRoles([]);
        $user->syncPermissions([Permission::firstOrCreate(['name' => 'assignments.view', 'guard_name' => 'tenant'])]);
        $this->actingAs($user, 'tenant');
    }

    public function test_assignment_with_multiple_cities_returns_one_city_and_its_properties_for_the_auction_form(): void
    {
        $assignment = $this->assignment(['Riyadh', 'Jeddah']);
        $property = $this->property($assignment, 'جدة');
        $this->getJson(tenant_route_name('assignments.properties', $assignment))
            ->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('assignment.city', 'الرياض')
            ->assertJsonPath('assignment.category', 'residential')
            ->assertJsonPath('properties.0.id', $property->id)
            ->assertJsonPath('properties.0.is_selectable', true);
    }

    public function test_single_city_and_legacy_city_values_remain_supported(): void
    {
        $assignment = $this->assignment(['الدمام']);
        $this->getJson(tenant_route_name('assignments.properties', $assignment))->assertOk()->assertJsonPath('assignment.city', 'الدمام');
        DB::table('assignments')->where('id', $assignment->id)->update(['city' => 'Jeddah']);
        $this->getJson(tenant_route_name('assignments.properties', $assignment))->assertOk()->assertJsonPath('assignment.city', 'جدة');
    }

    public function test_empty_assignment_cities_fall_back_to_a_property_city(): void
    {
        $assignment = $this->assignment([]);
        $this->property($assignment, 'Dammam');
        $this->getJson(tenant_route_name('assignments.properties', $assignment))->assertOk()->assertJsonPath('assignment.city', 'الدمام');
    }

    public function test_assignment_without_cities_or_properties_returns_an_empty_list(): void
    {
        $assignment = $this->assignment([]);
        $this->getJson(tenant_route_name('assignments.properties', $assignment))
            ->assertOk()->assertJsonPath('assignment.city', null)->assertJsonPath('properties', []);
    }

    public function test_assignment_properties_remain_permission_protected(): void
    {
        $assignment = $this->assignment(['الرياض']);
        auth('tenant')->user()->syncPermissions([]);
        $this->getJson(tenant_route_name('assignments.properties', $assignment))->assertForbidden();
    }

    private function assignment(array $cities): Assignment
    {
        return Assignment::create(['name' => 'إسناد اختبار المزاد', 'assignment_number' => 'AJAX-'.uniqid(), 'issuing_entity' => 'جهة اختبار', 'city' => $cities, 'category' => ['residential'], 'is_active' => true]);
    }

    private function property(Assignment $assignment, string $city): Property
    {
        return Property::create(['assignment_id' => $assignment->id, 'property_number' => 'AJAX-P-'.uniqid(), 'name' => 'عقار اختبار المزاد', 'type' => 'residential_land', 'city' => $city]);
    }
}
