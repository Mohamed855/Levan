<?php

namespace App\Services\Tenants\BusinessCenter\Customer\Import;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\UserMangment\User\User;

class __CustomerImportValidationService
{
    /*
    |--------------------------------------------------------------------------
    | Validate Identity Number
    |--------------------------------------------------------------------------
    | Validate Saudi identity number format.
    */
    public function validateIdentityNumber(string $identityNumber): array
    {
        $errors = [];
        $cleanIdentity = preg_replace('/[^0-9]/', '', $identityNumber);

        if (strlen($cleanIdentity) !== 10) {
            $errors[] = 'رقم الهوية يجب أن يكون 10 أرقام';
            return ['valid' => false, 'errors' => $errors];
        }

        $firstDigit = substr($cleanIdentity, 0, 1);
        if (!in_array($firstDigit, ['1', '2', '7'])) {
            $errors[] = 'رقم الهوية يجب أن يبدأ بـ 1 أو 2 أو 7';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'clean_value' => $cleanIdentity
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Phone Number
    |--------------------------------------------------------------------------
    | Validate Saudi phone number format.
    */
    public function validatePhoneNumber(string $phone): array
    {
        $errors = [];
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);

        $patterns = [
            '/^966[0-9]{9}$/',      // 966xxxxxxxxx
            '/^\+966[0-9]{9}$/',    // +966xxxxxxxxx
            '/^05[0-9]{8}$/',       // 05xxxxxxxx
            '/^5[0-9]{8}$/'        // 5xxxxxxxx
        ];

        $isValid = false;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $cleanPhone)) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid) {
            $errors[] = 'تنسيق رقم الهاتف غير صحيح';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'normalized_value' => $cleanPhone
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Customer Type
    |--------------------------------------------------------------------------
    | Validate customer type (individual/company).
    */
    public function validateCustomerType(string $value): array
    {
        $validTypes = ['individual', 'company'];
        $normalizedValue = strtolower(trim($value));

        $typeMapping = [
            'فردي' => 'individual',
            'فرد' => 'individual',
            'شخص' => 'individual',
            'مؤسسة' => 'company',
            'شركة' => 'company'
        ];

        foreach ($typeMapping as $arabic => $english) {
            if ($normalizedValue === $arabic) {
                return ['valid' => true, 'value' => $english];
            }
        }

        if (in_array($normalizedValue, $validTypes)) {
            return ['valid' => true, 'value' => $normalizedValue];
        }

        return [
            'valid' => false,
            'errors' => ['نوع العميل غير صالح']
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Employee Number
    |--------------------------------------------------------------------------
    | Validate employee number and find user.
    */
    public function validateEmployeeNumber(string $employeeNumber): array
    {
        $cleanEmployeeNumber = strtoupper(trim($employeeNumber));

        $user = User::where('employee_number', $cleanEmployeeNumber)
            ->where('status', 'active')
            ->first();

        if (!$user) {
            return [
                'valid' => false,
                'errors' => ["لا يوجد مستخدم نشط برقم وظيفي: {$cleanEmployeeNumber}"]
            ];
        }

        return [
            'valid' => true,
            'value' => $user->id
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Row Data
    |--------------------------------------------------------------------------
    | Validate a single row of customer data.
    */
    public function validateRowData(array $row, array $headers, array $fieldMapping, int $rowNumber, bool $hasSplitNames): array
    {
        $errors = [];
        $processedData = [];
        $supportsSplitNames = $hasSplitNames;

        foreach ($fieldMapping as $index => $mapping) {
            $value = trim($row[$index] ?? '');
            if ($value === '') continue;

            $fieldName = $mapping['field'] ?? 'unknown';
            $fieldType = $mapping['type'] ?? 'unknown';

            if ($fieldType === 'static') {
                $validationResult = $this->validateStaticField($fieldName, $value);
                if (!$validationResult['valid']) {
                    $errors = array_merge($errors, $validationResult['errors']);
                } else {
                    $processedData[$fieldName] = $validationResult['value'];
                }
            }
        }

        // Validate required fields
        if ($supportsSplitNames) {
            if (empty($processedData['first_name'])) {
                $errors[] = 'الاسم الأول مطلوب';
            }
            if (empty($processedData['family_name'])) {
                $errors[] = 'اسم العائلة مطلوب';
            }
        } else {
            if (empty($processedData['name'])) {
                $errors[] = 'اسم العميل مطلوب';
            }
        }

        if (empty($processedData['customer_type'])) {
            $errors[] = 'نوع العميل مطلوب';
        }

        if (empty($processedData['identity_number'])) {
            $errors[] = 'رقم الهوية مطلوب';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'processed_data' => $processedData
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Static Field
    |--------------------------------------------------------------------------
    | Validate a specific static field.
    */
    private function validateStaticField(string $fieldName, $value): array
    {
        switch ($fieldName) {
            case 'customer_type':
                return $this->validateCustomerType($value);
            case 'identity_number':
                return $this->validateIdentityNumber($value);
            case 'phone':
                return $this->validatePhoneNumber($value);
            case 'relationship_manager_id':
                if ($this->isEmployeeNumberField($fieldName)) {
                    return $this->validateEmployeeNumber($value);
                }
                return ['valid' => true, 'value' => $value];
            default:
                return ['valid' => true, 'value' => $value];
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Check if field is employee number
    |--------------------------------------------------------------------------
    */
    private function isEmployeeNumberField(string $fieldName): bool
    {
        $employeeNumberPatterns = [
            'employee_number',
            'رقم الموظف',
            'الرقم الوظيفي',
            'رقم وظيفي'
        ];

        $normalizedField = strtolower(trim($fieldName));
        foreach ($employeeNumberPatterns as $pattern) {
            if (strpos($normalizedField, strtolower($pattern)) !== false) {
                return true;
            }
        }
        return false;
    }
}
