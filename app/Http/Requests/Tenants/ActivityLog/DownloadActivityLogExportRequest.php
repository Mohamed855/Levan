<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\ActivityLog;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Cache;

class DownloadActivityLogExportRequest extends ExportActivityLogsRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['file' => $this->route('file')]);
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'string', 'regex:/^activity-logs-[0-9]{8}-[0-9]{6}\.xlsx$/'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $file = (string) $this->route('file');
            $owner = Cache::get(self::ownerKey($file));

            if (! $owner || (int) $owner !== (int) $this->user('tenant')?->getKey()) {
                $validator->errors()->add('file', 'ملف التصدير غير متاح لهذا المستخدم.');
            }
        });
    }

    public static function ownerKey(string $file): string
    {
        return 'activity_log_export_owner:' . hash('sha256', $file);
    }
}
