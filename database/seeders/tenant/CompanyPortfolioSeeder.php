<?php

namespace Database\Seeders\tenant;

use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Company\CompanyAuthorizedPerson;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use Illuminate\Database\Seeder;

class CompanyPortfolioSeeder extends Seeder
{
    public function run(): void
    {
        $companiesData = [
            [
                'name' => 'شركة الراجحي المالية',
                'status' => 'active',
                'authorized' => [
                    'name' => 'عبدالعزيز السليمان',
                    'position' => 'مدير الحسابات والتحصيل',
                    'phone' => '0551112233',
                    'email' => 'a.soliman@alrajhi.com',
                ],
                'portfolios' => [
                    'محفظة التمويل الشخصي 2025',
                    'محفظة البطاقات الائتمانية المتعثرة',
                ],
            ],
            [
                'name' => 'شركة الاتصالات السعودية (STC)',
                'status' => 'active',
                'authorized' => [
                    'name' => 'فهد المطيري',
                    'position' => 'مشرف إدارة المديونيات',
                    'phone' => '0552223344',
                    'email' => 'f.mutairi@stc.com.sa',
                ],
                'portfolios' => [
                    'محفظة الفواتير المفوترة 2025',
                    'محفظة أجهزة التقسيط',
                ],
            ],
            [
                'name' => 'شركة اليسر للإجارة والتمويل',
                'status' => 'active',
                'authorized' => [
                    'name' => 'عمر القاسم',
                    'position' => 'مدير الائتمان والمتابعة',
                    'phone' => '0553334455',
                    'email' => 'o.qasim@alyusr.com.sa',
                ],
                'portfolios' => [
                    'محفظة عقود السيارات المتعثرة',
                ],
            ],
            [
                'name' => 'شركة الوطنية للتأمين',
                'status' => 'inactive',
                'authorized' => null,
                'portfolios' => [
                    'محفظة مطالبات التعويض القديمة',
                ],
            ],
        ];

        foreach ($companiesData as $cData) {
            $company = Company::updateOrCreate(
                ['name' => $cData['name']],
                ['status' => $cData['status']]
            );

            if (!empty($cData['authorized'])) {
                CompanyAuthorizedPerson::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'name' => $cData['authorized']['name'],
                    ],
                    [
                        'position' => $cData['authorized']['position'],
                        'phone' => $cData['authorized']['phone'],
                        'email' => $cData['authorized']['email'],
                        'is_default' => true,
                    ]
                );
            }

            foreach ($cData['portfolios'] as $pName) {
                Portfolio::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'name' => $pName,
                    ],
                    [
                        'has_split_names' => false,
                        'assignment_date' => now()->subMonths(3)->toDateString(),
                        'call_start_time' => '08:00:00',
                        'call_end_time' => '17:00:00',
                        'status' => $cData['status'] === 'active' ? 'active' : 'inactive',
                    ]
                );
            }
        }
    }
}
