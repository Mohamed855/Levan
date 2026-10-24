<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Bills;

use Illuminate\Foundation\Http\FormRequest;

final class RejectBankTransferRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('admin')?->can('bills.update') ?? false; }

    public function rules(): array { return ['reason' => ['required', 'string', 'min:3', 'max:1000']]; }

    public function attributes(): array { return ['reason' => 'سبب الرفض']; }
}
