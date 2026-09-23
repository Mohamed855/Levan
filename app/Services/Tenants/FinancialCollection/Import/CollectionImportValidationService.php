<?php

namespace App\Services\Tenants\FinancialCollection\Import;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class CollectionImportValidationService
{
    protected array $customerCache = [];

    /*
    |--------------------------------------------------------------------------
    | Preload Cache
    |--------------------------------------------------------------------------
    */
    public function preloadCache(array $dataRows, array $importConfig): void
    {
        $accountNumbers = [];
        foreach ($dataRows as $rowIndex => $row) {
            if (!is_array($row)) {
                continue;
            }
            $accountNumber = trim($row[$importConfig['account_column']] ?? '');
            if (!empty($accountNumber)) {
                $accountNumbers[] = $accountNumber;
            }
        }

        $customers = Customer::whereIn('account_number', $accountNumbers)
            ->select('id', 'account_number', 'name', 'relationship_manager_id')
            ->get()
            ->keyBy('account_number');

        $this->customerCache = $customers->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Row Data
    |--------------------------------------------------------------------------
    */
    public function validateRowData(array $row, array $importConfig, int $rowNumber): array
    {
        $errors = [];
        $validatedData = [];

        $accountNumber = $this->validateAccountNumber($row, $importConfig, $errors);
        if ($accountNumber) {
            $customer = $this->validateCustomerExists($accountNumber, $errors, $importConfig);
            if ($customer) {
                $validatedData['customer_id'] = $customer['id'];

                if (!empty($importConfig['assigned_user_id'])) {
                    $validatedData['assigned_user_id'] = $importConfig['assigned_user_id'];
                } else {
                    $validatedData['assigned_user_id'] = $customer['relationship_manager_id'];
                }

                $amount = $this->validateAmount($row, $importConfig, $errors);
                if ($amount) {
                    $validatedData['collected_amount'] = $amount;
                }
            }
        }

        $date = $this->validateDate($row, $importConfig, $errors);
        if ($date) {
            $validatedData['collection_date'] = $date;
        }

        return [
            'success' => empty($errors),
            'errors' => $errors,
            'data' => $validatedData
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Account Number
    |--------------------------------------------------------------------------
    */
    protected function validateAccountNumber(array $row, array $importConfig, array &$errors): ?string
    {
        $accountNumber = trim($row[$importConfig['account_column']] ?? '');

        if (empty($accountNumber)) {
            $errors[] = 'رقم حساب العميل مطلوب';
            return null;
        }

        return $accountNumber;
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Customer Exists
    |--------------------------------------------------------------------------
    */
    protected function validateCustomerExists(string $accountNumber, array &$errors, array $importConfig): ?array
    {
        if (!isset($this->customerCache[$accountNumber])) {
            $errors[] = "العميل برقم الحساب {$accountNumber} غير موجود";
            return null;
        }

        $customer = $this->customerCache[$accountNumber];

        if (empty($importConfig['assigned_user_id'])) {
            if (empty($customer['relationship_manager_id'])) {
                $errors[] = "العميل برقم الحساب {$accountNumber} ليس له مسؤول علاقات مخصص";
                return null;
            }
        }

        return $customer;
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Amount
    |--------------------------------------------------------------------------
    */
    protected function validateAmount(array $row, array $importConfig, array &$errors): ?float
    {
        $amountValue = trim($row[$importConfig['amount_column']] ?? '');

        if (empty($amountValue)) {
            $errors[] = 'المبلغ المحصل مطلوب';
            return null;
        }

        $amount = filter_var($amountValue, FILTER_VALIDATE_FLOAT);

        if ($amount === false || $amount <= 0) {
            $errors[] = 'المبلغ المحصل غير صالح';
            return null;
        }

        return $amount;
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Date
    |--------------------------------------------------------------------------
    */
    protected function validateDate(array $row, array $importConfig, array &$errors): ?string
    {
        $dateValue = $row[$importConfig['date_column']] ?? '';

        if (empty($dateValue)) {
            $errors[] = 'تاريخ التحصيل مطلوب';
            return null;
        }

        if (is_numeric($dateValue) && $dateValue > 25569) {
            try {
                $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateValue);
                return $excelDate->format('Y-m-d');
            } catch (\Exception $e) {
            }
        }

        $dateValue = trim($dateValue);

        try {
            $date = Carbon::createFromFormat($importConfig['date_format'], $dateValue);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            $commonFormats = ['Y/m/d', 'd/m/Y', 'm/d/Y', 'Y-m-d', 'd-m-Y'];

            foreach ($commonFormats as $format) {
                try {
                    $date = Carbon::createFromFormat($format, $dateValue);
                    return $date->format('Y-m-d');
                } catch (\Exception $e) {
                    continue;
                }
            }

            $errors[] = "تاريخ التحصيل غير صالح: {$dateValue}";
            return null;
        }
    }
}
