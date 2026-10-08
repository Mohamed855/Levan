<?php

namespace App\Http\Requests\Tenants\UserManagement\Team;

use App\Models\Tenants\UserMangment\User\User;
use App\Http\Requests\Concerns\WizardFormRequest;
use Illuminate\Validation\Validator;

class StoreTeamRequest extends WizardFormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */
    public function authorize(): bool
    {
        return $this->user('tenant')?->can('teams.manage') ?? false;
    }

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'members' => ['required', 'array', 'min:2'],
            'members.*' => ['integer', 'distinct', 'exists:users,id'],
            'leaders' => ['required', 'array', 'size:1'],
            'leaders.*' => ['integer', 'distinct', 'exists:users,id'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Messages
    |--------------------------------------------------------------------------
    */
    public function messages(): array
    {
        return [
            'name.required' => 'اسم المجموعة مطلوب',
            'name.max' => 'اسم المجموعة يجب ألا يتجاوز 255 حرف',
            'members.required' => 'يجب إضافة عضوين على الأقل إلى المجموعة',
            'members.min' => 'يجب إضافة عضوين على الأقل إلى المجموعة',
            'members.*.exists' => 'أحد الأعضاء المحددين غير موجود',
            'leaders.required' => 'يجب تحديد قائد للمجموعة',
            'leaders.size' => 'يجب تحديد قائد واحد للمجموعة',
            'leaders.*.exists' => 'أحد القادة المحددين غير موجود',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم المجموعة',
            'description' => 'وصف المجموعة',
            'members' => 'الأعضاء',
            'members.*' => 'العضو',
            'leaders' => 'قائد المجموعة',
            'leaders.*' => 'قائد المجموعة',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Prepare For Validation
    |--------------------------------------------------------------------------
    */
    protected function prepareForValidation(): void
    {
        if ($this->has('leaders') && $this->has('members')) {
            $leaders = $this->leaders ?? [];
            $members = $this->members ?? [];

            $this->merge([
                'members' => array_unique(array_merge($members, $leaders))
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Validation
    |--------------------------------------------------------------------------
    */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('members')) {
                $members = $this->input('members', []);
                $leaders = $this->input('leaders', []);

                if (array_diff($leaders, $members) !== []) {
                    $validator->errors()->add('leaders', 'يجب أن يكون قائد المجموعة ضمن الأعضاء المحددين');
                }

                $usersWithTeams = User::whereIn('id', $members)
                    ->whereNotNull('team_id')
                    ->pluck('name')
                    ->toArray();

                if (!empty($usersWithTeams)) {
                    $validator->errors()->add(
                        'members',
                        'بعض المستخدمين مرتبطون بالفعل بفرق أخرى: ' . implode(', ', $usersWithTeams)
                    );
                }
            }
        });
    }
}
