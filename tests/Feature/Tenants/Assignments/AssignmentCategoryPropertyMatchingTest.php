<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Assignments;

use App\DataTables\Tenants\Assignments\AssignmentPropertySelectionDataTable;
use App\Enums\Tenants\Assignments\AssignmentCategory;
use App\Enums\Tenants\Properties\PropertyType;
use App\Enums\Tenants\Properties\PropertyUsageClassification;
use App\Http\Requests\Tenants\Assignments\StoreAssignmentRequest;
use App\Http\Requests\Tenants\Assignments\UpdateAssignmentRequest;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Properties\Property;
use Tests\TestCase;

class AssignmentCategoryPropertyMatchingTest extends TestCase
{
    public function test_property_types_and_labels_match_specification(): void
    {
        $expected = [
            'apartment' => 'شقة',
            'villa' => 'فيلا',
            'palace' => 'قصر',
            'building' => 'مبنى',
            'warehouse' => 'مستودع/هنجر',
            'land' => 'أرض',
            'other' => 'أخرى',
        ];

        $actual = collect(PropertyType::cases())
            ->mapWithKeys(fn (PropertyType $t) => [$t->value => $t->label()])
            ->all();

        $this->assertSame($expected, $actual);
    }

    public function test_property_classifications_and_labels_match_specification(): void
    {
        $expected = [
            'residential' => 'سكني',
            'commercial' => 'تجاري',
            'agricultural' => 'زراعي',
            'industrial' => 'صناعي',
            'mixed_use' => 'متعدد الأستخدامات',
            'other' => 'أخرى',
        ];

        $actual = collect(PropertyUsageClassification::cases())
            ->mapWithKeys(fn (PropertyUsageClassification $c) => [$c->value => $c->label()])
            ->all();

        $this->assertSame($expected, $actual);
    }

    public function test_assignment_categories_and_labels_match_specification(): void
    {
        $expected = [
            'residential' => 'سكنية',
            'commercial' => 'تجارية',
            'agricultural' => 'زراعية',
            'industrial' => 'صناعية',
            'mixed_use' => 'متعدد الأستخدامات',
        ];

        $actual = collect(AssignmentCategory::cases())
            ->mapWithKeys(fn (AssignmentCategory $c) => [$c->value => $c->label()])
            ->all();

        $this->assertSame($expected, $actual);
    }

    public function test_assignment_category_filtering_includes_same_classification_plus_other(): void
    {
        foreach (AssignmentCategory::cases() as $category) {
            $allowed = $category->allowedUsageClassifications();

            // Must include its own classification
            $this->assertContains($category->value, $allowed);
            // Must include "other" every time
            $this->assertContains(PropertyUsageClassification::OTHER->value, $allowed);
            $this->assertCount(2, $allowed);

            // Verifies allowsUsageClassification helper
            $this->assertTrue($category->allowsUsageClassification($category->value));
            $this->assertTrue($category->allowsUsageClassification(PropertyUsageClassification::OTHER));

            // Other non-matching categories should be rejected
            foreach (AssignmentCategory::cases() as $otherCat) {
                if ($otherCat->value !== $category->value) {
                    $this->assertFalse($category->allowsUsageClassification($otherCat->value));
                }
            }
        }
    }

    public function test_datatable_supports_category_filtering(): void
    {
        $dataTable = new AssignmentPropertySelectionDataTable;
        $dataTable->forCategory(AssignmentCategory::RESIDENTIAL->value);

        $ref = new \ReflectionClass($dataTable);
        $prop = $ref->getProperty('assignmentCategory');
        $prop->setAccessible(true);

        $this->assertSame(AssignmentCategory::RESIDENTIAL->value, $prop->getValue($dataTable));
    }

    public function test_form_partial_does_not_contain_category_filter(): void
    {
        $partialPath = resource_path('views/tenant/assignments/partials/_form.blade.php');
        $this->assertFileExists($partialPath);

        $content = file_get_contents($partialPath);
        $this->assertStringNotContainsString('id="assignment-category-filter"', $content);
    }

