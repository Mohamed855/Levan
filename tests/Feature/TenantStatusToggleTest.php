<?php

declare(strict_types=1);
namespace Tests\Feature;
use App\Models\Tenant\Assignment;
use App\Models\Tenant\Property;
use App\Models\Tenant\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
final class TenantStatusToggleTest extends TestCase { use RefreshDatabase; public function test_tenant_toggles_assignment_and_property_status(): void { $user=User::factory()->create();$assignment=Assignment::query()->create(['assignment_number'=>'A-1','name'=>'إسناد','issuing_entity'=>'جهة','created_by'=>$user->id]);$property=Property::query()->create(['property_number'=>'P-1','name'=>'عقار','city'=>'الرياض','created_by'=>$user->id]);$this->actingAs($user,'tenant')->patch(route('tenant.assignments.toggle-status',$assignment))->assertRedirect();$this->assertDatabaseHas('assignments',['id'=>$assignment->id,'is_active'=>false]);$this->actingAs($user,'tenant')->patch(route('tenant.properties.toggle-status',$property))->assertRedirect();$this->assertDatabaseHas('properties',['id'=>$property->id,'is_active'=>false]);} }