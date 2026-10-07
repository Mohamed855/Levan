<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants\FinancialCollection\Import;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Cache;

abstract class CollectionImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('collections.create') ?? false;
    }

    protected function ownsTemporaryFile(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $path = (string) $this->input('temp_path');
            $owner = Cache::get(self::temporaryFileOwnerKey($path));
            if (! $owner || (int) $owner !== (int) $this->user('tenant')?->getKey()) {
                $validator->errors()->add('temp_path', 'الملف المؤقت غير متاح لهذا المستخدم.');
            }
        });
    }

    public static function temporaryFileOwnerKey(string $path): string
    {
        return 'collection_import_file_owner:' . hash('sha256', $path);
    }
}
