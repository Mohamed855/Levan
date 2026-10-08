<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\ContentManagement;

class UpdatePortfolioRequest extends StorePortfolioRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('clients.edit') ?? false;
    }
}
