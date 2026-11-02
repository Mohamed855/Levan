<?php

namespace Tests\Unit;

use App\Http\Requests\Admin\AdminMangment\Users\AdminUserStoreRequest;
use App\Http\Requests\Admin\AdminMangment\Users\AdminUserUpdateRequest;
use App\Http\Requests\Admin\Clients\StoreClientRequest;
use App\Http\Requests\Admin\SubscriptionManagement\FinalizeCompanySubscriptionRequest;
use App\Http\Requests\Admin\SubscriptionManagement\StoreSubscriptionRequest;
use App\Http\Requests\Site\SubscriptionCheckoutRequest;
use App\Http\Requests\Tenants\Auth\ChangeTenantPasswordRequest;
use App\Http\Requests\Tenants\UserManagement\User\StoreUserRequest;
use App\Http\Requests\Tenants\UserManagement\User\UpdateUserRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

class PasswordComplexityPolicyTest extends TestCase
{
    private const POLICY_MESSAGE = 'يجب أن تتكون كلمة المرور من 8 أحرف على الأقل، وتحتوي على حرف إنجليزي كبير ورقم ورمز خاص.';

    public function test_shared_password_policy_requires_an_uppercase_letter_a_number_and_a_symbol(): void
    {
        $rules = ['password' => ['required', Password::defaults()]];

        $validPassword = Validator::make(['password' => 'Password1!'], $rules);
        $missingUppercase = Validator::make(['password' => 'password1!'], $rules);
        $missingNumber = Validator::make(['password' => 'Password!'], $rules);
        $missingSymbol = Validator::make(['password' => 'Password1'], $rules);

        self::assertTrue($validPassword->passes());
        self::assertTrue($missingUppercase->fails());
        self::assertTrue($missingNumber->fails());
        self::assertTrue($missingSymbol->fails());
    }

    public function test_shared_password_policy_explains_the_required_format_in_one_simple_message(): void
    {
        $rules = ['password' => ['required', Password::defaults()]];

        foreach (['password1!', 'Password!', 'Password1'] as $password) {
            $errors = Validator::make(['password' => $password], $rules)->errors()->all();

            self::assertSame([self::POLICY_MESSAGE], $errors);
        }
    }

    public function test_every_password_mutation_request_rejects_a_password_without_an_uppercase_letter(): void
    {
        foreach ($this->passwordMutationRequests() as $requestClass => $field) {
            $request = $requestClass::create('/', 'POST');
            $validator = Validator::make(
                [$field => 'password1!', "{$field}_confirmation" => 'password1!'],
                [$field => $request->rules()[$field]],
            );

            self::assertTrue($validator->fails(), "{$requestClass} must enforce the shared password policy.");
        }
    }

    public function test_optional_password_update_fields_remain_valid_when_omitted(): void
    {
        foreach ([AdminUserUpdateRequest::class, UpdateUserRequest::class] as $requestClass) {
            $request = $requestClass::create('/', 'PUT');
            $rules = $request->rules();

            self::assertTrue(Validator::make([], ['password' => $rules['password']])->passes());
        }
    }

    /**
     * @return array<class-string, string>
     */
    private function passwordMutationRequests(): array
    {
        return [
            AdminUserStoreRequest::class => 'password',
            AdminUserUpdateRequest::class => 'password',
            StoreClientRequest::class => 'password',
            StoreSubscriptionRequest::class => 'password',
            FinalizeCompanySubscriptionRequest::class => 'password',
            SubscriptionCheckoutRequest::class => 'billing_password',
            StoreUserRequest::class => 'password',
            UpdateUserRequest::class => 'password',
            ChangeTenantPasswordRequest::class => 'new_password',
        ];
    }
}
