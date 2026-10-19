<?php

declare(strict_types=1);

namespace App\Actions\Admin\AuctionTemplates;

use App\Models\Admin\AuctionTemplates\AuctionTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class UpdateAuctionTemplateAction
{
    public function __construct(
        private readonly CreateAuctionTemplateAction $createAction
    ) {}

    public function execute(
        AuctionTemplate $template,
        array $data,
        ?UploadedFile $file = null,
        ?UploadedFile $thumbnail = null
    ): AuctionTemplate {
        $oldFilePath = null;
        $oldThumbPath = null;
        $newStoredPaths = [];

        try {
            return DB::transaction(function () use ($template, $data, $file, $thumbnail, &$oldFilePath, &$oldThumbPath, &$newStoredPaths): AuctionTemplate {
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
                    $extracted = $this->createAction->extractFieldNames($absolutePath);
                    if (! empty($extracted)) {
                        $data['field_names'] = $extracted;
                    } else {
                        $guide = app(\App\Services\Admin\AuctionTemplates\AuctionTemplateService::class)->getFieldGuide();
                        $data['field_names'] = array_column($guide[$type]['fields'] ?? [], 'name');
                    }

                    if (! $thumbnail && in_array(strtolower($file->getClientOriginalExtension()), ['png', 'jpg', 'jpeg', 'webp'], true)) {
                        $data['thumbnail_path'] = $newFilePath;
                    }
                }

                // Handle new thumbnail
                if ($thumbnail instanceof UploadedFile) {
                    $newThumbPath = $thumbnail->store("auction_templates/{$type}/thumbnails", 'central_public');
                    $newStoredPaths[] = $newThumbPath;
                    $oldThumbPath = $template->thumbnail_path;
                    $data['thumbnail_path'] = $newThumbPath;
                }

                $template->update($data);

                // Clean up old files on success
                if ($oldFilePath && Storage::disk('central_public')->exists($oldFilePath)) {
                    Storage::disk('central_public')->delete($oldFilePath);
                }
                if ($oldThumbPath && Storage::disk('central_public')->exists($oldThumbPath)) {
                    Storage::disk('central_public')->delete($oldThumbPath);
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
