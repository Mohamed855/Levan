<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Faqs;

use App\Http\Requests\Admin\FaqRequest;

final class StoreFaqRequest extends FaqRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('faqs.create') ?? false;
    }
}
