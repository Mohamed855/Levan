<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Assignments;

use App\Models\Tenants\Assignments\Assignment;
use Tests\TestCase;

class AssignmentFormParityTest extends TestCase
{
    public function test_assignment_shared_form_partial_exists_and_contains_four_steps(): void
    {
        $partialPath = resource_path('views/tenant/assignments/partials/_form.blade.php');
        $this->assertFileExists($partialPath);

        $content = file_get_contents($partialPath);
        $this->assertStringContainsString('assignment-basic-validation', $content);
        $this->assertStringContainsString('assignment-properties-validation', $content);
        $this->assertStringContainsString('assignment-files-validation', $content);
        $this->assertStringContainsString('assignment-review-validation', $content);
    }

    public function test_create_and_edit_views_include_shared_form(): void
    {
        $createContent = file_get_contents(resource_path('views/tenant/assignments/create.blade.php'));
        $editContent = file_get_contents(resource_path('views/tenant/assignments/edit.blade.php'));

        $this->assertStringContainsString("tenant.assignments.partials._form", $createContent);
        $this->assertStringContainsString("'is_edit' => false", $createContent);

        $this->assertStringContainsString("tenant.assignments.partials._form", $editContent);
        $this->assertStringContainsString("'is_edit' => true", $editContent);
    }

    public function test_assignment_shared_form_compiles_without_syntax_errors(): void
    {
        \Illuminate\Support\Facades\View::share('errors', new \Illuminate\Support\ViewErrorBag());

        $dataTable = app(\App\DataTables\Tenants\Assignments\AssignmentPropertySelectionDataTable::class);
        $rendered = view('tenant.assignments.partials._form', [
            'is_edit' => false,
            'assignment' => null,
            'generatedAssignmentNumber' => 'ASG-TEST-001',
            'cityOptions' => ['Riyadh' => 'الرياض'],
            'propertyImageFallback' => '/test.png',
            'dataTable' => $dataTable->html(),
        ])->render();

        $this->assertNotEmpty($rendered);
        $this->assertStringContainsString('assignment-basic-validation', $rendered);
        $this->assertStringContainsString('data-category-mapping', $rendered);
    }
}

