<?php

declare(strict_types=1);
namespace Tests\Feature;
use App\Models\Access\Permission;
use App\Models\Access\Role;
use App\Models\Tenant\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
final class AccessControlTest extends TestCase { use RefreshDatabase; public function test_tenant_permission_is_resolved_by_laravel_gate():void { $role=Role::query()->create(['guard'=>'tenant','name'=>'auction-manager','display_name'=>'مدير المزادات']);$permission=Permission::query()->create(['guard'=>'tenant','name'=>'auctions.create','display_name'=>'إنشاء المزادات']);$role->permissions()->attach($permission);$allowed=User::factory()->create();$allowed->assignRole($role);$denied=User::factory()->create();$this->assertTrue($allowed->can('auctions.create'));$this->assertFalse($denied->can('auctions.create')); } }