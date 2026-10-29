<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Http\Controllers\Tenants\Assignments\AssignmentsController;
use App\Http\Middleware\Tenancy\InitializeTenancyBySession;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;
use Tests\TestCase;

final class TenantBindingOrderTest extends TestCase
{
    public function test_all_tenant_entry_modes_initialize_before_model_binding(): void
    {
        foreach ([
            'tenant.assignments.properties' => InitializeTenancyBySession::class,
            'path.assignments.properties' => InitializeTenancyByPath::class,
            'domain.assignments.properties' => InitializeTenancyByDomain::class,
        ] as $name => $initializer) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $middleware = app('router')->gatherRouteMiddleware($route);
            $bindingPosition = array_search(SubstituteBindings::class, $middleware, true);
            $initializerPosition = array_search($initializer, $middleware, true);
            $this->assertNotFalse($bindingPosition);
            $this->assertNotFalse($initializerPosition);
            $this->assertLessThan($bindingPosition, $initializerPosition, $name);
        }
    }

    public function test_assignment_lookup_returns_its_tenant_properties_after_initialization(): void
    {
        // Both databases are disposable in-memory connections; no configured tenant is provisioned.
        foreach (['binding_central', 'binding_tenant'] as $name) {
            config(["database.connections.$name" => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
            DB::purge($name);
            Schema::connection($name)->create('assignments', function (Blueprint $table): void {
                $table->id();
                foreach (['name', 'assignment_number', 'issuing_entity', 'city', 'category'] as $column) $table->string($column)->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->boolean('is_active')->default(true);
                $table->softDeletes();
            });
            DB::connection($name)->table('assignments')->insert(['id' => 5, 'name' => $name, 'issuing_entity' => 'Tenant authority', 'category' => 'lands']);
        }
        Schema::connection('binding_tenant')->create('properties', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            foreach (['property_number', 'name', 'type', 'city', 'district', 'deed_number', 'data_status', 'status', 'main_image'] as $column) $table->string($column)->nullable();
            $table->decimal('area')->nullable();
            $table->decimal('estimated_value')->nullable();
            $table->softDeletes();
        });
        Schema::connection('binding_tenant')->create('auction_properties', function (Blueprint $table): void {
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('auction_id');
            $table->string('result_status')->nullable();
        });
        Schema::connection('binding_tenant')->create('auctions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('status');
            $table->softDeletes();
        });
        DB::connection('binding_tenant')->table('properties')->insert(['id' => 12, 'assignment_id' => 5, 'name' => 'Tenant property', 'type' => 'residential_land', 'status' => 'available']);
        DB::setDefaultConnection('binding_central');
        $this->app->instance(InitializeTenancyBySession::class, new class {
            public function handle(Request $request, \Closure $next): mixed
            {
                DB::setDefaultConnection('binding_tenant');
                return $next($request);
            }
        });
        $request = Request::create('http://localhost/admin/assignments/5/properties');
        $route = Route::getRoutes()->getByName('tenant.assignments.properties');
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        $middleware = array_values(array_filter(app('router')->gatherRouteMiddleware($route), fn ($class) => in_array($class, [InitializeTenancyBySession::class, SubstituteBindings::class], true)));
        $response = app(Pipeline::class)->send($request)->through($middleware)->then(
            fn () => app(AssignmentsController::class)->getProperties($route->parameter('assignment')),
        );
        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertSame('binding_tenant', $data['assignment']['name']);
        $this->assertSame(12, $data['properties'][0]['id']);
        $this->assertSame('Tenant property', $data['properties'][0]['name']);
    }
}
