<?php

namespace Database\Seeders\tenant;

use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Follow\CustomerFollow;
use App\Models\Tenants\BusinessCenter\Customers\Phone\CustomerPhone;
use App\Models\Tenants\BusinessCenter\Customers\Task\Task;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use App\Models\Tenants\FinancialCollection\Collection\CustomerTransaction;
use App\Models\Tenants\FinancialCollection\Target\CollectionTarget;
use App\Models\Tenants\GeneralSetting\ContactMethod\SettingsContactMethod;
use App\Models\Tenants\GeneralSetting\ContactResult\ContactResult;
use App\Models\Tenants\GeneralSetting\CustomerStatus\SettingsCustomerStatus;
use App\Models\Tenants\GeneralSetting\PaymentMethod\SettingsPaymentMethod;
use App\Models\Tenants\UserMangment\User\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CustomerDataSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::where('status', 'active')->get();
        $portfolios = Portfolio::where('status', 'active')->get();
        $users = User::all();
        $activeStatus = SettingsCustomerStatus::where('status', 'active')->first();
        $paymentMethods = SettingsPaymentMethod::all();
        $contactMethods = SettingsContactMethod::all();
        $contactResults = ContactResult::all();

        if ($companies->isEmpty() || $users->isEmpty()) {
            return;
        }

        $names = [
            ['first' => 'محمد', 'father' => 'عبدالله', 'grand' => 'سعد', 'family' => 'الغامدي'],
            ['first' => 'عبدالرحمن', 'father' => 'علي', 'grand' => 'سليمان', 'family' => 'العتيبي'],
            ['first' => 'إبراهيم', 'father' => 'خالد', 'grand' => 'فهد', 'family' => 'الدوسري'],
            ['first' => 'سلطان', 'father' => 'حمد', 'grand' => 'ناصر', 'family' => 'الشمري'],
            ['first' => 'فيصل', 'father' => 'سعود', 'grand' => 'تركي', 'family' => 'القحطاني'],
            ['first' => 'عبدالعزيز', 'father' => 'منصور', 'grand' => 'بدر', 'family' => 'الشهري'],
            ['first' => 'طارق', 'father' => 'صالح', 'grand' => 'يوسف', 'family' => 'الزهراني'],
            ['first' => 'ياسر', 'father' => 'فهد', 'grand' => 'أحمد', 'family' => 'العنزي'],
            ['first' => 'ماجد', 'father' => 'عبدالله', 'grand' => 'محمد', 'family' => 'الحربي'],
            ['first' => 'راشد', 'father' => 'عمر', 'grand' => 'عبدالعزيز', 'family' => 'السبيعي'],
            ['first' => 'نايف', 'father' => 'خالد', 'grand' => 'حمد', 'family' => 'التميمي'],
            ['first' => 'سلمان', 'father' => 'أحمد', 'grand' => 'فيصل', 'family' => 'الرويلي'],
            ['first' => 'فهد', 'father' => 'بندر', 'grand' => 'مشاري', 'family' => 'الخالدي'],
            ['first' => 'عمر', 'father' => 'سالم', 'grand' => 'وليد', 'family' => 'المطيري'],
            ['first' => 'حسام', 'father' => 'إبراهيم', 'grand' => 'صالح', 'family' => 'الغامدي'],
        ];

        $customersCreated = [];

        foreach ($names as $index => $nameParts) {
            $fullName = "{$nameParts['first']} {$nameParts['father']} {$nameParts['grand']} {$nameParts['family']}";
            $company = $companies[$index % $companies->count()];
            $portfolio = $portfolios[$index % $portfolios->count()];
            $user = $users[$index % $users->count()];
            $phone = '05' . sprintf('%08d', 10000000 + $index);
            $identity = '10' . sprintf('%08d', 50000000 + $index);
            $account = 'ACC-' . (2025000 + $index);

            $customer = Customer::updateOrCreate(
                ['identity_number' => $identity],
                [
                    'name' => $fullName,
                    'first_name' => $nameParts['first'],
                    'father_name' => $nameParts['father'],
                    'grandfather_name' => $nameParts['grand'],
                    'family_name' => $nameParts['family'],
                    'customer_type' => 'individual',
                    'company_id' => $company->id,
                    'portfolio_id' => $portfolio->id,
                    'nationality' => 'سعودي',
                    'relationship_manager_id' => $user->id,
                    'customer_status_id' => $activeStatus?->id,
                    'phone' => $phone,
                    'account_number' => $account,
                    'created_by' => $user->id,
                ]
            );

            CustomerPhone::updateOrCreate(
                ['customer_id' => $customer->id, 'phone' => $phone],
                ['type' => 'mobile', 'is_primary' => true]
            );

            // Initial Debt Transaction
            $debtAmount = ($index + 1) * 7500.00;
            CustomerTransaction::updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'type' => 'debit',
                    'description' => 'مديونية مستحقة افتتاحية',
                ],
                [
                    'amount' => $debtAmount,
                    'transaction_date' => now()->subMonths(2)->toDateString(),
                    'created_by' => $user->id,
                ]
            );

            $customersCreated[] = $customer;
        }

        // 2. Collection Targets for Users
        $currentYear = (int) now()->format('Y');
        $currentMonth = (int) now()->format('n');

        foreach ($users as $uIndex => $user) {
            $targetAmount = 80000 + ($uIndex * 20000);

            CollectionTarget::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'target_type' => 'monthly',
                    'target_year' => $currentYear,
                    'target_month' => $currentMonth,
                ],
                [
                    'branch_id' => $user->branch_id,
                    'target_amount' => $targetAmount,
                    'status' => 'active',
                    'added_by' => 1,
                ]
            );
        }

        // 3. Collections (Approved, Pending, Rejected across dates)
        $paymentMethod = $paymentMethods->first();

        foreach ($customersCreated as $cIndex => $customer) {
            $user = $users[$cIndex % $users->count()];

            if ($cIndex % 3 === 0) {
                // Approved collection this month
                $colAmount = 4500.00;
                $collection = Collection::updateOrCreate(
                    [
                        'receipt_number' => 'REC-' . (3000 + $cIndex),
                    ],
                    [
                        'customer_id' => $customer->id,
                        'collected_amount' => $colAmount,
                        'collection_type' => 'cash',
                        'payment_method_id' => $paymentMethod?->id,
                        'collection_date' => now()->subDays($cIndex + 1)->toDateString(),
                        'payment_type' => 'settlement',
                        'status' => 'approved',
                        'added_by' => $user->id,
                        'approved_by' => 1,
                        'approved_at' => now()->subDays($cIndex + 1),
                    ]
                );

                CustomerTransaction::updateOrCreate(
                    [
                        'customer_id' => $customer->id,
                        'type' => 'credit',
                        'description' => 'سداد معتمد برقم إيصال ' . $collection->receipt_number,
                    ],
                    [
                        'amount' => $colAmount,
                        'transaction_date' => $collection->collection_date,
                        'created_by' => $user->id,
                    ]
                );
            } elseif ($cIndex % 3 === 1) {
                // Pending collection today
                Collection::updateOrCreate(
                    [
                        'receipt_number' => 'REC-' . (3000 + $cIndex),
                    ],
                    [
                        'customer_id' => $customer->id,
                        'collected_amount' => 2500.00,
                        'collection_type' => 'bank_transfer',
                        'payment_method_id' => $paymentMethod?->id,
                        'collection_date' => now()->toDateString(),
                        'payment_type' => 'partial',
                        'status' => 'pending',
                        'added_by' => $user->id,
                    ]
                );
            } else {
                // Approved collection today
                $colAmount = 3000.00;
                $collection = Collection::updateOrCreate(
                    [
                        'receipt_number' => 'REC-' . (3000 + $cIndex),
                    ],
                    [
                        'customer_id' => $customer->id,
                        'collected_amount' => $colAmount,
                        'collection_type' => 'pos',
                        'payment_method_id' => $paymentMethod?->id,
                        'collection_date' => now()->toDateString(),
                        'payment_type' => 'partial',
                        'status' => 'approved',
                        'added_by' => $user->id,
                        'approved_by' => 1,
                        'approved_at' => now(),
                    ]
                );

                CustomerTransaction::updateOrCreate(
                    [
                        'customer_id' => $customer->id,
                        'type' => 'credit',
                        'description' => 'سداد جزئي برقم إيصال ' . $collection->receipt_number,
                    ],
                    [
                        'amount' => $colAmount,
                        'transaction_date' => $collection->collection_date,
                        'created_by' => $user->id,
                    ]
                );
            }
        }

        // 4. Customer Follows & Tasks (Today & Overdue)
        $taskTypes = ['promise', 'callback', 'document_request'];
        $contactMethod = $contactMethods->first();
        $contactResult = $contactResults->first();

        foreach ($customersCreated as $cIndex => $customer) {
            $user = $users[$cIndex % $users->count()];
            $taskType = $taskTypes[$cIndex % count($taskTypes)];

            $follow = CustomerFollow::create([
                'customer_id' => $customer->id,
                'contact_method_id' => $contactMethod?->id,
                'contact_location' => 'هاتفي',
                'contacted_person' => 'العميل نفسه',
                'contacted_person_name' => $customer->name,
                'next_contact_datetime' => now()->addDays(2),
                'customer_status_id' => $activeStatus?->id,
                'contact_result_id' => $contactResult?->id,
                'notes' => 'تم الاتفاق على المتابعة وتحديث جدول السداد',
                'created_by' => $user->id,
            ]);

            // Create tasks: some due today, some overdue (due 3, 5, 8 days ago)
            if ($cIndex % 2 === 0) {
                // Due today
                Task::create([
                    'customer_id' => $customer->id,
                    'follow_up_id' => $follow->id,
                    'task_type' => $taskType,
                    'due_date' => now()->toDateString(),
                    'status' => 'pending',
                ]);
            } else {
                // Overdue
                $overdueDays = ($cIndex % 3 === 0) ? 7 : (($cIndex % 3 === 1) ? 4 : 2);
                Task::create([
                    'customer_id' => $customer->id,
                    'follow_up_id' => $follow->id,
                    'task_type' => $taskType,
                    'due_date' => now()->subDays($overdueDays)->toDateString(),
                    'status' => 'pending',
                ]);
            }
        }
    }
}
