<?php

declare(strict_types=1);

namespace Tests\Unit;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class SharedRequiredControlsTest extends TestCase
{
    public static function controls(): array
    {
        $cases = [];
        foreach (['input', 'select', 'textarea'] as $component) {
            foreach ($component === 'textarea' ? [false] : [false, true] as $group) {
                foreach ([true, false, 'false'] as $required) {
                    $label = $component.($group ? ' group' : '').' required='.var_export($required, true);
                    $cases[$label] = [$component, $group, $required];
                }
            }
        }

        return $cases;
    }

    #[DataProvider('controls')]
    public function test_required_reaches_the_rendered_control(string $component, bool $group, bool|string $required): void
    {
        view()->share('errors', new ViewErrorBag);
        $html = Blade::render(
            '<x-form.controls.'.$component.' name="field" label="Field" :required="$required" :input-group="$group" />',
            compact('required', 'group'),
        );
        $document = new DOMDocument;
        @$document->loadHTML('<meta charset="utf-8">'.$html);
        $control = (new DOMXPath($document))->query('//*[@name="field"]')->item(0);

        self::assertNotNull($control);
        self::assertSame($required === true, $control->hasAttribute('required'));
        self::assertSame($required === true ? 'true' : 'false', $control->getAttribute('aria-required'));
    }

    public function test_explicit_aria_required_is_rendered_once(): void
    {
        view()->share('errors', new ViewErrorBag);
        $html = Blade::render('<x-form.controls.input name="field" label="Field" required aria-required="true" />');

        self::assertSame(1, substr_count($html, 'aria-required='));
    }
}
