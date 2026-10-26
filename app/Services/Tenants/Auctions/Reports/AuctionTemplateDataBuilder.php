<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

use Carbon\Carbon;

final class AuctionTemplateDataBuilder
{
    private const TRANSPARENT_PIXEL = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAA=';

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
        $evidence = $snapshot['evidence'] ?? [];
        $marketing = $snapshot['marketing'] ?? [];

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

        $coverImage = $auction['cover_image'] ?? $auction['report_image'] ?? null;
        $propImages = collect($properties)->pluck('image')->filter()->values()->all();
        $billboardAttachments = collect($evidence['billboards'] ?? ($evidence['marketing'] ?? []))->pluck('uri')->filter()->values()->all();
        $brokerAttachments = collect($evidence['brokers'] ?? [])->pluck('uri')->filter()->values()->all();

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

        $pages = [];

        // Page 0 (Cover)
        $coverImg0 = $coverImage ?: ($propImages[0] ?? self::TRANSPARENT_PIXEL);
        $coverImg1 = ($propImages[0] ?? $coverImage) ?: self::TRANSPARENT_PIXEL;

        $pages[] = [
            'template' => 0,
            'values' => [
                'f3' => $name,
                'f4' => 'مخرجات ' . $name,
                'f5' => $dateRange,
                'f6' => (string) count($properties),
                'f7' => \Illuminate\Support\Str::limit($manager, 12),
                'f8' => \Illuminate\Support\Str::limit($assignmentName, 12),
                'f14' => '- ' . $name,
            ],
            'images' => [
                'i0' => $coverImg0,
                'i1' => $coverImg1,
            ],
            'tables' => (object)[],
        ];

        // Page 1 (Properties list)
        $pages[] = [
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
        ];

        // Page 2 (Sales summary & Unsold)
        $pages[] = [
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
        ];

        // Page 3 (Marketing) - include only if marketing data or evidence exists
        $hasMarketingData = !empty($marketing) || !empty($evidence['marketing'] ?? null);
        if ($hasMarketingData) {
            $mImages = [];
            for ($i = 0; $i <= 8; $i++) {
                $mImages['i' . $i] = self::TRANSPARENT_PIXEL;
            }
            $pages[] = [
                'template' => 3,
                'values' => [
                    'f69' => 'مخرجات المزاد - ' . $name,
                ],
                'images' => $mImages,
                'tables' => (object)[],
            ];
        }

        // Page 4 (Billboards) - include only if billboard attachments exist
        if (!empty($billboardAttachments)) {
            $bbImages = [];
            for ($i = 0; $i <= 10; $i++) {
                $bbImages['i' . $i] = $billboardAttachments[$i] ?? self::TRANSPARENT_PIXEL;
            }
            $pages[] = [
                'template' => 4,
                'values' => [
                    'f8' => 'مخرجات المزاد - ' . $name,
                ],
                'images' => $bbImages,
                'tables' => (object)[],
            ];
        }

        // Page 5 (Broker partners) - include only if broker attachments exist
        if (!empty($brokerAttachments)) {
            $brImages = [];
            for ($i = 0; $i <= 2; $i++) {
                $brImages['i' . $i] = $brokerAttachments[$i] ?? self::TRANSPARENT_PIXEL;
            }
            $pages[] = [
                'template' => 5,
                'values' => [
                    'f6' => 'مخرجات المزاد - ' . $name,
                ],
                'images' => $brImages,
                'tables' => (object)[],
            ];
        }

        // Page 6 (Closing)
        $pages[] = [
            'template' => 6,
            'values' => [
                'f6' => '- ' . $name,
            ],
            'images' => (object)[],
            'tables' => (object)[],
        ];

