<?php

namespace Database\Seeders\tenant;

use App\Jobs\Tenants\Auctions\GenerateAuctionReportsJob;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionAttachment;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class AuctionsAndPropertiesSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        $userId = $user?->id;

        // ---------------------------------------------------------
        // 1. Create Assignments
        // ---------------------------------------------------------
        $assignmentsData = [
            [
                'assignment_number' => 'ASN-' . date('Y') . '-00101',
                'name'              => 'إسناد تصفية تركة الشيخ الراجحي العقارية',
                'issuing_entity'    => 'مركز الإسناد والتصفية (إنفاذ)',
                'city'              => 'الرياض',
                'category'          => 'lands',
                'start_date'        => Carbon::now()->subMonths(2)->format('Y-m-d'),
                'end_date'          => Carbon::now()->addMonths(6)->format('Y-m-d'),
                'notes'             => 'إسناد رسمي برقم قرار قضائي صادر من محكمة الأحوال الشخصية بالرياض.',
                'status'            => 'active',
                'is_active'         => true,
            ],
            [
                'assignment_number' => 'ASN-' . date('Y') . '-00102',
                'name'              => 'إسناد بيع أصول ومخططات مساهمة النورس',
                'issuing_entity'    => 'محكمة التنفيذ بجدة - الدائرة الرابعة',
                'city'              => 'جدة',
                'category'          => 'commercial_buildings',
                'start_date'        => Carbon::now()->subMonth()->format('Y-m-d'),
                'end_date'          => Carbon::now()->addMonths(4)->format('Y-m-d'),
                'notes'             => 'تصفية محفظة عقارية تجارية وسكنية بمدينة جدة.',
                'status'            => 'active',
                'is_active'         => true,
            ],
            [
                'assignment_number' => 'ASN-' . date('Y') . '-00103',
                'name'              => 'إسناد محفظة أصول المنطقة الشرقية',
                'issuing_entity'    => 'الهيئة العامة لعقارات الدولة',
                'city'              => 'الدمام',
                'category'          => 'industrial',
                'start_date'        => Carbon::now()->subDays(20)->format('Y-m-d'),
                'end_date'          => Carbon::now()->addMonths(8)->format('Y-m-d'),
                'notes'             => 'مجموعة أراضي ومستودعات في المدن الصناعية.',
                'status'            => 'under_review',
                'is_active'         => true,
            ],
            [
                'assignment_number' => 'ASN-' . date('Y') . '-00104',
                'name'              => 'إسناد حراسة قضائية لمجموعة فلل النرجس والياسمين',
                'issuing_entity'    => 'وزارة العدل - محكمة التنفيذ بالرياض',
                'city'              => 'الرياض',
                'category'          => 'villas',
                'start_date'        => Carbon::now()->subDays(10)->format('Y-m-d'),
                'end_date'          => Carbon::now()->addMonths(3)->format('Y-m-d'),
                'notes'             => 'فلل سكنية فاخرة في أحياء شمال الرياض.',
                'status'            => 'active',
                'is_active'         => true,
            ],
            [
                'assignment_number' => 'ASN-' . date('Y') . '-00105',
                'name'              => 'إسناد عقارات العاصمة المقدسة',
                'issuing_entity'    => 'مركز الإسناد والتصفية (إنفاذ)',
                'city'              => 'مكة المكرمة',
                'category'          => 'commercial_buildings',
                'start_date'        => Carbon::now()->subMonths(3)->format('Y-m-d'),
                'end_date'          => Carbon::now()->subDays(5)->format('Y-m-d'),
                'notes'             => 'عقارات فندقية وسكنية بالمنطقة المركزية.',
                'status'            => 'suspended',
                'is_active'         => false,
            ],
            [
                'assignment_number' => 'ASN-' . date('Y') . '-00106',
                'name'              => 'إسناد ملغي - عقارات الواحة بالخبر',
                'issuing_entity'    => 'محكمة التنفيذ بالدمام',
                'city'              => 'الخبر',
                'category'          => 'lands',
                'start_date'        => Carbon::now()->subMonths(4)->format('Y-m-d'),
                'end_date'          => Carbon::now()->subMonths(2)->format('Y-m-d'),
                'notes'             => 'تم أرشفة الإسناد لاختبار سلة المحذوفات.',
                'status'            => 'suspended',
                'is_active'         => false,
            ],
        ];

        $assignments = [];
        foreach ($assignmentsData as $data) {
            $isArchived = ($data['assignment_number'] === 'ASN-' . date('Y') . '-00106');
            $assignment = Assignment::withTrashed()->updateOrCreate(
                ['assignment_number' => $data['assignment_number']],
                array_merge($data, ['created_by' => $userId])
            );

            if ($isArchived && ! $assignment->trashed()) {
                $assignment->delete();
            }

            $assignments[$data['assignment_number']] = $assignment;
        }

        // ---------------------------------------------------------
        // 2. Create Properties
        // ---------------------------------------------------------
        $assignment1 = $assignments['ASN-' . date('Y') . '-00101'];
        $assignment2 = $assignments['ASN-' . date('Y') . '-00102'];
        $assignment3 = $assignments['ASN-' . date('Y') . '-00103'];
        $assignment4 = $assignments['ASN-' . date('Y') . '-00104'];

        $propertiesData = [
            // Assignment 1 Properties (الرياض - أراضي)
            [
                'assignment_id'   => $assignment1->id,
                'property_number' => 'PROP-001',
                'name'            => 'أرض تجارية استثمارية بحي الملقا',
                'type'            => 'commercial_land',
                'city'            => 'الرياض',
                'district'        => 'الملقا',
                'area'            => 2450.00,
                'deed_number'     => '310105004812',
                'estimated_value' => 12500000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'تقع على طريق الملك فهد مباشرة بواجهة شمالية.',
            ],
            [
                'assignment_id'   => $assignment1->id,
                'property_number' => 'PROP-002',
                'name'            => 'أرض سكنية بمخطط النرجس ركنيّة',
                'type'            => 'residential_land',
                'city'            => 'الرياض',
                'district'        => 'النرجس',
                'area'            => 900.00,
                'deed_number'     => '310105004813',
                'estimated_value' => 3800000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'قريبة من محطة المترو والخدمات.',
            ],
            [
                'assignment_id'   => $assignment1->id,
                'property_number' => 'PROP-003',
                'name'            => 'بلك تجاري وسكني بحي الياسمين',
                'type'            => 'commercial_land',
                'city'            => 'الرياض',
                'district'        => 'الياسمين',
                'area'            => 5400.00,
                'deed_number'     => '310105004814',
                'estimated_value' => 24000000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'موقع استراتيجي متعدد الأدوار.',
            ],

            // Assignment 2 Properties (جدة - عمائر وأبراج)
            [
                'assignment_id'   => $assignment2->id,
                'property_number' => 'PROP-004',
                'name'            => 'مبنى تجاري إداري بحي الشاطئ',
                'type'            => 'commercial_building',
                'city'            => 'جدة',
                'district'        => 'الشاطئ',
                'area'            => 1850.00,
                'deed_number'     => '420206001290',
                'estimated_value' => 18000000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'مؤجر لشركات كبرى بدخل سنوي ممتاز.',
            ],
            [
                'assignment_id'   => $assignment2->id,
                'property_number' => 'PROP-005',
                'name'            => 'عمارة سكنية بحي الروضة 12 شقة',
                'type'            => 'commercial_building',
                'city'            => 'جدة',
                'district'        => 'الروضة',
                'area'            => 1200.00,
                'deed_number'     => '420206001291',
                'estimated_value' => 8500000.00,
                'data_status'     => 'needs_images',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'تحتاج إلى تصوير فوتوغرافي وتحديث الصك.',
            ],

            // Assignment 3 Properties (الدمام - صناعي)
            [
                'assignment_id'   => $assignment3->id,
                'property_number' => 'PROP-006',
                'name'            => 'مجمع مستودعات لوجستية بالمدينة الصناعية الثانية',
                'type'            => 'agricultural_land',
                'city'            => 'الدمام',
                'district'        => 'المدينة الصناعية الثانية',
                'area'            => 14000.00,
                'deed_number'     => '530307009800',
                'estimated_value' => 32000000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'مجهزة بأحدث معايير السلامة والدفاع المدني.',
            ],

            // Assignment 4 Properties (الرياض - فلل)
            [
                'assignment_id'   => $assignment4->id,
                'property_number' => 'PROP-007',
                'name'            => 'فيلا مودرن زاوية بحي النرجس',
                'type'            => 'villa',
                'city'            => 'الرياض',
                'district'        => 'النرجس',
                'area'            => 450.00,
                'deed_number'     => '310105009911',
                'estimated_value' => 4200000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'مسبح ومصعد وتكييف مركزي متكامل.',
            ],
            [
                'assignment_id'   => $assignment4->id,
                'property_number' => 'PROP-008',
                'name'            => 'فيلا سكنية بحي العارض مع حديقة',
                'type'            => 'villa',
                'city'            => 'الرياض',
                'district'        => 'العارض',
                'area'            => 500.00,
                'deed_number'     => '310105009912',
                'estimated_value' => 4600000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'بناء شخصي وتشطيبات فندقية.',
            ],
            [
                'assignment_id'   => null,
                'property_number' => 'PROP-010',
                'name'            => 'أرض تجارية مستقلة بحي النرجس',
                'type'            => 'commercial_land',
                'usage_classification' => 'commercial',
                'city'            => 'الرياض',
                'district'        => 'النرجس',
                'area'            => 1500.00,
                'deed_number'     => '310105009920',
                'estimated_value' => 7500000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'عقار مستقل مكتمل البيانات جاهز للربط بإسناد.',
            ],
            [
                'assignment_id'   => null,
                'property_number' => 'PROP-011',
                'name'            => 'فيلا تحت المراجعة بحي حطين',
                'type'            => 'villa',
                'usage_classification' => 'residential',
                'city'            => 'الرياض',
                'district'        => 'حطين',
                'area'            => 450.00,
                'deed_number'     => '310105009921',
                'estimated_value' => 3800000.00,
                'data_status'     => 'needs_review',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'عقار غير مرتبط بإسناد وبحاجة لمراجعة المستندات.',
            ],
            [
                'assignment_id'   => $assignment4->id,
                'property_number' => 'PROP-009',
                'name'            => 'عقار ملغي ومؤرشف للاختبار',
                'type'            => 'villa',
                'city'            => 'الرياض',
                'district'        => 'الياسمين',
                'area'            => 600.00,
                'deed_number'     => '310105009919',
                'estimated_value' => 5000000.00,
                'data_status'     => 'incomplete',
                'status'          => 'available',
                'is_active'       => false,
                'notes'           => 'عقار في الأرشيف لتجربة الاستعادة والحذف النهائي.',
            ],
        ];

        $createdProperties = [];
        foreach ($propertiesData as $pData) {
            $isArchived = ($pData['property_number'] === 'PROP-009');
            $prop = Property::withTrashed()->updateOrCreate(
                ['property_number' => $pData['property_number']],
                array_merge($pData, ['created_by' => $userId])
            );

            if ($prop->boundaries()->count() === 0) {
                $prop->boundaries()->createMany([
                    ['direction' => 'north', 'border_type' => 'شارع', 'border_description' => 'شارع عرض 20 متر', 'length' => 30.0, 'sort_order' => 0],
                    ['direction' => 'south', 'border_type' => 'قطعة', 'border_description' => 'رقم القطعة 1288', 'length' => 30.0, 'sort_order' => 1],
                    ['direction' => 'east', 'border_type' => 'قطعة', 'border_description' => 'رقم القطعة 1290', 'length' => 20.0, 'sort_order' => 2],
                    ['direction' => 'west', 'border_type' => 'شارع', 'border_description' => 'شارع عرض 15 متر', 'length' => 20.0, 'sort_order' => 3],
                ]);
            }

            if ($prop->owners()->count() === 0) {
                $prop->owners()->create([
                    'owner_name' => 'شركة أصول العقارية',
                    'national_id_or_cr' => '1010998877',
                    'sort_order' => 0,
                ]);
            }

            if ($isArchived && ! $prop->trashed()) {
                $prop->delete();
            }

            $createdProperties[$pData['property_number']] = $prop;
        }

        // ---------------------------------------------------------
        // 3. Create Auctions & Generate Sample Reports
        // ---------------------------------------------------------
        $sampleReportPath = 'auctions/report-images/sample-report-cover.png';
        if (! Storage::disk('tenant_public')->exists($sampleReportPath)) {
            $sourceFile = public_path('static/samples/auction-templates/brochure-1.png');
            if (File::exists($sourceFile)) {
                Storage::disk('tenant_public')->put($sampleReportPath, File::get($sourceFile));
            }
        }

        $auctionsData = [
            [
                'assignment_id'            => $assignment1->id,
                'name'                     => 'مزاد درة الرياض العقاري الإلكتروني',
                'description'              => 'فرص عقارية تجارية وسكنية كبرى في أرقى أحياء العاصمة الرياض تحت إشراف إنفاذ.',
                'start_date'               => Carbon::now()->addDays(5)->format('Y-m-d'),
                'start_time'               => '16:00:00',
                'end_date'                 => Carbon::now()->addDays(8)->format('Y-m-d'),
                'end_time'                 => '21:00:00',
                'type'                     => 'electronic',
                'category'                 => 'lands',
                'city'                     => 'الرياض',
                'expected_properties_count'=> 3,
                'supervising_entity'       => 'مركز الإسناد والتصفية (إنفاذ)',
                'project_manager_name'     => 'م. فهد بن عبدالعزيز',
                'platform_name'            => 'منصة سومات للمزادات الإلكترونية',
                'platform_url'             => 'https://soum.sa',
                'report_image'             => $sampleReportPath,
                'marketing_data'           => [
                    'campaigns' => [
                        ['platform' => 'Instagram', 'reach' => 32000, 'impressions' => 58000, 'engagements' => 2400, 'clicks' => 890],
                        ['platform' => 'Twitter/X', 'reach' => 22000, 'impressions' => 41000, 'engagements' => 1350, 'clicks' => 510],
                    ],
                    'links' => [
                        ['label' => 'حملة إطلاق المزاد', 'url' => 'https://soum.sa/dorra-riyadh'],
                    ],
                ],
                'status'                   => 'active',
                'is_active'                => true,
                'properties_to_sync'       => [
                    'PROP-001' => ['sort_order' => 1, 'result_status' => 'sold', 'opening_price' => 2500000.00, 'sale_price' => 3100000.00, 'participants_count' => 18, 'bids_count' => 45],
                    'PROP-002' => ['sort_order' => 2, 'result_status' => 'sold', 'opening_price' => 1800000.00, 'sale_price' => 2250000.00, 'participants_count' => 14, 'bids_count' => 32],
                    'PROP-003' => ['sort_order' => 3, 'result_status' => 'unsold', 'opening_price' => 4000000.00, 'sale_price' => null, 'participants_count' => 5, 'bids_count' => 12, 'unsold_reason' => 'عدم وصول المزايدات للسعر التحفظي المحدد من المحكمة'],
                ],
            ],
            [
                'assignment_id'            => $assignment2->id,
                'name'                     => 'مزاد جوهرة الغربية الحضوري',
                'description'              => 'مزاد علني حضوري لبيع مبانٍ تجارية واستثمارية بارزة بمدينة جدة.',
                'start_date'               => Carbon::now()->addDays(15)->format('Y-m-d'),
                'start_time'               => '18:30:00',
                'end_date'                 => Carbon::now()->addDays(16)->format('Y-m-d'),
                'end_time'                 => '22:00:00',
                'type'                     => 'in_person',
                'category'                 => 'commercial_buildings',
                'city'                     => 'جدة',
                'expected_properties_count'=> 2,
                'supervising_entity'       => 'محكمة التنفيذ بجدة',
                'project_manager_name'     => 'أ. سلطان الحربي',
                'platform_name'            => 'قاعة ليلتي للمناسبات - جدة',
                'platform_url'             => null,
                'report_image'             => $sampleReportPath,
                'marketing_data'           => [
                    'campaigns' => [
                        ['platform' => 'Instagram', 'reach' => 19000, 'impressions' => 34000, 'engagements' => 1400, 'clicks' => 460],
                    ],
                ],
                'status'                   => 'in_preparation',
                'is_active'                => true,
                'properties_to_sync'       => [
                    'PROP-004' => ['sort_order' => 1, 'result_status' => 'sold', 'opening_price' => 8500000.00, 'sale_price' => 10200000.00, 'participants_count' => 22, 'bids_count' => 58],
                    'PROP-005' => ['sort_order' => 2, 'result_status' => 'pending', 'opening_price' => 6000000.00, 'sale_price' => null, 'participants_count' => null, 'bids_count' => null],
                ],
            ],
            [
                'assignment_id'            => $assignment4->id,
                'name'                     => 'مزاد فلل النرجس الهجين',
                'description'              => 'مزاد هجين يجمع بين الحضور الشخصي والمزايدة الإلكترونية المتزامنة.',
                'start_date'               => Carbon::now()->addDays(25)->format('Y-m-d'),
                'start_time'               => '17:00:00',
                'end_date'                 => Carbon::now()->addDays(27)->format('Y-m-d'),
                'end_time'                 => '21:00:00',
                'type'                     => 'hybrid',
                'category'                 => 'villas',
                'city'                     => 'الرياض',
                'expected_properties_count'=> 2,
                'supervising_entity'       => 'وزارة العدل',
                'project_manager_name'     => 'م. عبدالله القحطاني',
                'platform_name'            => 'منصة مباشر للمزادات',
                'platform_url'             => 'https://mubasher.sa',
                'report_image'             => $sampleReportPath,
                'marketing_data'           => [
                    'campaigns' => [
                        ['platform' => 'Instagram', 'reach' => 28000, 'impressions' => 48000, 'engagements' => 1950, 'clicks' => 730],
                    ],
                ],
                'status'                   => 'in_preparation',
                'is_active'                => true,
                'properties_to_sync'       => [
                    'PROP-007' => ['sort_order' => 1, 'result_status' => 'sold', 'opening_price' => 3200000.00, 'sale_price' => 3750000.00, 'participants_count' => 16, 'bids_count' => 39],
                    'PROP-008' => ['sort_order' => 2, 'result_status' => 'pending', 'opening_price' => 2900000.00, 'sale_price' => null, 'participants_count' => null, 'bids_count' => null],
                ],
            ],
            [
                'assignment_id'            => $assignment1->id,
                'name'                     => 'مزاد مؤرشف تجريبي للاختبار',
                'description'              => 'مزاد تمت أرشفته لتجربة جدول سلة المحذوفات.',
                'start_date'               => Carbon::now()->subMonths(1)->format('Y-m-d'),
                'start_time'               => '16:00:00',
                'end_date'                 => Carbon::now()->subDays(25)->format('Y-m-d'),
                'end_time'                 => '20:00:00',
                'type'                     => 'electronic',
                'category'                 => 'lands',
                'city'                     => 'الرياض',
                'expected_properties_count'=> 1,
                'supervising_entity'       => 'مركز الإسناد والتصفية',
                'project_manager_name'     => 'مدير تجريبي',
                'platform_name'            => 'منصة سومات',
                'platform_url'             => 'https://soum.sa',
                'report_image'             => null,
                'status'                   => 'suspended',
                'is_active'                => false,
                'properties_to_sync'       => [],
            ],
        ];

        foreach ($auctionsData as $aucData) {
            $isArchived = ($aucData['name'] === 'مزاد مؤرشف تجريبي للاختبار');
            $propsToSync = $aucData['properties_to_sync'];
            unset($aucData['properties_to_sync']);

            $auction = Auction::updateOrCreate(
                ['name' => $aucData['name']],
                array_merge($aucData, ['created_by' => $userId])
            );

            // Sync properties to auction with results and ordering
            if (! empty($propsToSync)) {
                $syncData = [];
                $order = 1;
                foreach ($propsToSync as $key => $val) {
                    $code = is_array($val) ? $key : $val;
                    $pivotAttrs = is_array($val) ? $val : ['sort_order' => $order++];
                    if (isset($createdProperties[$code])) {
                        $prop = $createdProperties[$code];
                        $syncData[$prop->id] = $pivotAttrs;
                        $prop->update(['status' => 'assigned_to_auction']);
                    }
                }
                $auction->properties()->sync($syncData);
            }

            // Create sample attachment
            AuctionAttachment::firstOrCreate(
                [
                    'auction_id' => $auction->id,
                    'file_name'  => 'كتيب_المزاد_الرسمي.pdf',
                ],
                [
                    'file_path'  => 'auctions/attachments/sample_brochure.pdf',
                    'file_type'  => 'application/pdf',
                    'file_size'  => 2450000,
                    'category'   => 'brochure',
                    'channels'   => ['web', 'social', 'whatsapp'],
                ]
            );

            // Generate reports for active seeded auctions
            if (! $isArchived) {
                try {
                    $tenantId = (string) (tenant('id') ?: tenant()?->id);
                    if ($tenantId) {
                        GenerateAuctionReportsJob::dispatchSyncNow($auction, $tenantId);
                    }
                } catch (\Throwable $e) {
                    // Seeding remains resilient even if Browsershot is offline
                }
            }

            if ($isArchived && ! $auction->trashed()) {
                $auction->delete();
            }
        }
    }
}
