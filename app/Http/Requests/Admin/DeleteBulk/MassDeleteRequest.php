<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\DeleteBulk;

use App\Http\Controllers\Admin\DeleteBulk\AdminMassDeleteController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MassDeleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'model' => ['required', 'string', Rule::in(array_keys(AdminMassDeleteController::ALLOWED_MODELS))],
            'ids' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'model.required' => 'نوع السجل مطلوب.',
            'model.in' => 'نوع السجل غير مدعوم للحذف الجماعي.',
            'ids.required' => 'حدد سجلاً واحداً على الأقل.',
        ];
    }
}
