<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Assignments;

use App\DataTables\Tenants\Assignments\AssignmentPropertySelectionDataTable;
use App\Enums\Tenants\Assignments\AssignmentCategory;
use App\Enums\Tenants\Properties\PropertyType;
use Tests\TestCase;

class AssignmentCategoryPropertyMatchingTest extends TestCase
{
    public function test_assignment_category_allowed_property_types_mapping(): void
    {
        // 1. Lands category allows only land types
        $landsAllowed = AssignmentCategory::LANDS->allowedPropertyTypes();
        $this->assertContains(PropertyType::RESIDENTIAL_LAND->value, $landsAllowed);
        $this->assertContains(PropertyType::COMMERCIAL_LAND->value, $landsAllowed);
        $this->assertContains(PropertyType::AGRICULTURAL_LAND->value, $landsAllowed);
        $this->assertContains(PropertyType::INDUSTRIAL_LAND->value, $landsAllowed);
        $this->assertNotContains(PropertyType::VILLA->value, $landsAllowed);
        $this->assertNotContains(PropertyType::COMMERCIAL_BUILDING->value, $landsAllowed);

        $this->assertTrue(AssignmentCategory::LANDS->allowsPropertyType(PropertyType::RESIDENTIAL_LAND));
        $this->assertFalse(AssignmentCategory::LANDS->allowsPropertyType(PropertyType::VILLA));

        // 2. Villas category allows villa and palace
        $villasAllowed = AssignmentCategory::VILLAS->allowedPropertyTypes();
        $this->assertContains(PropertyType::VILLA->value, $villasAllowed);
        $this->assertContains(PropertyType::PALACE->value, $villasAllowed);
        $this->assertNotContains(PropertyType::RESIDENTIAL_LAND->value, $villasAllowed);
        $this->assertNotContains(PropertyType::WAREHOUSE->value, $villasAllowed);

        // 3. Commercial Buildings category
        $commAllowed = AssignmentCategory::COMMERCIAL_BUILDINGS->allowedPropertyTypes();
        $this->assertContains(PropertyType::COMMERCIAL_BUILDING->value, $commAllowed);
        $this->assertContains(PropertyType::RESIDENTIAL_BUILDING->value, $commAllowed);
        $this->assertNotContains(PropertyType::RESIDENTIAL_LAND->value, $commAllowed);

        // 4. Industrial category
        $indAllowed = AssignmentCategory::INDUSTRIAL->allowedPropertyTypes();
        $this->assertContains(PropertyType::INDUSTRIAL_LAND->value, $indAllowed);
        $this->assertContains(PropertyType::WAREHOUSE->value, $indAllowed);
        $this->assertNotContains(PropertyType::VILLA->value, $indAllowed);

        // 5. Mixed Use category allows all property types
        $mixedAllowed = AssignmentCategory::MIXED_USE->allowedPropertyTypes();
        $this->assertCount(count(PropertyType::cases()), $mixedAllowed);
        $this->assertTrue(AssignmentCategory::MIXED_USE->allowsPropertyType(PropertyType::VILLA));
        $this->assertTrue(AssignmentCategory::MIXED_USE->allowsPropertyType(PropertyType::RESIDENTIAL_LAND));
    }

    public function test_datatable_supports_category_filtering(): void
    {
        $dataTable = new AssignmentPropertySelectionDataTable();
        $dataTable->forCategory(AssignmentCategory::VILLAS->value);

        $ref = new \ReflectionClass($dataTable);
        $prop = $ref->getProperty('assignmentCategory');
        $prop->setAccessible(true);

        $this->assertSame(AssignmentCategory::VILLAS->value, $prop->getValue($dataTable));
    }

    public function test_form_partial_contains_category_filter_and_mapping(): void
    {
        $partialPath = resource_path('views/tenant/assignments/partials/_form.blade.php');
        $this->assertFileExists($partialPath);

        $content = file_get_contents($partialPath);
        $this->assertStringContainsString('data-category-mapping', $content);
        $this->assertStringContainsString('assignment-category-filter', $content);
        $this->assertStringContainsString('assignment_category', $content);
    }
}
