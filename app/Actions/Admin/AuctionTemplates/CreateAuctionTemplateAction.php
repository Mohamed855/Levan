<?php

declare(strict_types=1);

namespace App\Actions\Admin\AuctionTemplates;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\AuctionTemplates\AuctionTemplate;
use App\Models\Admin\AuctionTemplates\AuctionTemplateVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateAuctionTemplateAction
{
    /** @param array<string, mixed> $data */
    public function execute(AdminUser|array $userOrData, mixed $dataOrFile = null, ?UploadedFile $file = null): AuctionTemplate
    {
        if ($userOrData instanceof AdminUser) {
            $user = $userOrData;
            $data = (array) $dataOrFile;
        } else {
            $data = $userOrData;
            $user = auth('admin')->user() ?? AdminUser::find($data['created_by'] ?? null) ?? AdminUser::first();
            if ($dataOrFile instanceof UploadedFile) {
                $file = $dataOrFile;
            }
        }

        return DB::transaction(function () use ($user, $data, $file): AuctionTemplate {
            $type = is_object($data['type']) ? $data['type']->value : (string) $data['type'];

            $filePath = null;
            $extractedFields = [];
            if ($file instanceof UploadedFile) {
                $filePath = $file->store("auction_templates/{$type}", 'central_public');
                $absolutePath = \Illuminate\Support\Facades\Storage::disk('central_public')->path($filePath);
                $extractedFields = $this->extractFieldNames($absolutePath);

                if (empty($extractedFields) && empty($data['schema']) && empty($data['field_definitions'])) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'file' => 'يجب أن يحتوي ملف PDF على حقول مسماة قابلة للتعبئة قبل استخدامه كقالب مزاد.',
                    ]);
                }
            }

            $templateData = [
                'name' => $data['name'],
                'type' => $type,
                'description' => $data['description'] ?? null,
                'status' => 'draft',
                'is_active' => (bool) ($data['is_active'] ?? true),
                'created_by' => $user?->id ?? (int) auth('admin')->id(),
            ];

            if (\Illuminate\Support\Facades\Schema::hasColumn('auction_templates', 'file_path')) {
                $templateData['file_path'] = $filePath ?? ($data['file_path'] ?? null);
            }

            if (! empty($extractedFields) && \Illuminate\Support\Facades\Schema::hasColumn('auction_templates', 'field_names')) {
                $templateData['field_names'] = $extractedFields;
            }

            $template = AuctionTemplate::query()->create($templateData);

            $schema = $data['schema'] ?? null;
            if (is_string($schema)) {
                $schema = json_decode($schema, true);
            }
            if (empty($schema)) {
                $schema = [
                    'canvas' => ['width' => $type === 'billboard' ? 1200 : 794, 'height' => $type === 'billboard' ? 628 : 1123],
                    'pages' => [
                        [
                            'id' => (string) Str::uuid(),
                            'background' => ['color' => '#ffffff', 'image' => null],
                            'elements' => [],
                        ],
                    ],
                ];
            }

            AuctionTemplateVersion::query()->create([
                'auction_template_id' => $template->id,
                'version' => 1,
                'schema' => $schema,
                'status' => 'draft',
                'created_by' => $user?->id ?? (int) auth('admin')->id(),
            ]);

            return $template->load('versions');
        });
    }

    /**
     * Extract field names from PDF.
     *
     * @return array<int, string>
     */
    public function extractFieldNames(string $absolutePath): array
    {
        $fields = [];
        if (file_exists($absolutePath)) {
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
