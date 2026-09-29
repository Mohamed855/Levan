<?php

namespace Database\Seeders\tenant;

use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Follow\CustomerFollow;
use App\Models\Tenants\BusinessCenter\Customers\Phone\CustomerPhone;
use App\Models\Tenants\BusinessCenter\Customers\Task\Task;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
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

            $customersCreated[] = $customer;
        }

        // 2. Customer Follows & Tasks (Today & Overdue)
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
