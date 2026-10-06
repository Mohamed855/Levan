<?php

namespace App\Services\Tenants\BusinessCenter\Customer\Import;

use App\Enums\Tenants\CustomerImport\CustomerImportFields;
use App\Exports\Tenants\BusinessCenter\Customer\Import\Error\CustomerImportErrorExport;
use App\Exports\Tenants\BusinessCenter\Customer\Import\Template\CustomerImportTemplateExport;
use App\Jobs\Tenants\BusinessCenter\Customer\ProcessCustomerImportJob;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Customers\ImportSession\ImportSession;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\BusinessCenter\Portfolio\PortfolioField;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CustomerImportService
{
    public function __construct(private CustomerFieldMappingService $fieldMappingService) {}

    // تحميل القالب بالعناوين حسب المحفظة
    public function generateImportTemplate(Portfolio $portfolio)
    {
        $fileName = "customer_import_template_{$portfolio->name}_" . now()->format('Y_m_d') . '.xlsx';

        return Excel::download(new CustomerImportTemplateExport($portfolio), $fileName);
    }

    // معاينة للعناوين والحقول حسب المحفظة
    public function getTemplatePreview(Portfolio $portfolio): array
    {
        $requiredHeaders = CustomerImportFields::requiredHeaders($portfolio->has_split_names);
        $optionalHeaders = CustomerImportFields::optionalHeaders($portfolio->has_split_names);

        $dynamicFields = $portfolio->portfolioFields->map(function ($field) {
            return $field->field_name . ($field->is_required ? ' *' : '');
        })->toArray();

        return [
            'portfolio_name'    => $portfolio->name,
            'company_name'      => $portfolio->company->name ?? '',
            'has_split_names'   => $portfolio->has_split_names,
            'required_headers'  => $requiredHeaders,
            'optional_headers'  => $optionalHeaders,
            'dynamic_fields'    => $dynamicFields,
            'total_fields'      => count(array_merge($requiredHeaders, $optionalHeaders, $dynamicFields))
        ];
    }

    // تحليل الملف المرفوع قبل بدء عملية الاستيراد
    public function analyzeImportFile(UploadedFile $file, int $portfolioId): array
    {
        try {
            $tempPath   = $this->storeTemporaryFile($file);
            $loadResult = $this->loadFileData($tempPath, $portfolioId);

            if (!$loadResult['success']) {
                Storage::disk('tenant_public')->delete($tempPath);
                return $loadResult;
            }

            $data      = $loadResult['data'];
            $headers   = $loadResult['headers'];
            $portfolio = $loadResult['portfolio'];

            $headerAnalysis = $this->analyzeHeaders($headers, $portfolio, false);

            return [
                'success' => true,
                'data' => [
                    'total_rows'       => count($data),
                    'total_columns'    => count($headers),
                    'has_split_names'  => $portfolio->has_split_names,
                    'required_headers' => CustomerImportFields::requiredHeaders($portfolio->has_split_names),
                    'optional_headers' => CustomerImportFields::optionalHeaders($portfolio->has_split_names),
                    'missing_required' => $headerAnalysis['missing_required_fields'],
                    'header_analysis'  => $headerAnalysis,
                    'temp_path'        => $tempPath
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error analyzing import file: ' . $e->getMessage(), ['exception' => $e]);
            if (isset($tempPath)) {
                Storage::disk('tenant_public')->delete($tempPath);
            }
            return ['success' => false, 'message' => 'حدث خطأ أثناء تحليل الملف: ' . $e->getMessage()];
        }
    }

    // بدء عملية الاستيراد
    public function startImport(string $tempPath, int $portfolioId, int $userId): array
    {
        try {

            $loadResult = $this->loadFileData($tempPath, $portfolioId);

            if (!$loadResult['success']) {
                Storage::disk('tenant_public')->delete($tempPath);
                return $loadResult;
            }

            $data       = $loadResult['data'];
            $headers    = $loadResult['headers'];
            $portfolio  = $loadResult['portfolio'];

            //  تحليل العناوين (مع إنشاء حقول جديدة)
            $headerAnalysis = $this->analyzeHeaders($headers, $portfolio, true);

            $fieldMapping   = $headerAnalysis['field_mapping'];

            //  تحويل الحقول الديناميكية إلى مصفوفة بسيطة (id فقط)
            foreach ($fieldMapping as &$map) {
                if (($map['type'] ?? null) === 'dynamic' && is_object($map['field'])) {
                    $map['field'] = ['id' => $map['field']->id];
                }
            }
            unset($map);

            //  إنشاء sessionId قبل الـdispatch
            $sessionId = 'import_' . now()->format('Ymd_His') . '_' . Str::random(8);

            //  إنشاء سجل الجلسة
            $importSession = ImportSession::create([
                'session_id'        => $sessionId,
                'user_id'           => $userId,
                'portfolio_id'      => $portfolioId,
                'total_rows'        => count($data),
                'processed_rows'    => 0,
                'successful_rows'   => 0,
                'failed_rows'       => 0,
                'status'            => 'queued',
                'started_at'        => now(),
                'temp_file_path'    => $tempPath,
                'errors'            => json_encode([]),
            ]);

            $chunkSize   = config('import.chunk_size', 200);
            $chunks      = array_chunk($data, $chunkSize);
            $totalChunks = count($chunks);

            foreach ($chunks as $index => $chunk) {
                ProcessCustomerImportJob::dispatch(
                    $sessionId,
                    $chunk,
                    $headers,
                    $fieldMapping,
                    $portfolio->id,
                    $index,
                    $totalChunks,
                    auth()->id()
                )->onQueue('imports');
            }

            $importSession->update(['status' => 'processing']);

            return [
                'success'       => true,
                'session_id'    => $sessionId,
                'total_rows'    => count($data),
                'total_chunks'  => $totalChunks,
                'chunk_size'    => $chunkSize,
                'use_polling'   => true,
            ];
        } catch (\Exception $e) {
            if (isset($tempPath)) {
                Storage::disk('tenant_public')->delete($tempPath);
            }
            return ['success' => false, 'message' => 'حدث خطأ أثناء بدء الاستيراد: ' . $e->getMessage()];
        }
    }



    /*
    |--------------------------------------------------------------------------
    | Get Import Status
    |--------------------------------------------------------------------------
    | Retrieve the current status of an import session.
    */
    public function getImportStatus(string $sessionId, int $userId): array
    {
        $session = ImportSession::where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->first();

        if (!$session) {
            return ['found' => false, 'message' => 'جلسة الاستيراد غير موجودة'];
        }

        $progressPercentage = $session->total_rows > 0
            ? round(($session->processed_rows / $session->total_rows) * 100, 1)
            : 0;

        return [
            'found' => true,
            'data' => [
                'session_id' => $session->session_id,
                'status' => $session->status,
                'total_rows' => $session->total_rows,
                'processed_rows' => $session->processed_rows,
                'successful_rows' => $session->successful_rows,
                'failed_rows' => $session->failed_rows,
                'progress_percentage' => $progressPercentage,
                'started_at' => $session->started_at->toISOString(),
                'completed_at' => $session->completed_at ? $session->completed_at->toISOString() : null,
                'errors' => json_decode($session->errors, true) ?? []
            ]
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Cancel Import
    |--------------------------------------------------------------------------
    | Cancel an ongoing import process.
    */
    public function cancelImport(string $sessionId, int $userId): array
    {
        $session = ImportSession::where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->first();

        if (!$session) {
            return ['success' => false, 'message' => 'جلسة الاستيراد غير موجودة'];
        }

        $session->update([
            'status' => 'cancelled',
            'completed_at' => now()
        ]);

        return ['success' => true, 'message' => 'تم إلغاء عملية الاستيراد'];
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Error Report
    |--------------------------------------------------------------------------
    | Create and download an Excel report of import errors.
    */
    public function generateErrorReport(string $sessionId, int $userId)
    {
        $session = ImportSession::where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->first();

        if (!$session || !$session->errors) {
            abort(404, 'تقرير الأخطاء غير موجود');
        }

        $errors = json_decode($session->errors, true);
        if (!$errors) {
            abort(404, 'تقرير الأخطاء غير موجود');
        }

        $fileName = 'customer_import_errors_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        return Excel::download(new CustomerImportErrorExport($errors), $fileName);
    }



    /*
    |============================================================================
    |============================================================================
    |                         Private Methods
    |============================================================================
    |============================================================================
    */
    private function storeTemporaryFile(UploadedFile $file): string
    {
        $tempFileName = 'import_' . uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs('temp/imports', $tempFileName, 'tenant_public');
    }


    private function loadFileData(string $tempPath, int $portfolioId): array
    {
        try {
            // تحميل الملف
            $fullPath = Storage::disk('tenant_public')->path($tempPath);

            if (!file_exists($fullPath)) {
                return ['success' => false, 'message' => 'الملف المؤقت غير موجود'];
            }

            // قراءة البيانات
            $spreadsheet    = IOFactory::load($fullPath);
            $worksheet      = $spreadsheet->getActiveSheet();
            $data           = $worksheet->toArray();

            // التحقق من صحة البيانات
            if (empty($data) || count($data) < 2) {
                return ['success' => false, 'message' => 'الملف فارغ أو لا يحتوي على بيانات صالحة'];
            }

            // فصل العناوين عن البيانات
            $headers = array_shift($data);

            $portfolio = Portfolio::with(['portfolioFields', 'company'])->findOrFail($portfolioId);

            return [
                'success'   => true,
                'data'      => $data,
                'headers'   => $headers,
                'portfolio' => $portfolio
            ];
        } catch (\Exception $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء قراءة الملف: ' . $e->getMessage()
            ];
        }
    }


    private function analyzeHeaders(array $headers, Portfolio $portfolio, bool $createIfMissing = true): array
    {
        $existingDynamicFields = $portfolio->portfolioFields()->select('id', 'field_name', 'field_type', 'is_required', 'portfolio_id')->get()
            ->mapWithKeys(function ($field) {
                return [$this->fieldMappingService->normalizeHeader($field->field_name) => $field];
            });

        $fieldMapping       = [];
        $newFieldsCreated   = [];

        foreach ($headers as $index => $header) {
            $cleanHeader = trim($header);

            if (empty($cleanHeader) || $this->fieldMappingService->isInvalidHeader($cleanHeader)) {
                continue;
            }

            $normalizedHeader = $this->fieldMappingService->normalizeHeader($cleanHeader);

            // البحث عن الحقول الأساسية
            $coreFieldMatch = $this->fieldMappingService->findMatchingCoreField($cleanHeader);

            if ($coreFieldMatch) {
                $fieldMapping[$index] = [
                    'type'          => 'static',
                    'field'         => $coreFieldMatch['field'],
                    'category'      => 'core',
                    'display_name'  => $cleanHeader
                ];

                if (isset($coreFieldMatch['source_field'])) {
                    $fieldMapping[$index]['source_field'] = $coreFieldMatch['source_field'];
                }

                continue;
            }

            // حقول الهاتف الإضافية
            $phoneFieldInfo = $this->fieldMappingService->parsePhoneField($cleanHeader);
            if ($phoneFieldInfo) {
                $fieldMapping[$index] = [
                    'type'          => 'phone',
                    'field'         => $phoneFieldInfo,
                    'category'      => 'phone',
                    'display_name'  => $cleanHeader
                ];

                continue;
            }

            // الحقول الديناميكية الموجودة
            if ($existingDynamicFields->has($normalizedHeader)) {
                $dynamicField = $existingDynamicFields[$normalizedHeader];

                if (!$dynamicField || !$dynamicField->id) {
                    continue;
                }

                $fieldMapping[$index] = [
                    'type'          => 'dynamic',
                    'field'         => $dynamicField,
                    'category'      => 'existing_dynamic',
                    'display_name'  => $cleanHeader
                ];

                continue;
            }

            // إنشاء حقل ديناميكي جديد أو اقتراحه
            if ($this->fieldMappingService->shouldCreateNewField($cleanHeader)) {
                if ($createIfMissing) {
                    try {
                        $fieldType = $this->fieldMappingService->detectFieldType($cleanHeader);
                        $existingInDb = PortfolioField::where('portfolio_id', $portfolio->id)->where('field_name', $cleanHeader)->first();

                        if ($existingInDb) {
                            $newField = $existingInDb;
                        } else {
                            $newField = PortfolioField::create([
                                'portfolio_id'  => $portfolio->id,
                                'field_name'    => $cleanHeader,
                                'field_type'    => $fieldType,
                                'is_required'   => false,
                                'created_by'    => auth()->id() ?? 1
                            ]);
                        }

                        $newFieldsCreated[] = $newField;

                        $fieldMapping[$index] = [
                            'type'          => 'dynamic',
                            'field'         => $newField,
                            'category'      => 'new_dynamic',
                            'display_name'  => $cleanHeader
                        ];
                    } catch (\Exception $e) {
                    }
                } else {
                    $fieldType = $this->fieldMappingService->detectFieldType($cleanHeader);

                    $fieldMapping[$index] = [
                        'type'          => 'pending_dynamic',
                        'field'         => $cleanHeader,
                        'category'      => 'new_dynamic',
                        'display_name'  => $cleanHeader
                    ];

                    $newFieldsCreated[] = (object)[
                        'id' => null,
                        'field_name' => $cleanHeader,
                        'field_type' => $fieldType
                    ];
                }

                continue;
            }
        }

        //  تحقق من الحقول الإجبارية المفقودة
        $requiredHeaders = CustomerImportFields::requiredHeaders($portfolio->has_split_names);
        $missingRequired = [];

        foreach ($requiredHeaders as $requiredField) {
            $found = false;

            foreach ($fieldMapping as $mapping) {
                if (($mapping['type'] === 'static' || $mapping['type'] === 'core')
                    && $mapping['display_name'] === $requiredField
                ) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $missingRequired[] = $requiredField;
            }
        }

        return [
            'field_mapping'             => $fieldMapping,
            'missing_required_fields'   => $missingRequired,
            'new_fields_created_info'   => collect($newFieldsCreated)->map(function ($f) {
                return [
                    'id'    => is_object($f) && isset($f->id) ? $f->id : ($f->id ?? null),
                    'name'  => is_object($f) && isset($f->field_name) ? $f->field_name : ($f->field_name ?? $f->name ?? null),
                    'type'  => is_object($f) && isset($f->field_type) ? $f->field_type : ($f->field_type ?? null),
                ];
            })->toArray()
        ];
    }
}
