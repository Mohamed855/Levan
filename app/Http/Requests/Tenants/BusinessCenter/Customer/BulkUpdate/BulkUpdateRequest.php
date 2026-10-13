<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\BulkUpdate;

use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Cache;

abstract class BulkUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('customers.update') ?? false;
    }

    protected function portfolioBelongsToCompany(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->filled('company_id') || ! $this->filled('portfolio_id')) {
                return;
            }

            $belongsToCompany = Portfolio::query()
                ->whereKey($this->integer('portfolio_id'))
                ->where('company_id', $this->integer('company_id'))
                ->exists();

            if (! $belongsToCompany) {
                $validator->errors()->add('portfolio_id', 'المحفظة المحددة لا تتبع الشركة المختارة.');
            }
        });
    }

    protected function ownsTemporaryFile(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $path = (string) $this->input('temp_file_path');
            $ownerId = Cache::get($this->temporaryFileOwnerKey($path));

            if (! $ownerId || (int) $ownerId !== (int) $this->user('tenant')?->getKey()) {
                $validator->errors()->add('temp_file_path', 'الملف المؤقت غير متاح لهذا المستخدم.');
            }
        });
    }

    protected function ownsErrorReport(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $path = (string) $this->input('path');
            $ownerId = Cache::get($this->errorReportOwnerKey($path));

            if (! $ownerId || (int) $ownerId !== (int) $this->user('tenant')?->getKey()) {
                $validator->errors()->add('path', 'تقرير الأخطاء غير متاح لهذا المستخدم.');
            }
        });
    }

    public static function temporaryFileOwnerKey(string $path): string
    {
        return 'bulk_update_file_owner:'.hash('sha256', $path);
    }

    public static function errorReportOwnerKey(string $path): string
    {
        return 'bulk_update_report_owner:'.hash('sha256', $path);
    }
}
