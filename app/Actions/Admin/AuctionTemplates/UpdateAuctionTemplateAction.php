<?php

declare(strict_types=1);

namespace App\Actions\Admin\AuctionTemplates;

use App\Models\Admin\AuctionTemplates\AuctionTemplate;
use App\Services\Admin\AuctionTemplates\AuctionTemplateFieldDefinitionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

final class UpdateAuctionTemplateAction
{
    public function __construct(
        private readonly CreateAuctionTemplateAction $createAction
    ) {}

    public function execute(
        AuctionTemplate $template,
        array $data,
        ?UploadedFile $file = null
    ): AuctionTemplate {
        $oldFilePath = null;
        $newStoredPaths = [];

        try {
            return DB::transaction(function () use ($template, $data, $file, &$oldFilePath, &$newStoredPaths): AuctionTemplate {
                $type = isset($data['type'])
                    ? (is_object($data['type']) ? $data['type']->value : (string) $data['type'])
                    : $template->type->value;

                // Handle new file
                if ($file instanceof UploadedFile) {
                    $newFilePath = $file->store("auction_templates/{$type}", 'central_public');
                    $newStoredPaths[] = $newFilePath;
                    $oldFilePath = $template->file_path;
                    $data['file_path'] = $newFilePath;

                    $absolutePath = Storage::disk('central_public')->path($newFilePath);
                    $definitions = json_decode((string) ($data['field_definitions'] ?? '[]'), true);
                    $definitions = app(AuctionTemplateFieldDefinitionService::class)->normalize(is_array($definitions) ? $definitions : []);
                    if ($definitions !== []) {
                        $data['field_definitions'] = $definitions;
                        $data['field_names'] = array_column($definitions, 'key');
                    } else {
                        $extracted = $this->createAction->extractFieldNames($absolutePath);
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
                }

                $template->update($data);

                // Clean up old file on success
                if ($oldFilePath && Storage::disk('central_public')->exists($oldFilePath)) {
                    Storage::disk('central_public')->delete($oldFilePath);
                }

                return $template->fresh();
            });
        } catch (Throwable $e) {
            foreach ($newStoredPaths as $path) {
                Storage::disk('central_public')->delete($path);
            }
            throw $e;
        }
    }
}
