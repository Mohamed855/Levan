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

    public const BACKGROUND_FITS = ['cover'];

    public static function sample(string|AuctionTemplateType $type = 'brochure'): array
    {
        $typeVal = is_object($type) ? $type->value : (string) $type;
        $width = $typeVal === 'billboard' ? 1200 : ($typeVal === 'social' ? 1080 : 794);
        $height = $typeVal === 'billboard' ? 628 : ($typeVal === 'social' ? 1080 : 1123);

        return ['version' => self::SCHEMA_VERSION, 'canvas' => ['width' => $width, 'height' => $height], 'pages' => [['id' => 'page-1', 'background' => ['color' => '#ffffff', 'image' => null, 'fit' => 'cover', 'position_x' => 50, 'position_y' => 50], 'elements' => [['id' => 'elem-1', 'type' => 'variable', 'x' => 50, 'y' => 50, 'width' => 400, 'height' => 60, 'binding' => 'auction.name', 'required' => true, 'style' => self::defaultStyle()]]]]];
    }

    public static function defaultStyle(): array
    {
        return ['color' => '#173f35', 'background_color' => 'transparent', 'font_size' => 24, 'font_weight' => 400, 'font_family' => 'almarai', 'text_align' => 'right', 'line_height' => 1.4, 'border_radius' => 0];
    }

    /**
     * Pre-designed template presets / samples for brochure, billboard, and social.
     *
     * @return array<string, array{id: string, type: string, title: string, description: string, badge: string, schema: array<string, mixed>}>
     */
    public static function samplePresets(?string $type = null): array
    {
        $presets = [
            'brochure-official-multipage' => [
                'id' => 'brochure-official-multipage',
                'type' => 'brochure',
                'title' => 'بروشور تسويقي رسمي شامل',
                'description' => 'كتيب تسويقي من صفحتين يشمل غلافاً رئيسياً عريضاً وجدولاً زمنياً وتفاصيل للمزايدة والشروط.',
                'badge' => 'صفحتان • A4 رأسي',
                'schema' => [
                    'version' => self::SCHEMA_VERSION,
                    'canvas' => ['width' => 794, 'height' => 1123],
                    'pages' => [
                        [
                            'id' => 'page-1',
                            'background' => ['color' => '#ffffff', 'image' => null, 'fit' => 'cover', 'position_x' => 50, 'position_y' => 50],
                            'elements' => [
                                ['id' => 'b1-tag', 'type' => 'text', 'content' => 'المملكة العربية السعودية • إعلان مزاد عقاري', 'x' => 50, 'y' => 40, 'width' => 694, 'height' => 35, 'style' => ['font_size' => 16, 'font_weight' => '700', 'font_family' => 'cairo', 'color' => '#17604f', 'text_align' => 'center']],
                                ['id' => 'b1-name', 'type' => 'variable', 'binding' => 'auction.name', 'x' => 50, 'y' => 85, 'width' => 694, 'height' => 80, 'style' => ['font_size' => 32, 'font_weight' => '700', 'font_family' => 'cairo', 'color' => '#123e33', 'text_align' => 'center']],
                                ['id' => 'b1-cover', 'type' => 'image', 'binding' => 'auction.cover_image', 'x' => 50, 'y' => 180, 'width' => 694, 'height' => 430, 'style' => ['border_radius' => 8]],
                                ['id' => 'b1-desc', 'type' => 'variable', 'binding' => 'auction.description', 'x' => 50, 'y' => 630, 'width' => 694, 'height' => 110, 'style' => ['font_size' => 18, 'color' => '#4a5568', 'text_align' => 'right', 'font_family' => 'almarai']],
                                ['id' => 'b1-city', 'type' => 'variable', 'binding' => 'auction.city', 'x' => 50, 'y' => 760, 'width' => 335, 'height' => 60, 'style' => ['font_size' => 20, 'font_weight' => '700', 'color' => '#17604f', 'background_color' => '#eef6f3', 'border_radius' => 6, 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'b1-count', 'type' => 'variable', 'binding' => 'auction.expected_properties_count', 'x' => 409, 'y' => 760, 'width' => 335, 'height' => 60, 'style' => ['font_size' => 20, 'font_weight' => '700', 'color' => '#17604f', 'background_color' => '#eef6f3', 'border_radius' => 6, 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'b1-start-date', 'type' => 'variable', 'binding' => 'auction.start_date', 'x' => 50, 'y' => 840, 'width' => 335, 'height' => 65, 'style' => ['font_size' => 20, 'font_weight' => '700', 'color' => '#ffffff', 'background_color' => '#17604f', 'border_radius' => 6, 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'b1-start-time', 'type' => 'variable', 'binding' => 'auction.start_time', 'x' => 409, 'y' => 840, 'width' => 335, 'height' => 65, 'style' => ['font_size' => 20, 'font_weight' => '700', 'color' => '#ffffff', 'background_color' => '#17604f', 'border_radius' => 6, 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'b1-supervisor', 'type' => 'variable', 'binding' => 'auction.supervising_entity', 'x' => 50, 'y' => 925, 'width' => 694, 'height' => 50, 'style' => ['font_size' => 18, 'font_weight' => '700', 'color' => '#17604f', 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'b1-platform', 'type' => 'variable', 'binding' => 'auction.platform_name', 'x' => 50, 'y' => 990, 'width' => 694, 'height' => 45, 'style' => ['font_size' => 16, 'color' => '#718096', 'text_align' => 'center', 'font_family' => 'almarai']],
                            ],
                        ],
                        [
                            'id' => 'page-2',
                            'background' => ['color' => '#ffffff', 'image' => null, 'fit' => 'cover', 'position_x' => 50, 'position_y' => 50],
                            'elements' => [
                                ['id' => 'b2-title', 'type' => 'text', 'content' => 'تفاصيل المزايدة الإلكترونية والجدول الزمني', 'x' => 50, 'y' => 60, 'width' => 694, 'height' => 55, 'style' => ['font_size' => 26, 'font_weight' => '700', 'color' => '#123e33', 'text_align' => 'right', 'font_family' => 'cairo']],
                                ['id' => 'b2-category', 'type' => 'variable', 'binding' => 'auction.category', 'x' => 50, 'y' => 135, 'width' => 335, 'height' => 55, 'style' => ['font_size' => 18, 'color' => '#17604f', 'background_color' => '#f4f8f6', 'border_radius' => 6, 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'b2-type', 'type' => 'variable', 'binding' => 'auction.type', 'x' => 409, 'y' => 135, 'width' => 335, 'height' => 55, 'style' => ['font_size' => 18, 'color' => '#17604f', 'background_color' => '#f4f8f6', 'border_radius' => 6, 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'b2-end-date', 'type' => 'variable', 'binding' => 'auction.end_date', 'x' => 50, 'y' => 210, 'width' => 335, 'height' => 60, 'style' => ['font_size' => 18, 'font_weight' => '700', 'color' => '#123e33', 'background_color' => '#e2efe9', 'border_radius' => 6, 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'b2-end-time', 'type' => 'variable', 'binding' => 'auction.end_time', 'x' => 409, 'y' => 210, 'width' => 335, 'height' => 60, 'style' => ['font_size' => 18, 'font_weight' => '700', 'color' => '#123e33', 'background_color' => '#e2efe9', 'border_radius' => 6, 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'b2-url', 'type' => 'variable', 'binding' => 'auction.platform_url', 'x' => 50, 'y' => 290, 'width' => 694, 'height' => 50, 'style' => ['font_size' => 16, 'color' => '#17604f', 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'b2-notes', 'type' => 'text', 'content' => 'تخضع كافة المزايدات للأنظمة واللوائح المعتمدة. يشترط التسجيل المسبق وسداد الضمان البنكي للدخول في المزايدة.', 'x' => 50, 'y' => 360, 'width' => 694, 'height' => 160, 'style' => ['font_size' => 18, 'color' => '#4a5568', 'text_align' => 'right', 'background_color' => '#f8faf9', 'border_radius' => 8, 'font_family' => 'almarai']],
                            ],
                        ],
                    ],
                ],
            ],
            'brochure-executive-summary' => [
                'id' => 'brochure-executive-summary',
                'type' => 'brochure',
                'title' => 'بروشور تنفيذي ملخص',
                'description' => 'صفحة واحدة أنيقة تضم صورة الغلاف الرئيسية مع ملخص بطاقات لمواعيد وموقع وبيانات المزاد.',
                'badge' => 'صفحة واحدة • A4 رأسي',
                'schema' => [
                    'version' => self::SCHEMA_VERSION,
                    'canvas' => ['width' => 794, 'height' => 1123],
                    'pages' => [
                        [
                            'id' => 'page-1',
                            'background' => ['color' => '#ffffff', 'image' => null, 'fit' => 'cover', 'position_x' => 50, 'position_y' => 50],
                            'elements' => [
                                ['id' => 'be-tag', 'type' => 'text', 'content' => 'فرصة استثمارية عقارية كبرى', 'x' => 50, 'y' => 45, 'width' => 694, 'height' => 35, 'style' => ['font_size' => 16, 'font_weight' => '700', 'font_family' => 'cairo', 'color' => '#c59b27', 'text_align' => 'center']],
                                ['id' => 'be-name', 'type' => 'variable', 'binding' => 'auction.name', 'x' => 50, 'y' => 90, 'width' => 694, 'height' => 75, 'style' => ['font_size' => 30, 'font_weight' => '700', 'font_family' => 'cairo', 'color' => '#123e33', 'text_align' => 'center']],
                                ['id' => 'be-cover', 'type' => 'image', 'binding' => 'auction.cover_image', 'x' => 50, 'y' => 180, 'width' => 694, 'height' => 460, 'style' => ['border_radius' => 10]],
                                ['id' => 'be-city', 'type' => 'variable', 'binding' => 'auction.city', 'x' => 50, 'y' => 660, 'width' => 335, 'height' => 60, 'style' => ['font_size' => 20, 'font_weight' => '700', 'color' => '#17604f', 'background_color' => '#f2f8f5', 'border_radius' => 6, 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'be-count', 'type' => 'variable', 'binding' => 'auction.expected_properties_count', 'x' => 409, 'y' => 660, 'width' => 335, 'height' => 60, 'style' => ['font_size' => 20, 'font_weight' => '700', 'color' => '#17604f', 'background_color' => '#f2f8f5', 'border_radius' => 6, 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'be-start', 'type' => 'variable', 'binding' => 'auction.start_date', 'x' => 50, 'y' => 740, 'width' => 694, 'height' => 65, 'style' => ['font_size' => 22, 'font_weight' => '700', 'color' => '#ffffff', 'background_color' => '#123e33', 'border_radius' => 6, 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'be-platform', 'type' => 'variable', 'binding' => 'auction.platform_name', 'x' => 50, 'y' => 825, 'width' => 335, 'height' => 55, 'style' => ['font_size' => 18, 'color' => '#17604f', 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'be-supervisor', 'type' => 'variable', 'binding' => 'auction.supervising_entity', 'x' => 409, 'y' => 825, 'width' => 335, 'height' => 55, 'style' => ['font_size' => 18, 'color' => '#17604f', 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'be-url', 'type' => 'variable', 'binding' => 'auction.platform_url', 'x' => 50, 'y' => 900, 'width' => 694, 'height' => 45, 'style' => ['font_size' => 16, 'color' => '#718096', 'text_align' => 'center', 'font_family' => 'almarai']],
                            ],
                        ],
                    ],
                ],
            ],
            'billboard-split-showcase' => [
                'id' => 'billboard-split-showcase',
                'type' => 'billboard',
                'title' => 'لوحة إعلانية عريضة منقسمة',
                'description' => 'تصميم عرضي فخم 16:9 بصورة كبيرة على الجانب وعناوين ومعلومات المزاد على الجانب الآخر.',
                'badge' => 'صفحة واحدة • 16:9 عريض',
                'schema' => [
                    'version' => self::SCHEMA_VERSION,
                    'canvas' => ['width' => 1200, 'height' => 628],
                    'pages' => [
                        [
                            'id' => 'page-1',
                            'background' => ['color' => '#ffffff', 'image' => null, 'fit' => 'cover', 'position_x' => 50, 'position_y' => 50],
                            'elements' => [
                                ['id' => 'bb-cover', 'type' => 'image', 'binding' => 'auction.cover_image', 'x' => 580, 'y' => 40, 'width' => 580, 'height' => 548, 'style' => ['border_radius' => 12]],
                                ['id' => 'bb-city', 'type' => 'variable', 'binding' => 'auction.city', 'x' => 40, 'y' => 50, 'width' => 220, 'height' => 45, 'style' => ['font_size' => 20, 'font_weight' => '700', 'color' => '#17604f', 'background_color' => '#eef6f3', 'border_radius' => 6, 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'bb-name', 'type' => 'variable', 'binding' => 'auction.name', 'x' => 40, 'y' => 110, 'width' => 510, 'height' => 110, 'style' => ['font_size' => 40, 'font_weight' => '700', 'font_family' => 'cairo', 'color' => '#123e33', 'text_align' => 'right']],
                                ['id' => 'bb-start-date', 'type' => 'variable', 'binding' => 'auction.start_date', 'x' => 40, 'y' => 240, 'width' => 510, 'height' => 60, 'style' => ['font_size' => 24, 'font_weight' => '700', 'color' => '#ffffff', 'background_color' => '#17604f', 'border_radius' => 6, 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'bb-start-time', 'type' => 'variable', 'binding' => 'auction.start_time', 'x' => 40, 'y' => 315, 'width' => 240, 'height' => 50, 'style' => ['font_size' => 20, 'color' => '#17604f', 'background_color' => '#f4f8f6', 'border_radius' => 6, 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'bb-count', 'type' => 'variable', 'binding' => 'auction.expected_properties_count', 'x' => 300, 'y' => 315, 'width' => 250, 'height' => 50, 'style' => ['font_size' => 20, 'color' => '#17604f', 'background_color' => '#f4f8f6', 'border_radius' => 6, 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'bb-platform', 'type' => 'variable', 'binding' => 'auction.platform_name', 'x' => 40, 'y' => 380, 'width' => 510, 'height' => 55, 'style' => ['font_size' => 22, 'font_weight' => '700', 'color' => '#123e33', 'text_align' => 'right', 'font_family' => 'almarai']],
                                ['id' => 'bb-url', 'type' => 'variable', 'binding' => 'auction.platform_url', 'x' => 40, 'y' => 445, 'width' => 510, 'height' => 45, 'style' => ['font_size' => 18, 'color' => '#718096', 'text_align' => 'right', 'font_family' => 'almarai']],
                                ['id' => 'bb-supervisor', 'type' => 'variable', 'binding' => 'auction.supervising_entity', 'x' => 40, 'y' => 505, 'width' => 510, 'height' => 50, 'style' => ['font_size' => 18, 'color' => '#17604f', 'text_align' => 'right', 'font_family' => 'almarai']],
                            ],
                        ],
                    ],
                ],
            ],
            'billboard-impact-centered' => [
                'id' => 'billboard-impact-centered',
                'type' => 'billboard',
                'title' => 'لوحة ترويجية مركزية مميزة',
                'description' => 'عنوان مركزي ضخم وصورة أفقية بارزة مع شريط معلومات سفلي عريض للشاشات الخارجية.',
                'badge' => 'صفحة واحدة • 16:9 عريض',
                'schema' => [
                    'version' => self::SCHEMA_VERSION,
                    'canvas' => ['width' => 1200, 'height' => 628],
                    'pages' => [
                        [
                            'id' => 'page-1',
                            'background' => ['color' => '#ffffff', 'image' => null, 'fit' => 'cover', 'position_x' => 50, 'position_y' => 50],
                            'elements' => [
                                ['id' => 'bc-name', 'type' => 'variable', 'binding' => 'auction.name', 'x' => 60, 'y' => 40, 'width' => 1080, 'height' => 85, 'style' => ['font_size' => 44, 'font_weight' => '700', 'font_family' => 'cairo', 'color' => '#123e33', 'text_align' => 'center']],
                                ['id' => 'bc-cover', 'type' => 'image', 'binding' => 'auction.cover_image', 'x' => 150, 'y' => 140, 'width' => 900, 'height' => 330, 'style' => ['border_radius' => 12]],
                                ['id' => 'bc-city', 'type' => 'variable', 'binding' => 'auction.city', 'x' => 60, 'y' => 495, 'width' => 240, 'height' => 60, 'style' => ['font_size' => 22, 'font_weight' => '700', 'color' => '#17604f', 'background_color' => '#eef6f3', 'border_radius' => 6, 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'bc-start', 'type' => 'variable', 'binding' => 'auction.start_date', 'x' => 330, 'y' => 495, 'width' => 320, 'height' => 60, 'style' => ['font_size' => 22, 'font_weight' => '700', 'color' => '#ffffff', 'background_color' => '#17604f', 'border_radius' => 6, 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'bc-platform', 'type' => 'variable', 'binding' => 'auction.platform_name', 'x' => 680, 'y' => 495, 'width' => 230, 'height' => 60, 'style' => ['font_size' => 20, 'color' => '#123e33', 'background_color' => '#f4f8f6', 'border_radius' => 6, 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'bc-supervisor', 'type' => 'variable', 'binding' => 'auction.supervising_entity', 'x' => 940, 'y' => 495, 'width' => 200, 'height' => 60, 'style' => ['font_size' => 18, 'color' => '#17604f', 'background_color' => '#f4f8f6', 'border_radius' => 6, 'text_align' => 'center', 'font_family' => 'almarai']],
                            ],
                        ],
                    ],
                ],
            ],
            'social-promo-post' => [
                'id' => 'social-promo-post',
                'type' => 'social',
                'title' => 'إعلان انستقرام وبوستات رقمية',
                'description' => 'منشور مربع 1:1 بصورة علوية مميزة، عنوان عريض، وشارات لموعد المزاد وعدد العقارات.',
                'badge' => 'صفحة واحدة • 1:1 مربع',
                'schema' => [
                    'version' => self::SCHEMA_VERSION,
                    'canvas' => ['width' => 1080, 'height' => 1080],
                    'pages' => [
                        [
                            'id' => 'page-1',
                            'background' => ['color' => '#ffffff', 'image' => null, 'fit' => 'cover', 'position_x' => 50, 'position_y' => 50],
                            'elements' => [
                                ['id' => 'sp-cover', 'type' => 'image', 'binding' => 'auction.cover_image', 'x' => 50, 'y' => 50, 'width' => 980, 'height' => 500, 'style' => ['border_radius' => 14]],
                                ['id' => 'sp-city', 'type' => 'variable', 'binding' => 'auction.city', 'x' => 50, 'y' => 575, 'width' => 280, 'height' => 55, 'style' => ['font_size' => 22, 'font_weight' => '700', 'color' => '#17604f', 'background_color' => '#eef6f3', 'border_radius' => 6, 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'sp-name', 'type' => 'variable', 'binding' => 'auction.name', 'x' => 50, 'y' => 645, 'width' => 980, 'height' => 110, 'style' => ['font_size' => 40, 'font_weight' => '700', 'font_family' => 'cairo', 'color' => '#123e33', 'text_align' => 'right']],
                                ['id' => 'sp-start', 'type' => 'variable', 'binding' => 'auction.start_date', 'x' => 50, 'y' => 775, 'width' => 470, 'height' => 75, 'style' => ['font_size' => 26, 'font_weight' => '700', 'color' => '#ffffff', 'background_color' => '#17604f', 'border_radius' => 8, 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'sp-count', 'type' => 'variable', 'binding' => 'auction.expected_properties_count', 'x' => 560, 'y' => 775, 'width' => 470, 'height' => 75, 'style' => ['font_size' => 24, 'font_weight' => '700', 'color' => '#123e33', 'background_color' => '#eef6f3', 'border_radius' => 8, 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'sp-platform', 'type' => 'variable', 'binding' => 'auction.platform_name', 'x' => 50, 'y' => 875, 'width' => 980, 'height' => 60, 'style' => ['font_size' => 22, 'font_weight' => '700', 'color' => '#17604f', 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'sp-footer', 'type' => 'text', 'content' => 'للمزيد من التفاصيل والمزايدة، تفضل بزيارة رابط المزاد الرسمي', 'x' => 50, 'y' => 955, 'width' => 980, 'height' => 45, 'style' => ['font_size' => 16, 'color' => '#718096', 'text_align' => 'center', 'font_family' => 'almarai']],
                            ],
                        ],
                    ],
                ],
            ],
            'social-auction-card' => [
                'id' => 'social-auction-card',
                'type' => 'social',
                'title' => 'بطاقة إشعار مزاد مدمجة',
                'description' => 'بطاقة مركزية أنيقة مع ترويسة ذهبية، صورة في المنتصف، وشبكة مواعيد وتفاصيل.',
                'badge' => 'صفحة واحدة • 1:1 مربع',
                'schema' => [
                    'version' => self::SCHEMA_VERSION,
                    'canvas' => ['width' => 1080, 'height' => 1080],
                    'pages' => [
                        [
                            'id' => 'page-1',
                            'background' => ['color' => '#ffffff', 'image' => null, 'fit' => 'cover', 'position_x' => 50, 'position_y' => 50],
                            'elements' => [
                                ['id' => 'sc-header', 'type' => 'text', 'content' => 'إعلان مزاد عقاري رسمي', 'x' => 60, 'y' => 60, 'width' => 960, 'height' => 45, 'style' => ['font_size' => 20, 'font_weight' => '700', 'font_family' => 'cairo', 'color' => '#c59b27', 'text_align' => 'center']],
                                ['id' => 'sc-name', 'type' => 'variable', 'binding' => 'auction.name', 'x' => 60, 'y' => 120, 'width' => 960, 'height' => 100, 'style' => ['font_size' => 38, 'font_weight' => '700', 'font_family' => 'cairo', 'color' => '#123e33', 'text_align' => 'center']],
                                ['id' => 'sc-cover', 'type' => 'image', 'binding' => 'auction.cover_image', 'x' => 100, 'y' => 240, 'width' => 880, 'height' => 440, 'style' => ['border_radius' => 12]],
                                ['id' => 'sc-city', 'type' => 'variable', 'binding' => 'auction.city', 'x' => 100, 'y' => 705, 'width' => 420, 'height' => 65, 'style' => ['font_size' => 24, 'color' => '#17604f', 'background_color' => '#f4f8f6', 'border_radius' => 6, 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'sc-count', 'type' => 'variable', 'binding' => 'auction.expected_properties_count', 'x' => 560, 'y' => 705, 'width' => 420, 'height' => 65, 'style' => ['font_size' => 24, 'color' => '#17604f', 'background_color' => '#f4f8f6', 'border_radius' => 6, 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'sc-start', 'type' => 'variable', 'binding' => 'auction.start_date', 'x' => 100, 'y' => 790, 'width' => 880, 'height' => 75, 'style' => ['font_size' => 26, 'font_weight' => '700', 'color' => '#ffffff', 'background_color' => '#17604f', 'border_radius' => 8, 'text_align' => 'center', 'font_family' => 'almarai']],
                                ['id' => 'sc-platform', 'type' => 'variable', 'binding' => 'auction.platform_name', 'x' => 100, 'y' => 885, 'width' => 880, 'height' => 55, 'style' => ['font_size' => 20, 'color' => '#718096', 'text_align' => 'center', 'font_family' => 'almarai']],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        if ($type !== null) {
            $typeVal = is_object($type) ? $type->value : (string) $type;
            return array_filter($presets, fn ($p) => $p['type'] === $typeVal);
        }

        return $presets;
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
            $background = array_merge(['color' => '#ffffff', 'image' => null, 'fit' => 'cover', 'position_x' => 50, 'position_y' => 50], (array) ($page['background'] ?? []));
            $background['fit'] = 'cover';
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
            'pages' => ['required', 'array', 'min:1', $templateType === 'brochure' ? 'max:50' : 'max:1'], 'pages.*.id' => ['required', 'string', 'max:80', 'distinct'], 'pages.*.background' => ['nullable', 'array'], 'pages.*.background.color' => ['nullable', 'regex:/^#[A-Fa-f0-9]{6}$/'], 'pages.*.background.image' => ['nullable', 'string', 'max:2048'], 'pages.*.background.fit' => ['required', 'in:'.implode(',', self::BACKGROUND_FITS)], 'pages.*.background.position_x' => ['required', 'numeric', 'min:0', 'max:100'], 'pages.*.background.position_y' => ['required', 'numeric', 'min:0', 'max:100'], 'pages.*.elements' => ['required', 'array', 'max:100'], 'pages.*.elements.*.id' => ['required', 'string', 'max:80'], 'pages.*.elements.*.type' => ['required', 'in:'.implode(',', self::ELEMENT_TYPES)], 'pages.*.elements.*.x' => ['required', 'numeric', 'min:0'], 'pages.*.elements.*.y' => ['required', 'numeric', 'min:0'], 'pages.*.elements.*.width' => ['required', 'numeric', 'min:20'], 'pages.*.elements.*.height' => ['required', 'numeric', 'min:20'], 'pages.*.elements.*.content' => ['nullable', 'string', 'max:5000'], 'pages.*.elements.*.binding' => ['nullable', 'string', 'max:120'], 'pages.*.elements.*.required' => ['nullable', 'boolean'], 'pages.*.elements.*.style' => ['nullable', 'array'], 'pages.*.elements.*.style.font_size' => ['nullable', 'numeric', 'min:6', 'max:300'], 'pages.*.elements.*.style.line_height' => ['nullable', 'numeric', 'min:0.8', 'max:3'], 'pages.*.elements.*.style.font_weight' => ['nullable', 'in:normal,bold,100,200,300,400,500,600,700,800,900'], 'pages.*.elements.*.style.text_align' => ['nullable', 'in:right,center,left'], 'pages.*.elements.*.style.border_radius' => ['nullable', 'numeric', 'min:0', 'max:500'],
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
