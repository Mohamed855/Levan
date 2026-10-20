<?php

declare(strict_types=1);

namespace App\Actions\Admin\AuctionTemplates;

use App\Models\Admin\AuctionTemplates\AuctionTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use mikehaertl\pdftk\Pdf;
use Throwable;

final class CreateAuctionTemplateAction
{
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

                // 2. Extract field names or default to standard fields catalog
                $absolutePath = Storage::disk('central_public')->path($filePath);
                $extracted = $this->extractFieldNames($absolutePath);
                if (! empty($extracted)) {
                    $data['field_names'] = $extracted;
                } else {
                    $guide = app(\App\Services\Admin\AuctionTemplates\AuctionTemplateService::class)->getFieldGuide();
                    $data['field_names'] = array_column($guide[$type]['fields'] ?? [], 'name');
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
            Log::warning('pdftk getDataFields failed: ' . $e->getMessage());
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
}
