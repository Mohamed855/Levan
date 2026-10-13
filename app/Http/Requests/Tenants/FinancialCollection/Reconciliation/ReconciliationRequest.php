<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\FinancialCollection\Reconciliation;

use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Cache;

abstract class ReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('reconciliation.view') ?? false;
    }

    protected function portfolioBelongsToCompany(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->filled('company_id') || ! $this->filled('portfolio_id')) {
                return;
            }

            if (! Portfolio::query()->whereKey($this->integer('portfolio_id'))
                ->where('company_id', $this->integer('company_id'))->exists()) {
                $validator->errors()->add('portfolio_id', 'المحفظة المحددة لا تتبع الشركة المختارة.');
            }
        });
    }

    protected function ownsTemporaryFile(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $path = (string) $this->input('temp_file_path');
            $ownerId = Cache::get(self::temporaryFileOwnerKey($path));

            if (! $ownerId || (int) $ownerId !== (int) $this->user('tenant')?->getKey()) {
                $validator->errors()->add('temp_file_path', 'الملف المؤقت غير متاح لهذا المستخدم.');
            }
        });
    }

    public static function temporaryFileOwnerKey(string $path): string
    {
        return 'reconciliation_file_owner:'.hash('sha256', $path);
    }
}
