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
                        'status' => $cData['status'] === 'active' ? 'active' : 'inactive',
                    ]
                );
            }

            // Seed demo contract for each company
            $contract = \App\Models\Tenants\BusinessCenter\Contract\Contract::updateOrCreate(
                ['contract_number' => 'CON-2026-' . str_pad($company->id, 4, '0', STR_PAD_LEFT)],
                [
                    'company_id'      => $company->id,
                    'start_date'      => now()->subMonths(2)->toDateString(),
                    'duration_months' => 12,
                    'end_date'        => now()->addMonths(10)->toDateString(),
                    'auto_renewal'    => true,
                    'commission_type' => 'duration',
                    'status'          => $cData['status'] === 'active' ? 'active' : 'inactive',
                    'notes'           => 'عقد خدمات مع ' . $company->name,
                ]
            );

            \App\Models\Tenants\BusinessCenter\Contract\ContractCommission::updateOrCreate(
                [
                    'contract_id'     => $contract->id,
                    'commission_type' => 'duration',
                ],
                [
                    'duration_from'   => 1,
                    'duration_to'     => 12,
                    'commission_rate' => 10.00,
                    'is_active'       => true,
                ]
            );
        }
    }
}
