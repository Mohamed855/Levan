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
        $this->assertNotContains('status', $columnNames);

        $issuingEntityCol = collect($columns)->firstWhere('data', 'issuing_entity');
        $this->assertNotNull($issuingEntityCol);
        $this->assertSame('الجهة المسندة', $issuingEntityCol['title'] ?? null);
    }

    public function test_auction_shared_form_partial_exists_and_contains_seven_steps(): void
    {
        $partialPath = resource_path('views/tenant/auctions/partials/_form.blade.php');
        $this->assertFileExists($partialPath);

        $content = file_get_contents($partialPath);
        $this->assertStringContainsString('step-basic-info', $content);
        $this->assertStringContainsString('step-assignment', $content);
        $this->assertStringContainsString('step-properties', $content);
        $this->assertStringContainsString('step-reports-attachments', $content);
        $this->assertStringContainsString('step-results-boards', $content);
        $this->assertStringContainsString('step-campaigns', $content);
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

    public function test_auction_edit_does_not_load_invalid_pivot_relation(): void
    {
        $controllerContent = file_get_contents(app_path('Http/Controllers/Tenants/Auctions/AuctionsController.php'));

        $this->assertStringNotContainsString('properties.pivot', $controllerContent);
        $this->assertStringContainsString("'properties'", $controllerContent);
    }

    public function test_auction_show_renders_card_layout_and_loading_title(): void
    {
        $showContent = file_get_contents(resource_path('views/tenant/auctions/show.blade.php'));

        $this->assertStringNotContainsString('layout="list"', $showContent);
        $this->assertStringContainsString('layout="card"', $showContent);
        $this->assertStringContainsString('تحضير وتجهيز تقارير المزاد', $showContent);
        $this->assertStringContainsString('reports-progress-bar', $showContent);
    }

    public function test_auction_and_assignment_categories_are_identical(): void
    {
        $auctionCategories = collect(\App\Enums\Tenants\Auctions\AuctionCategory::cases())
            ->mapWithKeys(fn ($c) => [$c->value => $c->label()])
            ->all();

        $assignmentCategories = collect(\App\Enums\Tenants\Assignments\AssignmentCategory::cases())
            ->mapWithKeys(fn ($c) => [$c->value => $c->label()])
            ->all();

        $this->assertSame($assignmentCategories, $auctionCategories);
    }

    public function test_step1_basic_info_defaults_and_js_sync(): void
    {
        $step1Content = file_get_contents(resource_path('views/tenant/auctions/partials/steps/step1-basic-info.blade.php'));
        $this->assertStringContainsString('AuctionType::ELECTRONIC->value', $step1Content);

        $jsContent = file_get_contents(resource_path('assets/js/tenant/auctions/create.js'));
        $this->assertStringContainsString('syncSelectValue', $jsContent);
        $this->assertStringContainsString('"00:00"', $jsContent);
        $this->assertStringContainsString('"23:59"', $jsContent);
    }

    public function test_auction_review_step_contains_files_and_attachments_sections(): void
    {
        $reviewContent = file_get_contents(resource_path('views/tenant/auctions/partials/steps/step7-review.blade.php'));

        $this->assertStringContainsString('title="الملفات والمستندات"', $reviewContent);
        $this->assertStringContainsString('id="review-covers-documents"', $reviewContent);
        $this->assertStringContainsString('id="review-covers-documents-count"', $reviewContent);
        $this->assertStringContainsString('id="review-additional-attachments"', $reviewContent);
        $this->assertStringContainsString('id="review-additional-attachments-count"', $reviewContent);
        $this->assertStringContainsString('assignment-file-list', $reviewContent);

        $jsContent = file_get_contents(resource_path('assets/js/tenant/auctions/create.js'));
        $this->assertStringContainsString('renderReviewFiles', $jsContent);
        $this->assertStringContainsString('renderFileCardHtml', $jsContent);
        $this->assertStringContainsString('review-covers-documents', $jsContent);
        $this->assertStringContainsString('review-additional-attachments', $jsContent);
    }

    public function test_auction_create_view_renders_without_errors(): void
    {
        \Illuminate\Support\Facades\View::share('errors', new \Illuminate\Support\ViewErrorBag());

        tenancy()->initialize('test');
        try {
            $user = \App\Models\Tenants\UserMangment\User\User::where('email', 'demo@lot.com')->first();
            $response = $this->actingAs($user, 'tenant')->get('http://test.localhost/auctions/create');

            $response->assertOk();
            $response->assertSee('wizard-validation-form');
            $response->assertSee('step-basic-info');
            $response->assertSee('category');
        } finally {
            tenancy()->end();
        }
    }

    public function test_auction_edit_view_renders_without_errors(): void
    {
        \Illuminate\Support\Facades\View::share('errors', new \Illuminate\Support\ViewErrorBag());

        tenancy()->initialize('test');
        try {
            $user = \App\Models\Tenants\UserMangment\User\User::where('email', 'demo@lot.com')->first();
            $auction = Auction::first();
            $this->assertNotNull($auction);

            $response = $this->actingAs($user, 'tenant')->get("http://test.localhost/auctions/{$auction->id}/edit");

            $response->assertOk();
            $response->assertSee('auction-edit-form');
            $response->assertSee('step-basic-info');
            $response->assertSee('category');
        } finally {
            tenancy()->end();
        }
    }

    public function test_auctions_index_table_returns_issuing_entity(): void
    {
        tenancy()->initialize('test');
        try {
            $user = \App\Models\Tenants\UserMangment\User\User::where('email', 'demo@lot.com')->first();
            $response = $this->actingAs($user, 'tenant')->getJson('http://test.localhost/auctions', [
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

            $response->assertOk();
            $json = $response->json();
            $this->assertArrayHasKey('data', $json);
            if (! empty($json['data'])) {
                $firstRow = $json['data'][0];
                $this->assertArrayHasKey('issuing_entity', $firstRow);
            }
        } finally {
            tenancy()->end();
        }
    }
}