        return [
            'version' => 1,
            'template' => 'haram',
            'pages' => $pages,
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
        $totals = $snapshot['totals'] ?? [];
        $evidence = $snapshot['evidence'] ?? [];

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

        $startCarbon = !empty($auction['start_date']) ? Carbon::parse($auction['start_date'])->locale('ar') : null;
        $endCarbon = !empty($auction['end_date']) ? Carbon::parse($auction['end_date'])->locale('ar') : null;

        $startDayNum = $startCarbon ? $startCarbon->translatedFormat('d') : '—';
        $startMonth = $startCarbon ? $startCarbon->translatedFormat('F') : '—';
        $startYear = $startCarbon ? $startCarbon->translatedFormat('Y') : '—';
        $startDayName = $startCarbon ? 'يوم ' . $startCarbon->translatedFormat('l') : '—';
        $startTime = !empty($auction['start_time']) ? 'الساعة ' . $auction['start_time'] : 'الساعة 10:00 صباحاً';

        $endDayNum = $endCarbon ? $endCarbon->translatedFormat('d') : '—';
        $endMonth = $endCarbon ? $endCarbon->translatedFormat('F') : '—';
        $endYear = $endCarbon ? $endCarbon->translatedFormat('Y') : '—';
        $endDayName = $endCarbon ? 'يوم ' . $endCarbon->translatedFormat('l') : '—';
        $endTime = !empty($auction['end_time']) ? 'الساعة ' . $auction['end_time'] : 'الساعة 05:00 مساءً';

        $coverImage = $auction['cover_image'] ?? $auction['report_image'] ?? null;
        $propImages = collect($properties)->pluck('image')->filter()->values()->all();
        $billboardAttachments = collect($evidence['billboards'] ?? ($evidence['marketing'] ?? []))->pluck('uri')->filter()->values()->all();

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

        // Page 0 (Cover) - replace all cover image layers (top watermark and bottom landscape)
        $coverImgUri = $coverImage ?: ($propImages[0] ?? self::TRANSPARENT_PIXEL);
        $pages[] = [
            'template' => 0,
            'values' => [
                'f0' => $name,
                'f1' => 'التقرير النهائي لمزاد ' . $name,
            ],
            'images' => [
                'i0' => $coverImgUri,
                'i2' => $coverImgUri,
                'i3' => $coverImgUri,
                'i5' => $coverImgUri,
            ],
            'tables' => (object)[],
        ];

        // Page 1 (Project details & Properties)
        $pages[] = [
            'template' => 1,
            'values' => [
                'f3' => $manager,
                'f4' => $name,
                'f5' => \Illuminate\Support\Str::limit($assignmentName, 26),
                'f9' => $endDayName,
                'f11' => $startDayName,
                'f12' => $endMonth,
                'f13' => $startMonth,
                'f14' => $endDateStr,
                'f15' => $startDateStr,
                'f16' => '',
                'f17' => $endYear,
                'f18' => $startYear,
                'f19' => $endTime,
                'f20' => $startTime,
                'f21' => $endDayNum,
                'f22' => $startDayNum,
                'f23' => '',
                'f24' => '', // Hide duplicate glued header string
            ],
            'images' => (object)[],
            'tables' => [
                'properties' => $propRows,
            ],
        ];

        // Page 2 (Intro)
        $pages[] = [
            'template' => 2,
            'values' => [
                'f0' => 'قـامت شركــة ليفـان  العقــاريـة بالتحضيـر',
                'f1' => 'لإقامـة ' . $name . ' وفقا لخطتهــا',
            ],
            'images' => (object)[],
            'tables' => (object)[],
        ];

        // Page 7 (Property Photos) - include only if property images exist
        if (!empty($propImages)) {
            $pImages = [];
            for ($i = 2; $i <= 7; $i++) {
                $idx = $i - 2;
                $pImages['i' . $i] = $propImages[$idx] ?? ($propImages[0] ?? self::TRANSPARENT_PIXEL);
            }
            $pages[] = [
                'template' => 7,
                'values' => (object)[],
                'images' => $pImages,
                'tables' => (object)[],
            ];
        }

        // Page 8 (Billboard Photos) - include only if billboard evidence exists
        if (!empty($billboardAttachments)) {
            $bImages = [];
            for ($i = 2; $i <= 9; $i++) {
                $idx = $i - 2;
                $bImages['i' . $i] = $billboardAttachments[$idx] ?? self::TRANSPARENT_PIXEL;
            }
            $pages[] = [
                'template' => 8,
                'values' => (object)[],
                'images' => $bImages,
                'tables' => (object)[],
            ];
        }

        // Page 18 (Sold results)
        $pages[] = [
            'template' => 18,
            'values' => (object)[],
            'images' => (object)[],
            'tables' => [
                'sales' => $soldRows,
            ],
        ];

        // Page 19 (Unsold results) - include only if there are unsold properties
        if (($totals['unsold_count'] ?? 0) > 0) {
            $pages[] = [
                'template' => 19,
                'values' => (object)[],
                'images' => (object)[],
                'tables' => [
                    'unsold' => $unsoldRows,
                ],
            ];
        }

        // Page 20 (Closing)
        $pages[] = [
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
            $regularPath = public_path('static/fonts/almarai/Almarai-Regular.ttf');
            if (file_exists($regularPath)) {
                self::$almaraiRegularBase64 = base64_encode(file_get_contents($regularPath));
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
            $carbon = Carbon::parse($date)->locale('ar');
            return $carbon->translatedFormat('d F Y');
        } catch (\Throwable) {
            return (string) $date;
        }
    }
}
