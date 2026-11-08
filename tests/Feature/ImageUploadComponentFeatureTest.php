<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ImageUploadComponentFeatureTest extends TestCase
{
    public function test_image_upload_without_value_renders_required_file_input(): void
    {
        $rendered = Blade::render(
            '<x-form.controls.image-upload name="image" label="الصورة" :required="true" />'
        );

        $this->assertStringContainsString('required', $rendered);
        $this->assertStringContainsString('data-has-existing="false"', $rendered);
        $this->assertStringContainsString('data-was-required="true"', $rendered);
        $this->assertStringNotContainsString('data-existing-file-card', $rendered);
        $this->assertStringNotContainsString('data-existing-file-input', $rendered);
    }

    public function test_image_upload_with_existing_value_omits_native_required_and_renders_hidden_input(): void
    {
        $rendered = Blade::render(
            '<x-form.controls.image-upload name="image" label="الصورة" value="uploads/avatar.png" :required="true" />'
        );

        // Native file input must not have native required attribute
        $this->assertMatchesRegularExpression('/<input[^>]+type="file"[^>]*data-file-input[^>]*>/', $rendered);
        $this->assertDoesNotMatchRegularExpression('/<input[^>]+type="file"[^>]*\srequired[\s>]/', $rendered);

        // But must preserve required indicators for scripts and styles
        $this->assertStringContainsString('data-was-required="true"', $rendered);
        $this->assertStringContainsString('data-has-existing="true"', $rendered);

        // Must render existing file card and hidden input
        $this->assertStringContainsString('data-existing-file-card', $rendered);
        $this->assertStringContainsString('data-existing-file-input', $rendered);
        $this->assertStringContainsString('name="existing_image"', $rendered);
        $this->assertStringContainsString('value="uploads/avatar.png"', $rendered);
        $this->assertStringContainsString('data-remove-existing-file="remove_image"', $rendered);
    }

    public function test_multiple_file_upload_with_array_values_renders_multiple_hidden_inputs(): void
    {
        $rendered = Blade::render(
            '<x-form.controls.file name="attachments[]" label="المرفقات" :value="[\'doc1.pdf\', \'doc2.pdf\']" :multiple="true" />'
        );

        $this->assertStringContainsString('data-has-existing="true"', $rendered);
        $this->assertStringContainsString('name="existing_attachments[]"', $rendered);
        $this->assertStringContainsString('value="doc1.pdf"', $rendered);
        $this->assertStringContainsString('value="doc2.pdf"', $rendered);
    }
}
