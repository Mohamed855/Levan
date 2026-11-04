<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\ActivityLog;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class DownloadActivityLogExportRequest extends ExportActivityLogsRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['file' => $this->route('file')]);
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'string', 'regex:/^activity[-_]logs[-_][0-9]{4,8}[-_]?[0-9]{0,6}\.xlsx$/'],
        ];
    }

    public function attributes(): array
    {
        return ['file' => 'ملف التصدير'];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $file = (string) $this->route('file');
            $fileName = basename($file);
            $disk = Storage::disk('tenant_public');

            $isArchive = $disk->exists('activity-logs/' . $fileName);
            $isExport = $disk->exists('exports/' . $fileName);

            if (!$isArchive && !$isExport) {
                $validator->errors()->add('file', 'ملف التصدير غير متاح أو تم حذفه.');
                return;
            }

            if ($isArchive) {
                if (! $this->user('tenant')?->can('activity_log.view')) {
                    $validator->errors()->add('file', 'غير مصرح بتنزيل ملف الأرشيف.');
                }
                return;
            }

            $owner = Cache::get(self::ownerKey($fileName));
            if (! $owner || (int) $owner !== (int) $this->user('tenant')?->getKey()) {
                $validator->errors()->add('file', 'ملف التصدير غير متاح لهذا المستخدم.');
            }
        });
    }

    public static function ownerKey(string $file): string
    {
        return 'activity_log_export_owner:'.hash('sha256', basename($file));
    }
}
