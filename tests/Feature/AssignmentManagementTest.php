<?php

declare(strict_types=1);
namespace Tests\Feature;
use App\Models\Tenant\Property;
use App\Models\Tenant\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
final class AssignmentManagementTest extends TestCase { use RefreshDatabase; public function test_tenant_creates_assignment_and_links_available_property(): void { $user=User::factory()->create(); $property=Property::query()->create(['property_number'=>'P-1','name'=>'نص تجريبي','city'=>'نص تجريبي','created_by'=>$user->id]); $response=$this->actingAs($user,'tenant')->post(route('tenant.assignments.store'),['assignment_number'=>'ASG-1','name'=>'نص تجريبي نص تجريبي','issuing_entity'=>'نص تجريبي','city'=>'نص تجريبي','category'=>'lands','start_date'=>'2026-11-01','end_date'=>'2026-12-01','notes'=>'نص تجريبي','property_ids'=>[$property->id]]); $response->assertRedirect(); $this->assertDatabaseHas('assignments',['assignment_number'=>'ASG-1']); $this->assertDatabaseHas('properties',['id'=>$property->id,'assignment_id'=>1]); $this->actingAs($user,'tenant')->get(route('tenant.assignments.index'))->assertOk()->assertSee('نص تجريبي نص تجريبي'); } }