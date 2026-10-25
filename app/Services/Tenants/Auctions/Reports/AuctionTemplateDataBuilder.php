<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

use Carbon\Carbon;

final class AuctionTemplateDataBuilder
{
    private static ?string $almaraiRegularBase64 = null;
    private static ?string $almaraiBoldBase64 = null;

    /**
     * Build the dynamic JSON data payload for the 'haram' template (Outputs).
     *
     * @param array<string, mixed> $snapshot
     * @return array<string, mixed>
     */
    public function buildOutputsPayload(array $snapshot): array
    {
        $auction = $snapshot['auction'] ?? [];
        $assignment = $snapshot['assignment'] ?? [];
        $properties = $snapshot['properties'] ?? [];
        $totals = $snapshot['totals'] ?? [];

        $name = (string) ($auction['name'] ?? 'مزاد عقاري');
        $manager = (string) ($auction['project_manager_name'] ?? 'إدارة المزادات');
        if ($manager === '-' || blank($manager)) {
            $manager = 'إدارة المزادات';
        }
        $assignmentName = (string) ($assignment['name'] ?? $name);
        if ($assignmentName === '-' || blank($assignmentName)) {
            $assignmentName = $name;
        }

        $startDateStr = $this->formatDate($auction['start_date'] ?? null);
        $endDateStr = $this->formatDate($auction['end_date'] ?? null);
        $dateRange = $startDateStr . ' - ' . $endDateStr;

        // Properties table rows
        $propRows = [];
        foreach ($properties as $index => $prop) {
            $area = !empty($prop['area']) ? number_format((float) $prop['area']) . ' م²' : '—';
            $number = (string) ($prop['number'] ?? ($index + 1));
            $deed = (string) ($prop['deed_number'] ?? ($prop['deed'] ?? '—'));
            if ($deed === '-' || blank($deed)) {
                $deed = '—';
            }
            $type = (string) ($prop['type'] ?? 'عقار');

            $propRows[] = [
                'source' => 0,
                'cells' => [
                    $area,
                    $number,
                    $deed,
                    $type,
                    (string) ($index + 1),
                ],
            ];
        }

        if (empty($propRows)) {
            $propRows[] = [
                'source' => 0,
                'cells' => ['—', 'PROP-01', '—', 'عقار استثماري', '1'],
            ];
        }

        // Sold and Unsold tables
        $soldRows = [];
        $unsoldRows = [];
        $soldIdx = 1;
        $unsoldIdx = 1;

        foreach ($properties as $prop) {
            $status = $prop['result_status'] ?? 'pending';
            $pName = (string) ($prop['name'] ?? 'عقار');

            if ($status === 'sold') {
                $salePrice = !empty($prop['sale_price']) ? number_format((float) $prop['sale_price'], 0) : '—';
                $openingPrice = !empty($prop['opening_price']) ? number_format((float) $prop['opening_price'], 0) : '—';
                $participants = (string) ($prop['participants_count'] ?? 1);
                $bids = (string) ($prop['bids_count'] ?? 1);

                $soldRows[] = [
                    'source' => 0,
                    'cells' => [
                        $salePrice,
                        $openingPrice,
                        $participants,
                        $bids,
                        $pName,
                        (string) ($soldIdx++),
                    ],
                ];
            } elseif ($status === 'unsold') {
                $reason = (string) ($prop['unsold_reason'] ?? 'عدم وصول المزايدات للسعر التحفظي');
                if (blank($reason)) {
                    $reason = 'عدم وصول المزايدات للسعر التحفظي';
                }

                $unsoldRows[] = [
                    'source' => 0,
                    'cells' => [
                        $reason,
                        $pName,
                        (string) ($unsoldIdx++),
                    ],
                ];
            }
        }

        // Keep at least one placeholder if empty to avoid rendering errors
        if (empty($soldRows)) {
            $soldRows[] = [
                'source' => 0,
                'cells' => ['—', '—', '—', '—', 'لا توجد عقارات مباعة مسجلة', '1'],
            ];
        }
        if (empty($unsoldRows)) {
            $unsoldRows[] = [
                'source' => 0,
                'cells' => ['جميع العقارات بيعت أو قيد الإجراء', '—', '1'],
            ];
        }

        return [
            'version' => 1,
            'template' => 'haram',
            'pages' => [
                [
                    'template' => 0,
                    'values' => [
                        'f3' => $name,
                        'f4' => 'مخرجات ' . $name,
                        'f5' => $dateRange,
                        'f6' => (string) count($properties),
                        'f7' => $manager,
                        'f8' => $assignmentName,
                        'f14' => '- ' . $name,
                    ],
                    'images' => (object)[],
                    'tables' => (object)[],
                ],
                [
                    'template' => 1,
                    'values' => [
                        'f5' => $manager,
                        'f6' => $name,
                        'f7' => $assignmentName,
                        'f11' => $endDateStr . ' م',
                        'f12' => $startDateStr . ' م',
                        'f89' => 'مخرجات المزاد - ' . $name,
                    ],
                    'images' => (object)[],
                    'tables' => [
                        'properties' => $propRows,
                    ],
                ],
                [
                    'template' => 2,
                    'values' => [
                        'f7' => 'نظمت شركة ليفان العقارية مزاد ”' . $name . '“ وفق أعلى معايير الشفافية والتنظيم الاحترافي تحت إشراف مركز الإسناد والتصفية (إنفاذ).',
                        'f84' => 'مخرجات المزاد - ' . $name,
                    ],
                    'images' => (object)[],
                    'tables' => [
                        'sold' => $soldRows,
                        'unsold' => $unsoldRows,
                    ],
                ],
                [
                    'template' => 3,
                    'values' => [
                        'f69' => 'مخرجات المزاد - ' . $name,
                    ],
                    'images' => (object)[],
                    'tables' => (object)[],
                ],
                [
                    'template' => 4,
                    'values' => [
                        'f8' => 'مخرجات المزاد - ' . $name,
                    ],
                    'images' => (object)[],
                    'tables' => (object)[],
                ],
                [
                    'template' => 5,
                    'values' => [
                        'f6' => 'مخرجات المزاد - ' . $name,
                    ],
                    'images' => (object)[],
                    'tables' => (object)[],
                ],
                [
                    'template' => 6,
                    'values' => [
                        'f6' => '- ' . $name,
                    ],
                    'images' => (object)[],
                    'tables' => (object)[],
                ],
            ],
            'customFonts' => $this->getCustomFonts('haram'),
        ];
    }

