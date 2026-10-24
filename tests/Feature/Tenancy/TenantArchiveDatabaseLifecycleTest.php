<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Listeners\DeleteTenantDatabaseAfterForceDelete;
use App\Models\Admin\Tenants\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Stancl\JobPipeline\JobPipeline;
use Stancl\Tenancy\Events\TenantDeleted;
use Tests\TestCase;

final class TenantArchiveDatabaseLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_archiving_a_tenant_keeps_its_database_lifecycle_out_of_the_delete_pipeline(): void
    {
        Event::fake([TenantDeleted::class]);
        $tenant = Tenant::query()->create([
            'company_name' => 'شركة أرشيف',
            'commercial_number' => '1012345678',
            'primary_contact_name' => 'مسؤول الأرشيف',
            'primary_contact_email' => 'archive@example.test',
            'primary_contact_phone' => '0500000000',
        ]);

        $tenant->delete();
        $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);

        Bus::fake();
        app(DeleteTenantDatabaseAfterForceDelete::class)->handle(new TenantDeleted($tenant));

        Bus::assertNotDispatched(JobPipeline::class);
    }
}
