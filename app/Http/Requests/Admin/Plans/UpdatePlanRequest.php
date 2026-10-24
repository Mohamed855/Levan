<?php
declare(strict_types=1);
namespace App\Http\Requests\Admin\Plans;
use Illuminate\Validation\Rule;
final class UpdatePlanRequest extends StorePlanRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->hasPermissionTo('plans.update') ?? false;
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['code'][3] = Rule::unique('plans', 'code')->ignore($this->route('plan'));

        return $rules;
    }
}
