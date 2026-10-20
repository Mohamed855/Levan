<?php

declare(strict_types=1);

namespace App\Actions\Admin\AuctionTemplates;

use App\Models\Admin\AuctionTemplates\AuctionTemplate;
use App\Services\Admin\AuctionTemplates\AuctionTemplateFieldDefinitionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use mikehaertl\pdftk\Pdf;
use Throwable;

final class CreateAuctionTemplateAction
{
    public function __construct(
        private readonly AuctionTemplateFieldDefinitionService $fieldDefinitions,
    ) {}

    public function execute(array $data, UploadedFile $file): AuctionTemplate
    {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($data, $file, &$storedPaths): AuctionTemplate {
                $type = is_object($data['type']) ? $data['type']->value : (string) $data['type'];

                // 1. Store template file
                $filePath = $file->store("auction_templates/{$type}", 'central_public');
                $storedPaths[] = $filePath;
                $data['file_path'] = $filePath;

                $absolutePath = Storage::disk('central_public')->path($filePath);
                if ($this->isImage($file)) {
                    $definitions = $this->fieldDefinitions->normalize(json_decode((string) ($data['field_definitions'] ?? '[]'), true) ?: []);
                    $data['field_definitions'] = $definitions;
                    $data['field_names'] = array_column($definitions, 'key');
                } else {
                    $extracted = $this->extractFieldNames($absolutePath);
                    if (empty($extracted)) {
                        throw ValidationException::withMessages([
                            'file' => 'يجب أن يحتوي ملف PDF على حقول مسماة قابلة للتعبئة قبل استخدامه كقالب مزاد.',
                        ]);
                    }
                    $data['field_names'] = $extracted;
                    $data['field_definitions'] = null;
                }

                if (empty($data['field_names'])) {
                    throw ValidationException::withMessages([
                        'field_definitions' => 'يجب تحديد مواضع الحقول داخل التصميم.',
                    ]);
                }

                $data['created_by'] = auth('admin')->id();

                return AuctionTemplate::create($data);
            });
        } catch (Throwable $e) {
            foreach ($storedPaths as $path) {
                Storage::disk('central_public')->delete($path);
            }
            throw $e;
        }
    }

    /**
     * Extract field names using pdftk with fallback to PDF stream parsing.
     */
    public function extractFieldNames(string $absolutePath): array
    {
        $fields = [];

        // Try pdftk first
        try {
            $options = [];
            if ($pdftkPath = config('services.pdftk.path', env('PDFTK_PATH'))) {
                $options['command'] = $pdftkPath;
            }

            $pdf = new Pdf($absolutePath, $options);
            $dataFields = $pdf->getDataFields();

            if ($dataFields && is_iterable($dataFields)) {
                $fields = collect($dataFields)
                    ->pluck('FieldName')
                    ->filter()
                    ->values()
                    ->toArray();
            }
        } catch (Throwable $e) {
            Log::warning('pdftk getDataFields failed: '.$e->getMessage());
        }

        // Fallback: parse PDF form field keys /T (fieldName)
        if (empty($fields) && file_exists($absolutePath)) {
            $content = @file_get_contents($absolutePath);
            if ($content !== false && preg_match_all('/\/T\s*\(([^)]+)\)/', $content, $matches)) {
                $fields = collect($matches[1])
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();
            }
        }

        return $fields;
    }

    private function isImage(UploadedFile $file): bool
    {
        return str_starts_with((string) $file->getMimeType(), 'image/');
    }
}
