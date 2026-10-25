<?php

declare(strict_types=1);

namespace App\Support\Auctions;

use App\Enums\Admin\AuctionTemplates\AuctionTemplateType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class TemplateSchema
{
    public const SCHEMA_VERSION = 2;

    public const TYPES = ['brochure', 'billboard', 'social'];

    public const BINDINGS = ['auction.name', 'auction.description', 'auction.start_date', 'auction.start_time', 'auction.end_date', 'auction.end_time', 'auction.type', 'auction.category', 'auction.city', 'auction.expected_properties_count', 'auction.supervising_entity', 'auction.platform_name', 'auction.platform_url', 'auction.cover_image'];

    public const ELEMENT_TYPES = ['text', 'variable', 'image'];

    public const BACKGROUND_FITS = ['cover', 'contain', 'stretch', 'center'];

    public static function sample(string|AuctionTemplateType $type = 'brochure'): array
    {
        $typeVal = is_object($type) ? $type->value : (string) $type;
        $width = $typeVal === 'billboard' ? 1200 : ($typeVal === 'social' ? 1080 : 794);
        $height = $typeVal === 'billboard' ? 628 : ($typeVal === 'social' ? 1080 : 1123);

        return ['version' => self::SCHEMA_VERSION, 'canvas' => ['width' => $width, 'height' => $height], 'pages' => [['id' => 'page-1', 'background' => ['color' => '#ffffff', 'image' => null, 'fit' => 'cover'], 'elements' => [['id' => 'elem-1', 'type' => 'variable', 'x' => 50, 'y' => 50, 'width' => 400, 'height' => 60, 'binding' => 'auction.name', 'required' => true, 'style' => self::defaultStyle()]]]]];
    }

    public static function defaultStyle(): array
    {
        return ['color' => '#173f35', 'background_color' => 'transparent', 'font_size' => 24, 'font_weight' => 400, 'font_family' => 'almarai', 'text_align' => 'right', 'line_height' => 1.4, 'border_radius' => 0];
    }

    public function decode(string|array $schema): array
    {
        if (is_array($schema)) {
            return $schema;
        }
        $decoded = json_decode($schema, true);
        if (! is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::withMessages(['schema' => 'بنية القالب غير صحيحة.']);
        }

        return $decoded;
    }

    public function normalize(string|array $schema, string $templateType = 'brochure'): array
    {
        $source = $this->decode($schema);
        $out = $source;
        $out['version'] = self::SCHEMA_VERSION;
        $out['canvas'] = array_merge(['width' => 794, 'height' => 1123], (array) ($source['canvas'] ?? []));
        if (isset($out['canvas']['background']) && ! isset($out['canvas']['background_color'])) {
            $out['canvas']['background_color'] = $out['canvas']['background'];
            unset($out['canvas']['background']);
        }
        $pages = [];
        foreach ((array) ($source['pages'] ?? []) as $index => $page) {
            $page = (array) $page;
            $page['id'] = (string) ($page['id'] ?? ('page-'.($page['page_number'] ?? $index + 1)));
            $background = array_merge(['color' => '#ffffff', 'image' => null, 'fit' => 'cover'], (array) ($page['background'] ?? []));
            if (isset($page['background_image']) && ! $background['image']) {
                $background['image'] = $page['background_image'];
            }
            $elements = [];
            foreach ((array) ($page['elements'] ?? []) as $ei => $element) {
                $element = (array) $element;
                $position = (array) ($element['position'] ?? []);
                foreach (['x', 'y', 'width', 'height'] as $key) {
                    if (! isset($element[$key]) && isset($position[$key])) {
                        $element[$key] = $position[$key];
                    }
                }
                unset($element['position']);
                $element['id'] = (string) ($element['id'] ?? "{$page['id']}-element-".($ei + 1));
                $style = (array) ($element['style'] ?? []);
                $styleMap = ['fontSize' => 'font_size', 'fontWeight' => 'font_weight', 'backgroundColor' => 'background_color', 'textAlign' => 'text_align', 'fontFamily' => 'font_family', 'lineHeight' => 'line_height', 'borderRadius' => 'border_radius'];
                foreach ($styleMap as $from => $to) {
                    if (isset($style[$from]) && ! isset($style[$to])) {
                        $style[$to] = $style[$from];
                    }
                }
                $style = array_merge(self::defaultStyle(), $style);
                if (isset($style['font_family']) && ! preg_match('/^[a-z0-9-]+$/', (string) $style['font_family'])) {
                    $style['font_family'] = 'almarai';
                }
                $type = $element['type'] ?? 'text';
                if ($type === 'text' && ! empty($element['binding'])) {
                    $type = 'variable';
                }
                $element['type'] = $type;
                if (array_key_exists('default_value', $element) && ! isset($element['content'])) {
                    $element['content'] = $element['default_value'];
                }
                $element['x'] = $element['x'] ?? 0;
                $element['y'] = $element['y'] ?? 0;
                $element['width'] = $element['width'] ?? 100;
                $element['height'] = $element['height'] ?? 40;
                $element['style'] = $style;
                $elements[] = $element;
            }
            $pages[] = ['id' => $page['id'], 'background' => $background, 'elements' => $elements] + array_diff_key($page, array_flip(['id', 'background', 'background_image', 'elements', 'page_number']));
        }
        $out['pages'] = $pages ?: self::sample($templateType)['pages'];

        return $out;
    }

    public function validate(string|array $schema, string $templateType): array
    {
        $schema = $this->normalize($schema, $templateType);
        $validator = Validator::make($schema, [
            'version' => ['required', 'integer', 'in:2'], 'canvas' => ['required', 'array'], 'canvas.width' => ['required', 'integer', 'min:320', 'max:10000'], 'canvas.height' => ['required', 'integer', 'min:320', 'max:10000'],
            'pages' => ['required', 'array', 'min:1', $templateType === 'brochure' ? 'max:50' : 'max:1'], 'pages.*.id' => ['required', 'string', 'max:80', 'distinct'], 'pages.*.background' => ['nullable', 'array'], 'pages.*.background.color' => ['nullable', 'regex:/^#[A-Fa-f0-9]{6}$/'], 'pages.*.background.image' => ['nullable', 'string', 'max:2048'], 'pages.*.background.fit' => ['nullable', 'in:'.implode(',', self::BACKGROUND_FITS)], 'pages.*.elements' => ['required', 'array', 'max:100'], 'pages.*.elements.*.id' => ['required', 'string', 'max:80'], 'pages.*.elements.*.type' => ['required', 'in:'.implode(',', self::ELEMENT_TYPES)], 'pages.*.elements.*.x' => ['required', 'numeric', 'min:0'], 'pages.*.elements.*.y' => ['required', 'numeric', 'min:0'], 'pages.*.elements.*.width' => ['required', 'numeric', 'min:20'], 'pages.*.elements.*.height' => ['required', 'numeric', 'min:20'], 'pages.*.elements.*.content' => ['nullable', 'string', 'max:5000'], 'pages.*.elements.*.binding' => ['nullable', 'string', 'max:120'], 'pages.*.elements.*.required' => ['nullable', 'boolean'], 'pages.*.elements.*.style' => ['nullable', 'array'], 'pages.*.elements.*.style.font_size' => ['nullable', 'numeric', 'min:6', 'max:300'], 'pages.*.elements.*.style.line_height' => ['nullable', 'numeric', 'min:0.8', 'max:3'], 'pages.*.elements.*.style.font_weight' => ['nullable', 'in:normal,bold,100,200,300,400,500,600,700,800,900'], 'pages.*.elements.*.style.text_align' => ['nullable', 'in:right,center,left'], 'pages.*.elements.*.style.border_radius' => ['nullable', 'numeric', 'min:0', 'max:500'],
        ]);
        $validator->after(function ($v) use ($schema): void {
            foreach ($schema['pages'] as $pi => $page) {
                $image = $page['background']['image'] ?? null;
                if ($image !== null && (preg_match('#^(https?:|file:|//|\\\\|/)#i', (string) $image) || str_contains((string) $image, '..') || ! preg_match('#^auction_templates/#', (string) $image))) {
                    $v->errors()->add("pages.$pi.background.image", 'مسار الصورة غير آمن.');
                }
                foreach ($page['elements'] as $ei => $el) {
                    $path = "pages.$pi.elements.$ei";
                    $id = $el['id'] ?? null;
                    if (in_array($el['type'] ?? null, ['variable', 'image'], true) && ! in_array($el['binding'] ?? null, self::BINDINGS, true)) {
                        $v->errors()->add("$path.binding", 'ربط البيانات غير مدعوم.');
                    }
                    if (($el['type'] ?? null) === 'text' && blank($el['content'] ?? null)) {
                        $v->errors()->add("$path.content", 'محتوى النص مطلوب.');
                    }
                    if (($el['x'] + $el['width']) > $schema['canvas']['width'] || ($el['y'] + $el['height']) > $schema['canvas']['height']) {
                        $v->errors()->add($path, 'العنصر يتجاوز حدود اللوحة.');
                    }
                    $family = $el['style']['font_family'] ?? 'almarai';
                    if (class_exists(AuctionFontRegistry::class)) {
                        $registry = app(AuctionFontRegistry::class);
                        if (! $registry->find((string) $family)) {
                            $v->errors()->add("$path.style.font_family", 'الخط غير مدعوم.');
                        }
                        $weight = (int) ($el['style']['font_weight'] ?? 400);
                        if (! $registry->supportsWeight((string) $family, $weight)) {
                            $v->errors()->add("$path.style.font_weight", 'وزن الخط غير مدعوم.');
                        }
                    }
                }
            }
        });

        return $validator->validate();
    }

    public function slots(array $schema): array
    {
        $slots = [];
        foreach (($this->normalize($schema)['pages'] ?? []) as $page) {
            foreach (($page['elements'] ?? []) as $e) {
                if (in_array($e['type'] ?? null, ['variable', 'image'], true) && isset($e['binding'])) {
                    $slots[] = ['binding' => $e['binding'], 'required' => (bool) ($e['required'] ?? false)];
                }
            }
        }

return $slots;
    }
}
