<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\DataTables\Tenants\Auctions\AuctionsDataTable;
use App\Models\Tenants\Auctions\Auction;
use Tests\TestCase;

class AuctionFormAndDataTableParityTest extends TestCase
{
    public function test_auctions_data_table_has_image_and_status_toggle_columns(): void
    {
        $dataTable = new AuctionsDataTable();
        $reflection = new \ReflectionClass($dataTable);
        $method = $reflection->getMethod('getColumns');
        $method->setAccessible(true);
        $columns = $method->invoke($dataTable);
        $columnNames = collect($columns)->pluck('name')->all();

        $this->assertContains('image', $columnNames);
        $this->assertContains('is_active', $columnNames);
        $this->assertContains('status', $columnNames);
    }

    public function test_auction_shared_form_partial_exists_and_contains_five_steps(): void
    {
        $partialPath = resource_path('views/tenant/auctions/partials/_form.blade.php');
        $this->assertFileExists($partialPath);

        $content = file_get_contents($partialPath);
        $this->assertStringContainsString('step-basic-info', $content);
        $this->assertStringContainsString('step-assignment', $content);
        $this->assertStringContainsString('step-properties', $content);
        $this->assertStringContainsString('step-settings', $content);
        $this->assertStringContainsString('step-review', $content);
    }

    public function test_create_and_edit_views_include_shared_form(): void
    {
        $createContent = file_get_contents(resource_path('views/tenant/auctions/create.blade.php'));
        $editContent = file_get_contents(resource_path('views/tenant/auctions/edit.blade.php'));

        $this->assertStringContainsString("tenant.auctions.partials._form", $createContent);
        $this->assertStringContainsString("'is_edit' => false", $createContent);

        $this->assertStringContainsString("tenant.auctions.partials._form", $editContent);
        $this->assertStringContainsString("'is_edit' => true", $editContent);
    }

    public function test_edit_view_does_not_contain_under_review_crash(): void
    {
        $editContent = file_get_contents(resource_path('views/tenant/auctions/edit.blade.php'));
        $formContent = file_get_contents(resource_path('views/tenant/auctions/partials/_form.blade.php'));

        $this->assertStringNotContainsString('under_review', $editContent);
        $this->assertStringNotContainsString('status->value', $editContent);
        $this->assertStringNotContainsString('under_review', $formContent);
        $this->assertStringNotContainsString('status->value', $formContent);
    }

    public function test_auction_show_renders_card_layout_and_loading_title(): void
    {
        $showContent = file_get_contents(resource_path('views/tenant/auctions/show.blade.php'));

        $this->assertStringNotContainsString('layout="list"', $showContent);
        $this->assertStringContainsString('layout="card"', $showContent);
        $this->assertStringContainsString('برجاء الانتظار، جار تحضير تقارير المزاد', $showContent);
        $this->assertStringContainsString('reports-progress-bar', $showContent);
    }
}
