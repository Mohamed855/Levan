<?php

declare(strict_types=1);

namespace App\Actions\Admin\AuctionTemplates;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\AuctionTemplates\AuctionTemplate;
use App\Models\Admin\AuctionTemplates\AuctionTemplateVersion;
use App\Services\Admin\AuctionTemplates\AuctionDesignAssetService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class UpdateAuctionTemplateAction
{
    /** @param array<string, mixed> $data */
    public function execute(
        AuctionTemplate $template,
        AdminUser|array $userOrData,
        mixed $dataOrFile = null,
        ?UploadedFile $file = null
    ): AuctionTemplate {
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

        return DB::transaction(function () use ($template, $user, $data, $file): AuctionTemplate {
            $type = isset($data['type'])
                ? (is_object($data['type']) ? $data['type']->value : (string) $data['type'])
                : (is_object($template->type) ? $template->type->value : (string) $template->type);

            $updateData = [
                'name' => $data['name'] ?? $template->name,
                'description' => array_key_exists('description', $data) ? $data['description'] : $template->description,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $template->is_active,
            ];

            if ($file instanceof UploadedFile) {
                $filePath = $file->store("auction_templates/{$type}", 'central_public');
                if (Schema::hasColumn('auction_templates', 'file_path')) {
                    $updateData['file_path'] = $filePath;
                }
            }

            $template->update($updateData);

            if (array_key_exists('schema', $data)) {
                $schema = $data['schema'];
                if (is_string($schema)) {
                    $schema = json_decode($schema, true);
                }

                $latestVersion = (int) $template->versions()->max('version');
                $versionPrefix = "auction_templates/{$template->id}/versions/".($latestVersion + 1);
                $uploads = (array) ($data['page_backgrounds'] ?? []);
                if ($uploads !== []) {
                    $schema = app(AuctionDesignAssetService::class)->finalizeSchema($schema, $uploads, $versionPrefix);
                }
                AuctionTemplateVersion::query()->create([
                    'auction_template_id' => $template->id,
                    'version' => $latestVersion + 1,
                    'schema' => $schema,
                    'status' => 'draft',
                    'created_by' => $user?->id ?? (int) auth('admin')->id(),
                ]);
                $template->update(['status' => 'draft']);
            }

            return $template->refresh()->load('versions');
        });
    }
}
