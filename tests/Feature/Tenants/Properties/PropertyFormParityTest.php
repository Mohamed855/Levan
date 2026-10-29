<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Properties;

use App\Actions\Tenants\Properties\UpdatePropertyAction;
use App\Models\Tenants\Properties\Property;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyFormParityTest extends TestCase
{
    public function test_property_shared_form_partial_exists_and_contains_five_steps(): void
    {
        $partialPath = resource_path('views/tenant/properties/partials/_form.blade.php');
        $this->assertFileExists($partialPath);

        $content = file_get_contents($partialPath);
        $this->assertStringContainsString('property-basic-validation', $content);
        $this->assertStringContainsString('property-location-validation', $content);
        $this->assertStringContainsString('property-ownership-validation', $content);
        $this->assertStringContainsString('property-files-validation', $content);
        $this->assertStringContainsString('property-review-validation', $content);
    }

    public function test_create_and_edit_views_include_shared_form(): void
    {
        $createContent = file_get_contents(resource_path('views/tenant/properties/create.blade.php'));
        $editContent = file_get_contents(resource_path('views/tenant/properties/edit.blade.php'));

        $this->assertStringContainsString("tenant.properties.partials._form", $createContent);
        $this->assertStringContainsString("'is_edit' => false", $createContent);

        $this->assertStringContainsString("tenant.properties.partials._form", $editContent);
        $this->assertStringContainsString("'is_edit' => true", $editContent);
    }
}