    public function test_assignment_requests_allow_property_types_regardless_of_category(): void
    {
        $storeReq = new StoreAssignmentRequest;
        $storeRules = $storeReq->rules();

        $this->assertSame(['required', 'array', 'min:1'], $storeRules['property_ids']);

        $updateReq = new UpdateAssignmentRequest;
        $updateRules = $updateReq->rules();
        $this->assertSame(['required', 'array', 'min:1'], $updateRules['property_ids']);
    }

    public function test_datatable_query_filters_by_category_usage_classifications(): void
    {
        $dataTable = new AssignmentPropertySelectionDataTable;
        $dataTable->forCategory(AssignmentCategory::RESIDENTIAL->value);

        $query = Property::query();
        $ref = new \ReflectionClass($dataTable);
        $method = $ref->getMethod('applyCustomFilters');
        $method->setAccessible(true);
        $method->invoke($dataTable, $query);

        $sql = $query->toSql();
        $this->assertStringContainsString('usage_classification', $sql);
        $bindings = $query->getBindings();
        $this->assertContains('residential', $bindings);
        $this->assertContains('other', $bindings);
    }

    public function test_datatable_query_filters_by_multiple_categories_usage_classifications(): void
    {
        $dataTable = new AssignmentPropertySelectionDataTable;
        $dataTable->forCategory([
            AssignmentCategory::RESIDENTIAL->value,
            AssignmentCategory::COMMERCIAL->value,
        ]);

        $query = Property::query();
        $ref = new \ReflectionClass($dataTable);
        $method = $ref->getMethod('applyCustomFilters');
        $method->setAccessible(true);
        $method->invoke($dataTable, $query);

        $sql = $query->toSql();
        $this->assertStringContainsString('usage_classification', $sql);
        $bindings = $query->getBindings();
        $this->assertContains('residential', $bindings);
        $this->assertContains('commercial', $bindings);
        $this->assertContains('other', $bindings);
    }

    public function test_assignment_category_enum_helper_for_multiple_categories(): void
    {
        $allowed = AssignmentCategory::allowedUsageClassificationsFor([
            AssignmentCategory::RESIDENTIAL,
            AssignmentCategory::COMMERCIAL,
        ]);

        $this->assertContains('residential', $allowed);
        $this->assertContains('commercial', $allowed);
        $this->assertContains('other', $allowed);
        $this->assertCount(3, $allowed);
    }

    public function test_assignment_requests_require_category_to_be_array(): void
    {
        $storeReq = new StoreAssignmentRequest;
        $storeRules = $storeReq->rules();

        $this->assertSame(['required', 'array', 'min:1'], $storeRules['category']);
        $this->assertArrayHasKey('category.*', $storeRules);

        $updateReq = new UpdateAssignmentRequest;
        $updateRules = $updateReq->rules();

        $this->assertSame(['required', 'array', 'min:1'], $updateRules['category']);
        $this->assertArrayHasKey('category.*', $updateRules);
    }

    public function test_assignment_model_handles_multiple_categories_and_labels(): void
    {
        $assignment = new Assignment;
        $assignment->category = ['residential', 'commercial'];

        $this->assertSame(['residential', 'commercial'], $assignment->category);
        $this->assertSame(['سكنية', 'تجارية'], $assignment->category_labels);
        $this->assertSame('سكنية، تجارية', $assignment->category_label);

        // Test backward compatibility with legacy single string
        $legacyAssignment = new Assignment;
        $legacyAssignment->setRawAttributes(['category' => 'residential']);
        $this->assertSame(['residential'], $legacyAssignment->category);
        $this->assertSame('سكنية', $legacyAssignment->category_label);
    }

    public function test_linked_properties_next_buttons_are_disabled_when_empty_in_forms(): void
    {
        $asgContent = file_get_contents(resource_path('views/tenant/assignments/partials/_form.blade.php'));
        $this->assertStringContainsString('<x-form.wizard.next-button :disabled="!($is_edit && $assignment->properties && $assignment->properties->isNotEmpty())" />', $asgContent);

        $auctionContent = file_get_contents(resource_path('views/tenant/auctions/partials/_form.blade.php'));
        $this->assertStringContainsString('<x-form.wizard.next-button :disabled="!($is_edit && $auction->properties && $auction->properties->isNotEmpty())" />', $auctionContent);
    }
}