    /**
     * Build the dynamic JSON data payload for the 'asir' template (Report).
     *
     * @param array<string, mixed> $snapshot
     * @return array<string, mixed>
     */
    public function buildReportPayload(array $snapshot): array
    {
        $auction = $snapshot['auction'] ?? [];
        $assignment = $snapshot['assignment'] ?? [];
        $properties = $snapshot['properties'] ?? [];

        $name = (string) ($auction['name'] ?? 'مزاد عقاري');
        $manager = (string) ($auction['project_manager_name'] ?? 'إدارة المزادات');
        if ($manager === '-' || blank($manager)) {
            $manager = 'إدارة المزادات';
        }
        $assignmentName = (string) ($assignment['name'] ?? $name);
        if ($assignmentName === '-' || blank($assignmentName)) {
            $assignmentName = $name;
        }

        $startDateStr = $this->formatDate($auction['start_date'] ?? null);
        $endDateStr = $this->formatDate($auction['end_date'] ?? null);

        // Properties table rows
        $propRows = [];
        foreach ($properties as $index => $prop) {
            $number = (string) ($prop['number'] ?? ($index + 1));
            $deed = (string) ($prop['deed_number'] ?? ($prop['deed'] ?? '—'));
            if ($deed === '-' || blank($deed)) {
                $deed = '—';
            }
            $type = (string) ($prop['type'] ?? 'عقار');

            $propRows[] = [
                'source' => 0,
                'cells' => [
                    '٦*٣',
                    '١',
                    $number,
                    $deed,
                    $type,
                    (string) ($index + 1),
                ],
            ];
        }

        if (empty($propRows)) {
            $propRows[] = [
                'source' => 0,
                'cells' => ['٦*٣', '١', 'PROP-01', '—', 'عقار استثماري', '١'],
            ];
        }

        // Sold rows for page 18
        $soldRows = [];
        $unsoldRows = [];
        $unsoldIdx = 1;

        foreach ($properties as $prop) {
            $status = $prop['result_status'] ?? 'pending';
            $pNumber = (string) ($prop['number'] ?? '—');
            $deed = (string) ($prop['deed_number'] ?? ($prop['deed'] ?? '—'));
            $type = (string) ($prop['type'] ?? 'عقار');

            if ($status === 'sold') {
                $salePrice = !empty($prop['sale_price']) ? number_format((float) $prop['sale_price'], 0) : '—';
                $openingPrice = !empty($prop['opening_price']) ? number_format((float) $prop['opening_price'], 0) : '—';
                $participants = (string) ($prop['participants_count'] ?? 1);
                $bids = (string) ($prop['bids_count'] ?? 1);

                $soldRows[] = [
                    'source' => 0,
                    'cells' => [
                        $bids,
                        $participants,
                        $salePrice,
                        $openingPrice,
                        $deed !== '—' ? $deed : $pNumber,
                    ],
                ];
            } elseif ($status === 'unsold') {
                $reason = (string) ($prop['unsold_reason'] ?? 'عدم وصول المزايدات للسعر التحفظي');
                if (blank($reason)) {
                    $reason = 'عدم وصول المزايدات للسعر التحفظي';
                }

                $unsoldRows[] = [
                    'source' => 0,
                    'cells' => [
                        $reason,
                        $deed,
                        $pNumber,
                        $type,
                        (string) ($unsoldIdx++),
                    ],
                ];
            }
        }

        if (empty($soldRows)) {
            $soldRows[] = [
                'source' => 0,
                'cells' => ['—', '—', '—', '—', '—'],
            ];
        }

        if (empty($unsoldRows)) {
            $unsoldRows[] = [
                'source' => 0,
                'cells' => ['جميع العقارات بيعت أو قيد الإجراء', '—', '—', '—', '١'],
            ];
        }

        $pages = [];

        // Page 0 (Cover)
        $pages[0] = [
            'template' => 0,
            'values' => [
                'f0' => $name,
                'f1' => 'التقرير النهائي لمزاد ' . $name,
            ],
            'images' => (object)[],
            'tables' => (object)[],
        ];

        // Page 1 (Project details & Properties)
        $pages[1] = [
            'template' => 1,
            'values' => [
                'f3' => $manager,
                'f4' => $name,
                'f5' => $assignmentName,
                'f14' => $endDateStr,
                'f15' => $startDateStr,
            ],
            'images' => (object)[],
            'tables' => [
                'properties' => $propRows,
            ],
        ];

        // Page 2 (Intro)
        $pages[2] = [
            'template' => 2,
            'values' => [
                'f0' => 'قـامت شركــة ليفـان  العقــاريـة بالتحضيـر',
                'f1' => 'لإقامـة ' . $name . ' وفقا لخطتهــا',
            ],
            'images' => (object)[],
            'tables' => (object)[],
        ];

        // Fill pages 3 to 17 as standard design pages
        for ($i = 3; $i <= 17; $i++) {
            $pages[$i] = [
                'template' => $i,
                'values' => (object)[],
                'images' => (object)[],
                'tables' => (object)[],
            ];
        }

        // Page 18 (Sold results)
        $pages[18] = [
            'template' => 18,
            'values' => (object)[],
            'images' => (object)[],
            'tables' => [
                'sales' => $soldRows,
            ],
        ];

        // Page 19 (Unsold results)
        $pages[19] = [
            'template' => 19,
            'values' => (object)[],
            'images' => (object)[],
            'tables' => [
                'unsold' => $unsoldRows,
            ],
        ];

        // Page 20 (Closing)
        $pages[20] = [
            'template' => 20,
            'values' => (object)[],
            'images' => (object)[],
            'tables' => (object)[],
        ];

        return [
            'version' => 1,
            'template' => 'asir',
            'pages' => $pages,
            'customFonts' => $this->getCustomFonts('asir'),
        ];
    }

