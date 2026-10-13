<?php

namespace App\Jobs\Tenants\BusinessCenter\Customer;

use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Services\Tenants\BusinessCenter\Customer\Import\CustomerAnalysisQueueService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
// use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ProcessCustomerAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $sessionId;

    protected string $tempPath;

    protected Portfolio $portfolio;

    protected int $userId;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 600; // 10 دقائق للتحليل

    /**
     * Delete the job if its models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    public function __construct(
        string $sessionId,
        string $tempPath,
        Portfolio $portfolio,
        int $userId
    ) {
        $this->sessionId = $sessionId;
        $this->tempPath = $tempPath;
        $this->portfolio = $portfolio;
        $this->userId = $userId;

        // Log::info('Customer analysis job created', [
        //     'session_id' => $sessionId,
        //     'portfolio_id' => $portfolio->id,
        //     'user_id' => $userId
        // ]);
    }

    public function handle(): void
    {
        $startTime = microtime(true);

        // Log::info('Starting file analysis', [
        //     'session_id' => $this->sessionId,
        //     'portfolio_id' => $this->portfolio->id
        // ]);

        try {
            $analysisService = app(CustomerAnalysisQueueService::class);

            // فحص حالة الجلسة
            if ($this->isSessionCancelled($analysisService)) {
                // Log::info('Analysis session cancelled - stopping job', [
                //     'session_id' => $this->sessionId
                // ]);
                return;
            }

            // تحديث الحالة: بدء التحليل
            $analysisService->updateAnalysisProgress($this->sessionId, [
                'status' => 'processing',
                'progress_percentage' => 5,
                'current_step' => 'reading_file',
                'processing_started_at' => now()->toISOString(),
            ]);

            // قراءة الملف
            $fullPath = storage_path('app/public/'.$this->tempPath);
            if (! file_exists($fullPath)) {
                throw new \Exception('الملف المؤقت غير موجود');
            }

            // تحديث التقدم: قراءة البيانات
            $analysisService->updateAnalysisProgress($this->sessionId, [
                'progress_percentage' => 15,
                'current_step' => 'loading_data',
            ]);

            // قراءة الملف
            $allSheets = Excel::toArray(null, $fullPath);
            [$rawData, $selectedSheetIndex] = $this->pickBestDataSheet($allSheets, $analysisService);

            if (empty($rawData)) {
                throw new \Exception('تعذر العثور على ورقة بيانات صالحة');
            }

            // تحديث التقدم: تحليل البنية
            $analysisService->updateAnalysisProgress($this->sessionId, [
                'progress_percentage' => 30,
                'current_step' => 'analyzing_structure',
            ]);

            // البحث عن رؤوس الأعمدة
            $headersDetection = $this->smartDetectHeaders($rawData, $analysisService);

            if (! $headersDetection['found']) {
                throw new \Exception('لم يتم العثور على رؤوس أعمدة صالحة في الملف');
            }

            // تحديث التقدم: معالجة الرؤوس
            $analysisService->updateAnalysisProgress($this->sessionId, [
                'progress_percentage' => 50,
                'current_step' => 'processing_headers',
            ]);

            $headers = $this->sanitizeHeaders($headersDetection['headers']);
            $dataStartRow = $headersDetection['data_start_row'];
            $actualData = array_slice($rawData, $dataStartRow);

            // تحديث التقدم: تحليل الحقول
            $analysisService->updateAnalysisProgress($this->sessionId, [
                'progress_percentage' => 70,
                'current_step' => 'analyzing_fields',
            ]);

            // تحليل الرؤوس مقابل حقول المحفظة
            $headerAnalysis = $this->analyzeHeaders($headers, $this->portfolio);

            // تحديث التقدم: تحليل البيانات
            $analysisService->updateAnalysisProgress($this->sessionId, [
                'progress_percentage' => 85,
                'current_step' => 'analyzing_data_quality',
            ]);

            // تحليل جودة البيانات
            $sampleSize = min(1000, count($actualData)); // عينة محدودة للملفات الكبيرة
            $sampleData = array_slice($actualData, 0, $sampleSize);
            $dataAnalysis = $this->analyzeSampleData($sampleData, $headers);
            $dataQuality = $this->enhancedDataQualityAnalysis($sampleData, $headers);

            // تحديث التقدم: الانتهاء
            $analysisService->updateAnalysisProgress($this->sessionId, [
                'progress_percentage' => 100,
                'current_step' => 'completed',
                'status' => 'completed',
            ]);

            $duration = microtime(true) - $startTime;

            // إعداد النتيجة النهائية
            $analysisResult = [
                'total_rows' => count($actualData),
                'total_columns' => count($headers),
                'headers' => $headers,
                'header_analysis' => $headerAnalysis,
                'sample_data' => $dataAnalysis,
                'data_quality' => $dataQuality,
                'file_structure' => [
                    'headers_row' => $headersDetection['headers_row'],
                    'data_start_row' => $dataStartRow,
                    'skipped_rows' => $dataStartRow,
                    'original_rows' => count($actualData) + $dataStartRow,
                ],
                'portfolio_info' => [
                    'name' => $this->portfolio->name,
                    'company' => $this->portfolio->company->name ?? '',
                    'existing_fields' => $this->portfolio->portfolioFields->count(),
                ],
                'analysis_duration_seconds' => round($duration, 2),
                'raw_data_path' => $this->saveRawDataForImport($actualData, $headers),

            ];

            // حفظ النتيجة النهائية
            $analysisService->updateAnalysisProgress($this->sessionId, [
                'analysis_result' => $analysisResult,
                'completed_at' => now()->toISOString(),
            ]);

            // Log::info('File analysis completed successfully', [
            //     'session_id' => $this->sessionId,
            //     'duration_seconds' => round($duration, 2),
            //     'total_rows' => count($actualData),
            //     'total_columns' => count($headers)
            // ]);

        } catch (\Exception $e) {
            $analysisService = app(CustomerAnalysisQueueService::class);

            // Log::error('File analysis failed', [
            //     'session_id' => $this->sessionId,
            //     'error' => $e->getMessage(),
            //     'file' => $e->getFile(),
            //     'line' => $e->getLine()
            // ]);

            $analysisService->updateAnalysisProgress($this->sessionId, [
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'failed_at' => now()->toISOString(),
            ]);

            throw $e;
        } finally {
            // تنظيف الملف المؤقت
            if (file_exists(storage_path('app/public/'.$this->tempPath))) {
                unlink(storage_path('app/public/'.$this->tempPath));
            }
        }
    }

    private function saveRawDataForImport(array $data, array $headers): string
    {
        $tempData = array_merge([$headers], $data);
        $tempFile = 'temp/analysis_data_'.$this->sessionId.'.json';
        Storage::put($tempFile, json_encode($tempData));

        return $tempFile;
    }

    /**
     * فحص إلغاء الجلسة
     */
    private function isSessionCancelled($analysisService): bool
    {
        $status = $analysisService->getAnalysisStatus($this->sessionId);

        return $status['found'] && isset($status['data']['status']) && $status['data']['status'] === 'cancelled';
    }

    /**
     * اختيار أفضل ورقة بيانات
     */
    private function pickBestDataSheet(array $allSheets, $analysisService): array
    {
        $analysisService->updateAnalysisProgress($this->sessionId, [
            'progress_percentage' => 20,
            'current_step' => 'selecting_best_sheet',
        ]);

        $best = ['score' => -INF, 'index' => null, 'data' => []];

        foreach ($allSheets as $i => $sheetData) {
            if (empty($sheetData)) {
                continue;
            }

            $detect = $this->smartDetectHeaders($sheetData, $analysisService);
            if (! $detect['found'] || empty($detect['headers'])) {
                continue;
            }

            $headers = $this->sanitizeHeaders($detect['headers']);
            $cols = count(array_filter($headers, fn ($h) => trim((string) $h) !== ''));

            $pivotPenalty = $this->countPivotTokens($headers) * 12;
            $validNames = 0;
            foreach ($headers as $h) {
                if ($this->isValidHeaderName($h)) {
                    $validNames++;
                }
            }

            $score = ($detect['quality_score'] ?? 0) + ($cols * 3) + ($validNames * 2) - $pivotPenalty;

            if ($cols >= 6) {
                $score += 10;
            }
            if ($validNames >= 2) {
                $score += 5;
            }

            if ($score > $best['score']) {
                $best = ['score' => $score, 'index' => $i, 'data' => $sheetData];
            }
        }

        if (! empty($best['data'])) {
            $detect = $this->smartDetectHeaders($best['data'], $analysisService);
            $headers = $this->sanitizeHeaders($detect['headers'] ?? []);
            $cols = count(array_filter($headers, fn ($h) => trim((string) $h) !== ''));
            $pivotLike = $this->looksLikePivotHeaders($headers);

            if ($pivotLike && $cols <= 4) {
                return [[], null];
            }
        }

        return [$best['data'], $best['index']];
    }

    /**
     * البحث الذكي عن رؤوس الأعمدة
     */
    private function smartDetectHeaders(array $rawData, $analysisService): array
    {
        $attempts = [];
        $maxRowsToScan = min(10, count($rawData));

        for ($rowIndex = 0; $rowIndex < $maxRowsToScan; $rowIndex++) {
            $row = $rawData[$rowIndex] ?? [];

            $rowAnalysis = [
                'row_index' => $rowIndex,
                'non_empty_cells' => 0,
                'valid_header_names' => 0,
                'total_cells' => count($row),
                'suspicious_patterns' => [],
            ];

            foreach ($row as $cellIndex => $cell) {
                $cellValue = trim($cell ?? '');

                if (! empty($cellValue)) {
                    $rowAnalysis['non_empty_cells']++;

                    if ($this->isValidHeaderName($cellValue)) {
                        $rowAnalysis['valid_header_names']++;
                    }

                    if ($this->isSuspiciousHeaderPattern($cellValue)) {
                        $rowAnalysis['suspicious_patterns'][] = $cellValue;
                    }
                }
            }

            $qualityScore = $this->calculateHeaderRowQuality($rowAnalysis);
            $rowAnalysis['quality_score'] = $qualityScore;

            $attempts[] = $rowAnalysis;

            if ($qualityScore > 70 && $rowAnalysis['valid_header_names'] >= 3) {
                return [
                    'found' => true,
                    'headers_row' => $rowIndex,
                    'data_start_row' => $rowIndex + 1,
                    'headers' => $row,
                    'quality_score' => $qualityScore,
                    'attempts' => $attempts,
                ];
            }
        }

        $bestRow = null;
        $bestScore = 0;

        foreach ($attempts as $attempt) {
            if ($attempt['quality_score'] > $bestScore) {
                $bestScore = $attempt['quality_score'];
                $bestRow = $attempt;
            }
        }

        if ($bestRow && $bestScore > 30) {
            return [
                'found' => true,
                'headers_row' => $bestRow['row_index'],
                'data_start_row' => $bestRow['row_index'] + 1,
                'headers' => $rawData[$bestRow['row_index']],
                'quality_score' => $bestScore,
                'attempts' => $attempts,
                'low_confidence' => true,
            ];
        }

        return [
            'found' => false,
            'attempts' => $attempts,
        ];
    }

    // الدوال المساعدة (مختصرة لتوفير المساحة)
    private function sanitizeHeaders(array $headers): array
    {
        return array_map(fn ($h) => $this->stripAggregationPrefix((string) $h), $headers);
    }

    private function stripAggregationPrefix(string $header): string
    {
        $h = trim($header);
        $h = preg_replace('/^(distinct\s+count\s+of|count\s+of|sum\s+of|average\s+of|avg\s+of|min\s+of|max\s+of)\s+/i', '', $h);
        $h = preg_replace('/^(إجمالي|المجموع|مجموع|عدد|متوسط|الحد\s+الأدنى|الحد\s+الأقصى)\s+/u', '', $h);

        return trim($h);
    }

    private function countPivotTokens(array $headers): int
    {
        $tokens = ['row labels', 'column labels', 'grand total', 'values', 'sum of', 'count of'];
        $c = 0;
        foreach ($headers as $h) {
            $x = mb_strtolower((string) $h);
            foreach ($tokens as $t) {
                if (mb_strpos($x, $t) !== false) {
                    $c++;
                    break;
                }
            }
        }

        return $c;
    }

    private function looksLikePivotHeaders(array $headers): bool
    {
        return $this->countPivotTokens($headers) >= 1;
    }

    private function isValidHeaderName(string $value): bool
    {
        $value = trim($value);
        if (empty($value) || is_numeric($value)) {
            return false;
        }

        $validPatterns = ['اسم', 'نوع', 'رقم', 'هوية', 'هاتف', 'name', 'type', 'phone', 'email'];
        foreach ($validPatterns as $pattern) {
            if (stripos($value, $pattern) !== false) {
                return true;
            }
        }

        return strlen($value) >= 3 && strlen($value) <= 30;
    }

    private function isSuspiciousHeaderPattern(string $value): bool
    {
        return preg_match('/^تقرير\s+/', $value) || is_numeric($value);
    }

    private function calculateHeaderRowQuality(array $rowAnalysis): float
    {
        $score = 0;
        if ($rowAnalysis['total_cells'] > 0) {
            $score += ($rowAnalysis['non_empty_cells'] / $rowAnalysis['total_cells']) * 40;
        }
        if ($rowAnalysis['non_empty_cells'] > 0) {
            $score += ($rowAnalysis['valid_header_names'] / $rowAnalysis['non_empty_cells']) * 50;
        }
        $score -= count($rowAnalysis['suspicious_patterns']) * 10;
        if ($rowAnalysis['valid_header_names'] >= 3) {
            $score += 20;
        }

        return max(0, min(100, $score));
    }

    private function analyzeHeaders(array $headers, Portfolio $portfolio): array
    {
        $existingFieldsNames = $portfolio->portfolioFields->pluck('field_name')->map(function ($name) {
            return $this->normalizeHeader($name);
        })->toArray();

        // نفس الخريطة الكاملة من الكود الأصلي
        $staticFieldsMap = [
            'الاسم الاول' => 'first_name',
            'الاسم الأول' => 'first_name',
            'first name' => 'first_name',
            'اسم الاب' => 'father_name',
            'اسم الأب' => 'father_name',
            'father name' => 'father_name',
            'اسم الجد' => 'grandfather_name',
            'grandfather name' => 'grandfather_name',
            'اسم العائلة' => 'family_name',
            'family name' => 'family_name',
            'اسم العميل' => 'name',
            'customer name' => 'name',
            'نوع العميل' => 'customer_type',
            'نوع الهوية' => 'customer_type',
            'customer type' => 'customer_type',
            'رقم الهوية' => 'identity_number',
            'رقم معرف العميل' => 'identity_number',
            'معرف العميل' => 'identity_number',
            'الاقامة' => 'identity_number',
            'الهوية' => 'identity_number',
            'identity number' => 'identity_number',
            'الجنسية' => 'nationality',
            'nationality' => 'nationality',
            'الهاتف الرئيسي' => 'phone',
            'الهاتف الاساسي' => 'phone',
            'رقم الهاتف الاساسي' => 'phone',
            'الهاتف' => 'phone',
            'هاتف' => 'phone',
            'phone' => 'phone',
            'mobile' => 'phone',
            'المديونية' => 'debt_amount',
            'مبلغ الدين' => 'debt_amount',
            'الرصيد المتاخر' => 'debt_amount',
            'إجمالى المديونية' => 'debt_amount',
            'اجمالي المديونية' => 'debt_amount',
            'debt amount' => 'debt_amount',
            'رقم الحساب' => 'account_number',
            'الحساب' => 'account_number',
            'account number' => 'account_number',
            'حساب' => 'account_number',
            'حالة العميل' => 'customer_status_id',
            'customer status' => 'customer_status_id',
            'مسؤول العلاقة' => 'relationship_manager_id',
            'مدير العلاقة' => 'relationship_manager_id',
            'relationship manager' => 'relationship_manager_id',
            'ملاحظات' => 'notes',
            'notes' => 'notes',
            'تاريخ العقد' => 'contract_date',
            'contract date' => 'contract_date',
            'معدل العمولة' => 'commission_rate',
            'commission rate' => 'commission_rate',
            'تاريخ التعثر' => 'default_date',
            'default date' => 'default_date',
        ];

        $analysis = ['recognized' => [], 'new_fields' => [], 'phone_fields' => []];

        foreach ($headers as $index => $header) {
            $cleanHeader = trim($header);
            if (empty($cleanHeader) || $this->isInvalidHeader($cleanHeader)) {
                continue;
            }

            $normalizedHeader = $this->normalizeHeader($header);

            // 1. فحص حقول الهواتف الإضافية أولاً
            if (preg_match('/(هاتف|جوال|رقم\s*هاتف)\s*(اضافي|إضافي|ثاني|ثالث)?\s*(\d+)?/u', $normalizedHeader, $matches)) {
                $phoneIndex = isset($matches[3]) ? (int) $matches[3] : 2;
                $analysis['phone_fields'][] = [
                    'index' => $index,
                    'name' => $header,
                    'type' => 'phone',
                    'field' => ['index' => $phoneIndex, 'type' => 'phone'],
                ];

                continue;
            }

            // 2. فحص الحقول الثابتة
            $staticField = $this->findMatchingStaticField($normalizedHeader, $staticFieldsMap);
            if ($staticField) {
                $analysis['recognized'][] = [
                    'index' => $index,
                    'name' => $header,
                    'type' => 'static',
                    'field' => $staticField,
                ];

                continue;
            }

            // 3. فحص الحقول الموجودة في المحفظة
            if (in_array($normalizedHeader, $existingFieldsNames)) {
                $existingField = $portfolio->portfolioFields->first(function ($field) use ($normalizedHeader) {
                    return $this->normalizeHeader($field->field_name) === $normalizedHeader;
                });

                if ($existingField) {
                    $analysis['recognized'][] = [
                        'index' => $index,
                        'name' => $header,
                        'type' => 'existing_dynamic',
                        'field' => $existingField,
                    ];

                    continue;
                }
            }

            // 4. إنشاء حقل جديد
            if ($this->shouldCreateNewField($cleanHeader)) {
                $detectedType = $this->detectFieldType($cleanHeader);
                $analysis['new_fields'][] = [
                    'index' => $index,
                    'name' => $header,
                    'type' => 'new_dynamic',
                    'field' => $cleanHeader,
                    'detected_type' => $detectedType,
                ];
            }
        }

        return $analysis;
    }

    private function findMatchingStaticField(string $header, array $staticFieldsMap): ?string
    {
        // مطابقة دقيقة أولاً
        if (isset($staticFieldsMap[$header])) {
            return $staticFieldsMap[$header];
        }

        // مطابقة جزئية
        foreach ($staticFieldsMap as $key => $field) {
            if (mb_strpos($header, $this->normalizeHeader($key)) !== false) {
                return $field;
            }
        }

        return null;
    }

    private function isInvalidHeader(string $header): bool
    {
        $invalidPatterns = ['—', '-', '', 'فارغ', 'empty', 'null', '###', 'sum of', 'count of'];
        $cleanHeader = strtolower(trim($header));

        if (strlen($cleanHeader) < 2) {
            return true;
        }

        foreach ($invalidPatterns as $pattern) {
            if (stripos($cleanHeader, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    private function normalizeHeader(string $str): string
    {
        $clean = trim($str);
        $clean = preg_replace('/\s+/', ' ', $clean);
        $clean = str_replace(['*', '(مطلوب)', '(إجباري)'], '', $clean);

        return mb_strtolower(trim($clean), 'UTF-8');
    }

    private function shouldCreateNewField(string $header): bool
    {
        return strlen(trim($header)) >= 2 && ! is_numeric(trim($header));
    }

    private function analyzeSampleData(array $sampleData, array $headers): array
    {
        $analysis = ['preview' => []];
        $maxRows = min(5, count($sampleData));

        for ($i = 0; $i < $maxRows; $i++) {
            $row = $sampleData[$i] ?? [];
            $previewRow = [];
            foreach ($headers as $colIndex => $header) {
                $previewRow[$header] = $row[$colIndex] ?? '';
            }
            $analysis['preview'][] = $previewRow;
        }

        return $analysis;
    }

    private function enhancedDataQualityAnalysis(array $data, array $headers): array
    {
        $totalCells = count($data) * count($headers);
        $emptyCells = 0;
        $validCells = 0;
        $columnAnalysis = [];

        foreach ($headers as $colIndex => $header) {
            $columnData = array_column($data, $colIndex);
            $nonEmptyValues = array_filter($columnData, function ($value) {
                return ! empty(trim($value));
            });

            $columnAnalysis[$header] = [
                'total_values' => count($columnData),
                'non_empty_values' => count($nonEmptyValues),
                'empty_percentage' => round((1 - count($nonEmptyValues) / count($columnData)) * 100, 2),
            ];
        }

        foreach ($data as $row) {
            foreach ($row as $cell) {
                if (empty(trim($cell))) {
                    $emptyCells++;
                } else {
                    $validCells++;
                }
            }
        }

        $emptyCellsPercentage = $totalCells > 0 ? round(($emptyCells / $totalCells) * 100, 2) : 0;
        $qualityScore = max(0, 100 - $emptyCellsPercentage);

        return [
            'quality_score' => $qualityScore,
            'indicators' => [
                'empty_cells_percentage' => $emptyCellsPercentage,
                'valid_cells_count' => $validCells,
                'total_cells' => $totalCells,
            ],
            'column_analysis' => $columnAnalysis,
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $analysisService = app(CustomerAnalysisQueueService::class);

        // Log::error('Customer analysis job failed permanently', [
        //     'session_id' => $this->sessionId,
        //     'error' => $exception->getMessage(),
        //     'trace' => $exception->getTraceAsString()
        // ]);

        $analysisService->updateAnalysisProgress($this->sessionId, [
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'failed_at' => now()->toISOString(),
        ]);
    }
}
