<?php

namespace App\Http\Requests\Tenants\UserManagement\Team;

use App\Models\Tenants\UserMangment\User\User;
use App\Http\Requests\Concerns\WizardFormRequest;

class StoreTeamRequest extends WizardFormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */
    public function authorize(): bool
    {
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    */
    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'members' => ['nullable', 'array'],
            'members.*' => ['exists:users,id'],
            'leaders' => ['nullable', 'array'],
            'leaders.*' => ['exists:users,id'],
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
            'branch_id.required' => 'يرجى اختيار الفرع',
            'branch_id.exists' => 'الفرع المحدد غير موجود',
            'name.required' => 'اسم المجموعة مطلوب',
            'name.max' => 'اسم المجموعة يجب ألا يتجاوز 255 حرف',
            'members.*.exists' => 'أحد الأعضاء المحددين غير موجود',
            'leaders.*.exists' => 'أحد القادة المحددين غير موجود',
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
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('members')) {
                $members = $this->input('members', []);

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
