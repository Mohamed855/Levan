<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Assignments;

use App\DataTables\Tenants\Assignments\AssignmentPropertySelectionDataTable;
use App\Enums\Tenants\Assignments\AssignmentCategory;
use App\Enums\Tenants\Properties\PropertyType;
use App\Enums\Tenants\Properties\PropertyUsageClassification;
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
        $dataTable = new AssignmentPropertySelectionDataTable();
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
        $storeReq = new \App\Http\Requests\Tenants\Assignments\StoreAssignmentRequest();
        $storeRules = $storeReq->rules();

        $this->assertSame(['required', 'array', 'min:1'], $storeRules['property_ids']);

        $updateReq = new \App\Http\Requests\Tenants\Assignments\UpdateAssignmentRequest();
        $updateRules = $updateReq->rules();
        $this->assertSame(['required', 'array', 'min:1'], $updateRules['property_ids']);
    }

    public function test_datatable_query_filters_by_category_usage_classifications(): void
    {
        $dataTable = new AssignmentPropertySelectionDataTable();
        $dataTable->forCategory(AssignmentCategory::RESIDENTIAL->value);

        $query = \App\Models\Tenants\Properties\Property::query();
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

    public function test_linked_properties_next_buttons_are_disabled_when_empty_in_forms(): void
    {
        $asgContent = file_get_contents(resource_path('views/tenant/assignments/partials/_form.blade.php'));
        $this->assertStringContainsString('<x-form.wizard.next-button :disabled="!($is_edit && $assignment->properties && $assignment->properties->isNotEmpty())" />', $asgContent);

        $auctionContent = file_get_contents(resource_path('views/tenant/auctions/partials/_form.blade.php'));
        $this->assertStringContainsString('<x-form.wizard.next-button :disabled="!($is_edit && $auction->properties && $auction->properties->isNotEmpty())" />', $auctionContent);
    }
}
