<?php

declare(strict_types=1);
namespace Tests\Feature;
use App\Actions\Tenant\Properties\SavePropertyAction;
use App\Models\Tenant\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
final class PropertyAggregateTest extends TestCase { use RefreshDatabase; public function test_property_persists_boundaries_owners_and_units_together(): void { $user=User::factory()->create(); $property=app(SavePropertyAction::class)->create($user,['property_number'=>'P-100','name'=>'نص تجريبي نص تجريبي','type'=>'residential_land','usage_classification'=>'residential','area'=>100,'short_description'=>'نص تجريبي','city'=>'نص تجريبي','district'=>'??','administrative_region'=>'نص تجريبي','plan_number'=>'12','parcel_number'=>'4','deed_number'=>'123','deed_date'=>'2026-01-01','issuing_authority'=>'نص تجريبي','ownership_type'=>'sole_ownership','boundaries'=>[['direction'=>'north','border_description'=>'نص تجريبي','length'=>10]],'owners'=>[['owner_name'=>'نص تجريبي','national_id_or_cr'=>'1000000000']],'units'=>[['unit_number'=>'U1','area'=>50]]]); $this->assertCount(1,$property->boundaries);$this->assertCount(1,$property->owners);$this->assertCount(1,$property->units); } }