    /**
     * Map all font families in the template to Almarai with full Unicode coverage.
     *
     * @return array<string, string>
     */
    public function getCustomFonts(string $templateKey): array
    {
        $this->loadAlmaraiFonts();

        $regularUri = 'data:font/ttf;base64,' . self::$almaraiRegularBase64;
        $boldUri = 'data:font/ttf;base64,' . self::$almaraiBoldBase64;

        if ($templateKey === 'haram') {
            return [
                'NotoSansArabic-Bold' => $boldUri,
                'Arimo-Bold' => $boldUri,
                'Montserrat-Regular' => $regularUri,
                'NotoSansArabic-Regular' => $regularUri,
                'Montserrat-Medium' => $boldUri,
                'IBMPlexSansCond' => $regularUri,
            ];
        }

        return [
            'GEContrastBold-Bold' => $boldUri,
            'IBMPlexSansArabic-Medium' => $boldUri,
            'NizarBBCKurdish' => $regularUri,
            'STV' => $boldUri,
            'TheSans-Plain' => $regularUri,
            'TheSans-Bold' => $boldUri,
            'LamaSans-Bold' => $boldUri,
            'RuaqArabic-Medium' => $boldUri,
            'ArialMT' => $regularUri,
            'Calibri' => $regularUri,
            'Arial-BoldMT' => $boldUri,
            'SymbolMT' => $regularUri,
            'MyriadPro-Regular' => $regularUri,
        ];
    }

    private function loadAlmaraiFonts(): void
    {
        if (self::$almaraiRegularBase64 === null) {
            $regPath = public_path('static/fonts/almarai/Almarai-Regular.ttf');
            if (file_exists($regPath)) {
                self::$almaraiRegularBase64 = base64_encode(file_get_contents($regPath));
            } else {
                self::$almaraiRegularBase64 = '';
            }
        }

        if (self::$almaraiBoldBase64 === null) {
            $boldPath = public_path('static/fonts/almarai/Almarai-Bold.ttf');
            if (file_exists($boldPath)) {
                self::$almaraiBoldBase64 = base64_encode(file_get_contents($boldPath));
            } else {
                self::$almaraiBoldBase64 = '';
            }
        }
    }

    private function formatDate(?string $date): string
    {
        if (blank($date)) {
            return '—';
        }

        try {
            return Carbon::parse($date)->translatedFormat('d F Y');
        } catch (\Throwable) {
            return (string) $date;
        }
    }
}
