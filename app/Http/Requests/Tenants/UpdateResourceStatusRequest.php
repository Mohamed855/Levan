<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenants;

use Illuminate\Foundation\Http\FormRequest;

class UpdateResourceStatusRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('status')) {
            return;
        }

        $action = $this->route()?->getActionName() ?? '';
        $contract = $this->route('contract');

        if (str_contains($action, 'ContractController@') && $contract) {
            $this->merge([
                'status' => $contract->status === 'active' ? 'inactive' : 'active',
            ]);
        }
    }

    public function authorize(): bool
    {
        $action = (string) $this->route()?->getActionName();
        $permission = match (true) {
            str_contains($action, 'CompanyController@') => 'companies.edit',
            str_contains($action, 'ContractController@') => 'contracts.edit',
            str_contains($action, 'PortfolioController@') => 'portfolios.edit',
            str_contains($action, 'AssignmentsController@') => 'assignments.edit',
            str_contains($action, 'PropertiesController@') => 'properties.edit',
            str_contains($action, 'AuctionsController@') => 'auctions.edit',
            default => null,
        };

        return $permission !== null && ($this->user('tenant')?->can($permission) ?? false);
    }

    public function rules(): array
    {
        return ['status' => ['required', 'in:active,inactive']];
    }
}
