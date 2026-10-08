<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\FinancialCollection\Import;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Cache;

class DownloadCollectionImportErrorReportRequest extends CollectionImportRequest
{
    public function rules(): array
    {
        return [
            'report_path' => ['required', 'string', 'regex:/^reports\/error_report_[0-9]{14}\.xlsx$/'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $path = (string) $this->input('report_path');
            $owner = Cache::get(self::errorReportOwnerKey($path));

            if (! $owner || (int) $owner !== (int) $this->user('tenant')?->getKey()) {
                $validator->errors()->add('report_path', 'تقرير الأخطاء غير متاح لهذا المستخدم.');
            }
        });
    }
}
