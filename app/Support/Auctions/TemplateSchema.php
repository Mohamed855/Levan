<?php

declare(strict_types=1);

namespace App\Support\Auctions;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class TemplateSchema
{
    public const TYPES = ['brochure', 'billboard', 'social'];

    public const BINDINGS = [
        'auction.name', 'auction.description', 'auction.start_date', 'auction.start_time',
        'auction.end_date', 'auction.end_time', 'auction.type', 'auction.category',
        'auction.city', 'auction.expected_properties_count', 'auction.supervising_entity',
        'auction.platform_name', 'auction.platform_url', 'auction.cover_image',
    ];

    public const ELEMENT_TYPES = ['text', 'variable', 'image'];

    public static function sample(string|\App\Enums\Admin\AuctionTemplates\AuctionTemplateType $type = 'brochure'): array
    {
        $typeVal = is_object($type) ? $type->value : (string) $type;
        $width = $typeVal === 'billboard' ? 1200 : ($typeVal === 'social' ? 1080 : 794);
        $height = $typeVal === 'billboard' ? 628 : ($typeVal === 'social' ? 1080 : 1123);

        return [
            'canvas' => [
                'width' => $width,
                'height' => $height,
            ],
            'pages' => [
                [
                    'id' => 'page-1',
                    'background' => [
                        'color' => '#ffffff',
                    ],
                    'elements' => [
                        [
                            'id' => 'elem-1',
                            'type' => 'variable',
                            'x' => 50,
                            'y' => 50,
                            'width' => 400,
                            'height' => 60,
                            'binding' => 'auction.name',
                            'required' => true,
                        ],
                    ],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
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

    /** @return array<string, mixed> */
    public function validate(string|array $schema, string $templateType): array
    {
        $schema = $this->decode($schema);
        $validator = Validator::make($schema, [
            'canvas' => ['required', 'array'],
            'canvas.width' => ['required', 'integer', 'min:320', 'max:10000'],
            'canvas.height' => ['required', 'integer', 'min:320', 'max:10000'],
            'pages' => ['required', 'array', 'min:1', $templateType === 'brochure' ? 'max:50' : 'max:1'],
            'pages.*.id' => ['required', 'string', 'max:80', 'distinct'],
            'pages.*.background' => ['nullable', 'array'],
            'pages.*.background.color' => ['nullable', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'pages.*.background.image' => ['nullable', 'string', 'max:2048'],
            'pages.*.elements' => ['required', 'array', 'max:100'],
            'pages.*.elements.*.id' => ['required', 'string', 'max:80'],
            'pages.*.elements.*.type' => ['required', 'in:'.implode(',', self::ELEMENT_TYPES)],
            'pages.*.elements.*.x' => ['required', 'numeric', 'min:0'],
            'pages.*.elements.*.y' => ['required', 'numeric', 'min:0'],
            'pages.*.elements.*.width' => ['required', 'numeric', 'gt:0'],
            'pages.*.elements.*.height' => ['required', 'numeric', 'gt:0'],
            'pages.*.elements.*.content' => ['nullable', 'string', 'max:5000'],
            'pages.*.elements.*.binding' => ['nullable', 'string', 'max:120'],
            'pages.*.elements.*.required' => ['nullable', 'boolean'],
            'pages.*.elements.*.style' => ['nullable', 'array'],
            'pages.*.elements.*.style.color' => ['nullable', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'pages.*.elements.*.style.background_color' => ['nullable', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'pages.*.elements.*.style.font_size' => ['nullable', 'numeric', 'min:6', 'max:300'],
            'pages.*.elements.*.style.font_weight' => ['nullable', 'in:normal,bold,100,200,300,400,500,600,700,800,900'],
            'pages.*.elements.*.style.text_align' => ['nullable', 'in:right,center,left'],
            'pages.*.elements.*.style.border_radius' => ['nullable', 'numeric', 'min:0', 'max:500'],
        ], [], [
            'canvas.width' => 'عرض اللوحة',
            'canvas.height' => 'ارتفاع اللوحة',
            'pages' => 'صفحات القالب',
            'pages.*.elements' => 'عناصر الصفحة',
        ]);

        $validator->after(function ($validator) use ($schema): void {
            $elementIds = [];
            foreach (Arr::get($schema, 'pages', []) as $pageIndex => $page) {
                foreach (($page['elements'] ?? []) as $elementIndex => $element) {
                    $path = "pages.{$pageIndex}.elements.{$elementIndex}";
                    $id = $element['id'] ?? null;
                    if ($id && in_array($id, $elementIds, true)) {
                        $validator->errors()->add("{$path}.id", 'معرف العنصر مطلوب ويجب أن يكون فريدًا داخل الصفحة.');
                    }
                    $elementIds[] = $id;

                    $type = $element['type'] ?? null;
                    $binding = $element['binding'] ?? null;
                    if (in_array($type, ['variable', 'image'], true) && ! in_array($binding, self::BINDINGS, true)) {
                        $validator->errors()->add("{$path}.binding", 'ربط البيانات غير مدعوم لهذا العنصر.');
                    }
                    if ($type === 'text' && blank($element['content'] ?? null)) {
                        $validator->errors()->add("{$path}.content", 'محتوى النص مطلوب لعنصر النص.');
                    }
                }
            }
        });

        return $validator->validate();
    }

    /** @return list<array{binding:string,required:bool}> */
    public function slots(array $schema): array
    {
        $slots = [];
        foreach ($schema['pages'] ?? [] as $page) {
            foreach ($page['elements'] ?? [] as $element) {
                if (in_array($element['type'] ?? null, ['variable', 'image'], true) && isset($element['binding'])) {
                    $slots[] = ['binding' => $element['binding'], 'required' => (bool) ($element['required'] ?? false)];
                }
            }
        }

        return $slots;
    }
}
