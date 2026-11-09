<?php

declare(strict_types=1);

namespace Database\Seeders\tenant;

use App\Enums\Tenants\Properties\PropertyUnitStatus;
use App\Enums\Tenants\Properties\PropertyUnitType;
use App\Enums\Tenants\Properties\PropertyUnitUsage;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Assignments\IssuingEntity;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionAttachment;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Auctions\Reports\AuctionReportService;
use Carbon\Carbon;
use Database\Seeders\tenant\Support\SeededAuctionDocuments;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class AuctionsAndPropertiesSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('seeding.test_seeders_enabled', false)) {
            $this->command?->warn('AuctionsAndPropertiesSeeder is disabled. Set TEST_SEEDERS_ENABLED=true to enable test data.');

            return;
        }

        $user = User::first();
        $userId = $user?->id;

        // Ensure issuing entities exist
        if (IssuingEntity::count() === 0) {
            $this->call(IssuingEntitySeeder::class);
        }

        // ---------------------------------------------------------
        // 1. Create Assignments (Covering all 5 categories + inactive + archived)
        // ---------------------------------------------------------
        $assignmentsData = [
            [
                'assignment_number' => 'ASN-' . date('Y') . '-00101',
                'name'              => 'إسناد تصفية تركة الشيخ الراجحي العقارية',
                'issuing_entity'    => 'مركز الإسناد والتصفية (إنفاذ)',
                'city'              => 'الرياض',
                'category'          => 'residential',
                'start_date'        => Carbon::now()->subMonths(2)->format('Y-m-d'),
                'end_date'          => Carbon::now()->addMonths(6)->format('Y-m-d'),
                'notes'             => 'إسناد رسمي برقم قرار قضائي صادر من محكمة الأحوال الشخصية بالرياض.',
                'is_active'         => true,
            ],
            [
                'assignment_number' => 'ASN-' . date('Y') . '-00102',
                'name'              => 'إسناد بيع أصول ومخططات مساهمة النورس',
                'issuing_entity'    => 'محكمة التنفيذ بجدة',
                'city'              => 'جدة',
                'category'          => 'commercial',
                'start_date'        => Carbon::now()->subMonth()->format('Y-m-d'),
                'end_date'          => Carbon::now()->addMonths(4)->format('Y-m-d'),
                'notes'             => 'تصفية محفظة عقارية تجارية كبرى بمدينة جدة.',
                'is_active'         => true,
            ],
            [
                'assignment_number' => 'ASN-' . date('Y') . '-00103',
                'name'              => 'إسناد محفظة أصول ومصانع المنطقة الشرقية',
                'issuing_entity'    => 'الهيئة العامة لعقارات الدولة',
                'city'              => 'الدمام',
                'category'          => 'industrial',
                'start_date'        => Carbon::now()->subDays(20)->format('Y-m-d'),
                'end_date'          => Carbon::now()->addMonths(8)->format('Y-m-d'),
                'notes'             => 'مجموعة مستودعات وأراضي صناعية في المدن الصناعية.',
                'is_active'         => true,
            ],
            [
                'assignment_number' => 'ASN-' . date('Y') . '-00104',
                'name'              => 'إسناد حراسة قضائية لمجموعة فلل النرجس والياسمين',
                'issuing_entity'    => 'وزارة العدل',
                'city'              => 'الرياض',
                'category'          => 'residential',
                'start_date'        => Carbon::now()->subDays(10)->format('Y-m-d'),
                'end_date'          => Carbon::now()->addMonths(3)->format('Y-m-d'),
                'notes'             => 'فلل سكنية فاخرة في أحياء شمال الرياض.',
                'is_active'         => true,
            ],
            [
                'assignment_number' => 'ASN-' . date('Y') . '-00105',
                'name'              => 'إسناد عقارات العاصمة المقدسة',
                'issuing_entity'    => 'محكمة التنفيذ بمكة المكرمة',
                'city'              => 'مكة المكرمة',
                'category'          => 'commercial',
                'start_date'        => Carbon::now()->subMonths(3)->format('Y-m-d'),
                'end_date'          => Carbon::now()->subDays(5)->format('Y-m-d'),
                'notes'             => 'عقارات فندقية وتجارية بالمنطقة المركزية (غير نشط لاختبار الفلاتر).',
                'is_active'         => false,
            ],
            [
                'assignment_number' => 'ASN-' . date('Y') . '-00106',
                'name'              => 'إسناد ملغي - عقارات الواحة بالخبر',
                'issuing_entity'    => 'محكمة التنفيذ بالدمام',
                'city'              => 'الخبر',
                'category'          => 'residential',
                'start_date'        => Carbon::now()->subMonths(4)->format('Y-m-d'),
                'end_date'          => Carbon::now()->subMonths(2)->format('Y-m-d'),
                'notes'             => 'تم أرشفة الإسناد لاختبار سلة المحذوفات.',
                'is_active'         => false,
            ],
            [
                'assignment_number' => 'ASN-' . date('Y') . '-00107',
                'name'              => 'إسناد مزارع وبساتين القصيم والخرج النموذجية',
                'issuing_entity'    => 'لجنة الإفلاس',
                'city'              => 'بريدة',
                'category'          => 'agricultural',
                'start_date'        => Carbon::now()->subMonths(1)->format('Y-m-d'),
                'end_date'          => Carbon::now()->addMonths(5)->format('Y-m-d'),
                'notes'             => 'إسناد تصفية أصول زراعية ومزارع إنتاجية مجهزة بالآبار والرشاشات المحورية.',
                'is_active'         => true,
            ],
            [
                'assignment_number' => 'ASN-' . date('Y') . '-00108',
                'name'              => 'إسناد أبراج ومجمعات طريق الملك فهد الاستثمارية',
                'issuing_entity'    => 'محكمة التنفيذ بالرياض',
                'city'              => 'الرياض',
                'category'          => 'mixed_use',
                'start_date'        => Carbon::now()->subDays(15)->format('Y-m-d'),
                'end_date'          => Carbon::now()->addMonths(7)->format('Y-m-d'),
                'notes'             => 'أصول ومجمعات تجارية وسكنية متعددة الاستخدامات بمواقع استراتيجية.',
                'is_active'         => true,
            ],
        ];

        $assignments = [];
        foreach ($assignmentsData as $data) {
            $isArchived = ($data['assignment_number'] === 'ASN-' . date('Y') . '-00106');
            $issuingEntityId = ! empty($data['issuing_entity'])
                ? IssuingEntity::where('name', trim($data['issuing_entity']))->value('id')
                : null;

            $assignment = Assignment::withTrashed()->updateOrCreate(
                ['assignment_number' => $data['assignment_number']],
                array_merge($data, [
                    'created_by' => $userId,
                    'issuing_entity_id' => $issuingEntityId,
                ])
            );

            if ($isArchived && ! $assignment->trashed()) {
                $assignment->delete();
            }

            $assignments[$data['assignment_number']] = $assignment;
        }

        // ---------------------------------------------------------
        // 2. Create Properties
        // ---------------------------------------------------------
        $assignment1 = $assignments['ASN-' . date('Y') . '-00101']; // Residential
        $assignment2 = $assignments['ASN-' . date('Y') . '-00102']; // Commercial
        $assignment3 = $assignments['ASN-' . date('Y') . '-00103']; // Industrial
        $assignment4 = $assignments['ASN-' . date('Y') . '-00104']; // Residential
        $assignment7 = $assignments['ASN-' . date('Y') . '-00107']; // Agricultural
        $assignment8 = $assignments['ASN-' . date('Y') . '-00108']; // Mixed Use

        $propertiesData = [
            // ==========================================
            // ASSIGNED PROPERTIES (Assigned to Assignments)
            // ==========================================

            // Assignment 1 (Residential)
            [
                'assignment_id'   => $assignment1->id,
                'property_number' => 'PROP-001',
                'name'            => 'أرض سكنية بمخطط النرجس ركنيّة',
                'type'            => 'land',
                'usage_classification' => 'residential',
                'city'            => 'الرياض',
                'district'        => 'النرجس',
                'area'            => 900.00,
                'deed_number'     => '310105004812',
                'estimated_value' => 3800000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'قريبة من محطة المترو والخدمات بواجهة شمالية شرقية.',
            ],
            [
                'assignment_id'   => $assignment1->id,
                'property_number' => 'PROP-002',
                'name'            => 'فيلا سكنية فاخرة بحي حطين النموذجي',
                'type'            => 'villa',
                'usage_classification' => 'residential',
                'city'            => 'الرياض',
                'district'        => 'حطين',
                'area'            => 650.00,
                'deed_number'     => '310105004813',
                'estimated_value' => 6200000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'فيلا درج بالصالة بتشطيبات راقية ومصعد ومسبح.',
            ],
            [
                'assignment_id'   => $assignment1->id,
                'property_number' => 'PROP-003',
                'name'            => 'قصر اليمامة الملكي بحي المعذر الشمالي',
                'type'            => 'palace',
                'usage_classification' => 'residential',
                'city'            => 'الرياض',
                'district'        => 'المعذر الشمالي',
                'area'            => 3200.00,
                'deed_number'     => '310105004814',
                'estimated_value' => 28000000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'قصر فاخر بحدائق واسعة وقاعات استقبال وملاحق ضيافة متكاملة.',
            ],
            [
                'assignment_id'   => $assignment1->id,
                'property_number' => 'PROP-004',
                'name'            => 'شقة بنتهاوس فاخرة بإطلالة على أبراج العليا',
                'type'            => 'apartment',
                'usage_classification' => 'residential',
                'city'            => 'الرياض',
                'district'        => 'العليا',
                'area'            => 280.00,
                'deed_number'     => '310105004815',
                'estimated_value' => 1850000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'شقة مفروشة بالكامل مع تراس بانورامي وموقفين سيارة بالبدروم.',
            ],
            [
                'assignment_id'   => $assignment1->id,
                'property_number' => 'PROP-005',
                'name'            => 'استراحة ومرفق ضيافة خاص بحي الرمال',
                'type'            => 'other',
                'usage_classification' => 'other',
                'city'            => 'الرياض',
                'district'        => 'الرمال',
                'area'            => 1800.00,
                'deed_number'     => '310105004816',
                'estimated_value' => 2900000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'مرفق ترفيهي واستراحة عائلية مجهزة بمسطحات خضراء ومجالس تراثية.',
            ],

            // Assignment 2 (Commercial)
            [
                'assignment_id'   => $assignment2->id,
                'property_number' => 'PROP-006',
                'name'            => 'مبنى تجاري إداري بحي الشاطئ',
                'type'            => 'building',
                'usage_classification' => 'commercial',
                'city'            => 'جدة',
                'district'        => 'الشاطئ',
                'area'            => 1850.00,
                'deed_number'     => '420206001290',
                'estimated_value' => 18000000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'مؤجر لشركات كبرى بدخل سنوي ممتاز وواجهات زجاجية.',
            ],
            [
                'assignment_id'   => $assignment2->id,
                'property_number' => 'PROP-007',
                'name'            => 'أرض تجارية استثمارية على كورنيش جدة',
                'type'            => 'land',
                'usage_classification' => 'commercial',
                'city'            => 'جدة',
                'district'        => 'الحمراء',
                'area'            => 3200.00,
                'deed_number'     => '420206001291',
                'estimated_value' => 22000000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'موقع استثماري فريد بواجهة مباشرة على الكورنيش.',
            ],

            // Assignment 3 (Industrial)
            [
                'assignment_id'   => $assignment3->id,
                'property_number' => 'PROP-008',
                'name'            => 'مجمع مستودعات لوجستية بالمدينة الصناعية الثانية',
                'type'            => 'warehouse',
                'usage_classification' => 'industrial',
                'city'            => 'الدمام',
                'district'        => 'المدينة الصناعية الثانية',
                'area'            => 14000.00,
                'deed_number'     => '530307009800',
                'estimated_value' => 32000000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'مجهزة بأحدث معايير السلامة والدفاع المدني ومستودعات تبريد.',
            ],
            [
                'assignment_id'   => $assignment3->id,
                'property_number' => 'PROP-009',
                'name'            => 'أرض صناعية مخصصة للصناعات الخفيفة بالجبيل',
                'type'            => 'land',
                'usage_classification' => 'industrial',
                'city'            => 'الجبيل',
                'district'        => 'المنطقة المساندة',
                'area'            => 8500.00,
                'deed_number'     => '530307009801',
                'estimated_value' => 12000000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'أرض مطورة بالكامل مع شبكة كهرباء ومياه وصرف صناعي.',
            ],

            // Assignment 4 (Residential)
            [
                'assignment_id'   => $assignment4->id,
                'property_number' => 'PROP-010',
                'name'            => 'فيلا مودرن زاوية بحي النرجس',
                'type'            => 'villa',
                'usage_classification' => 'residential',
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
                'property_number' => 'PROP-011',
                'name'            => 'فيلا سكنية بحي العارض مع حديقة واسعة',
                'type'            => 'villa',
                'usage_classification' => 'residential',
                'city'            => 'الرياض',
                'district'        => 'العارض',
                'area'            => 500.00,
                'deed_number'     => '310105009912',
                'estimated_value' => 4600000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'بناء شخصي وتشطيبات فندقية ومجلس خارجي.',
            ],

            // Assignment 7 (Agricultural)
            [
                'assignment_id'   => $assignment7->id,
                'property_number' => 'PROP-012',
                'name'            => 'مزرعة نخيل نموذجية ببريدة مجهزة بآبار ارتوازية',
                'type'            => 'land',
                'usage_classification' => 'agricultural',
                'city'            => 'بريدة',
                'district'        => 'البصر',
                'area'            => 45000.00,
                'deed_number'     => '640108001122',
                'estimated_value' => 8500000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'تحتوي على أكثر من 800 نخلة سكري خلاص وبئرين ارتوازيين وسكن عمال.',
            ],
            [
                'assignment_id'   => $assignment7->id,
                'property_number' => 'PROP-013',
                'name'            => 'أرض زراعية وبساتين حمضيات بالخرج',
                'type'            => 'land',
                'usage_classification' => 'agricultural',
                'city'            => 'الخرج',
                'district'        => 'الهياثم',
                'area'            => 60000.00,
                'deed_number'     => '640108001123',
                'estimated_value' => 11000000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'مجهزة برشاش محوري ومستودع تبريد للمحاصيل وشبكة ري حديثة.',
            ],

            // Assignment 8 (Mixed Use)
            [
                'assignment_id'   => $assignment8->id,
                'property_number' => 'PROP-014',
                'name'            => 'برج تجاري وسكني استثماري بطريق الملك فهد',
                'type'            => 'building',
                'usage_classification' => 'mixed_use',
                'city'            => 'الرياض',
                'district'        => 'الصحافة',
                'area'            => 3800.00,
                'deed_number'     => '310105009940',
                'estimated_value' => 35000000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'يتكون من معارض تجارية في الأرضي ومكاتب وشقق سكنية بالأدوار العليا.',
            ],
            [
                'assignment_id'   => $assignment8->id,
                'property_number' => 'PROP-015',
                'name'            => 'أرض متعددة الاستخدامات على الواجهة البحرية بالخبر',
                'type'            => 'land',
                'usage_classification' => 'mixed_use',
                'city'            => 'الخبر',
                'district'        => 'الكورنيش',
                'area'            => 4200.00,
                'deed_number'     => '530307009830',
                'estimated_value' => 19500000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'موقع مميز مرخص لبناء مجمع فندقي وسكني وتجاري بارتفاعات مفتوحة.',
            ],

            // Archived property for testing trash & restore
            [
                'assignment_id'   => $assignment1->id,
                'property_number' => 'PROP-016',
                'name'            => 'عقار ملغي ومؤرشف للاختبار بحي الياسمين',
                'type'            => 'villa',
                'usage_classification' => 'residential',
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

            // =========================================================================
            // UNASSIGNED PROPERTIES (assignment_id = null)
            // Essential for testing assignment creation category filter & Next button
            // =========================================================================

            // Unassigned: "other" classification (MUST appear in ALL assignment categories)
            [
                'assignment_id'   => null,
                'property_number' => 'PROP-017',
                'name'            => 'مرفق خدمات عامة ومركز تدريب متعدد الأغراض',
                'type'            => 'other',
                'usage_classification' => 'other',
                'city'            => 'الرياض',
                'district'        => 'الملز',
                'area'            => 1200.00,
                'deed_number'     => '310105009951',
                'estimated_value' => 3200000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'عقار مستقل غير مرتبط بإسناد بتصنيف "أخرى" يظهر في كافة تصنيفات الإسنادات.',
            ],
            [
                'assignment_id'   => null,
                'property_number' => 'PROP-018',
                'name'            => 'محطة خدمات ومواقف سيارات مساندة بجدة',
                'type'            => 'other',
                'usage_classification' => 'other',
                'city'            => 'جدة',
                'district'        => 'الروضة',
                'area'            => 1500.00,
                'deed_number'     => '420206001295',
                'estimated_value' => 4100000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'مرفق مساند مستقل بتصنيف "أخرى" لاختبار الظهور الدائم في خطوات إنشاء الإسناد.',
            ],

            // Unassigned: "residential" classification
            [
                'assignment_id'   => null,
                'property_number' => 'PROP-019',
                'name'            => 'شقة سكنية عائلية مستقلة بحي الملقا',
                'type'            => 'apartment',
                'usage_classification' => 'residential',
                'city'            => 'الرياض',
                'district'        => 'الملقا',
                'area'            => 185.00,
                'deed_number'     => '310105009952',
                'estimated_value' => 1250000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'شقة مستقلة جاهزة للربط بإسناد سكني.',
            ],
            [
                'assignment_id'   => null,
                'property_number' => 'PROP-020',
                'name'            => 'فيلا دوبلكس مستقلة بحي النرجس',
                'type'            => 'villa',
                'usage_classification' => 'residential',
                'city'            => 'الرياض',
                'district'        => 'النرجس',
                'area'            => 420.00,
                'deed_number'     => '310105009953',
                'estimated_value' => 3900000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'فيلا سكنية مستقلة غير مرتبطة بإسناد.',
            ],

            // Unassigned: "commercial" classification
            [
                'assignment_id'   => null,
                'property_number' => 'PROP-021',
                'name'            => 'مركز تجاري وصالات عرض بحي الصحافة',
                'type'            => 'building',
                'usage_classification' => 'commercial',
                'city'            => 'الرياض',
                'district'        => 'الصحافة',
                'area'            => 2100.00,
                'deed_number'     => '310105009954',
                'estimated_value' => 16500000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'مبنى تجاري مستقل يظهر عند اختيار تصنيف الإسناد تجاري.',
            ],
            [
                'assignment_id'   => null,
                'property_number' => 'PROP-022',
                'name'            => 'أرض تجارية على طريق الملك فهد بالدمام',
                'type'            => 'land',
                'usage_classification' => 'commercial',
                'city'            => 'الدمام',
                'district'        => 'الشاطئ الغربي',
                'area'            => 3500.00,
                'deed_number'     => '530307009851',
                'estimated_value' => 14000000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'أرض تجارية استثمارية مستقلة جاهزة للربط.',
            ],

            // Unassigned: "agricultural" classification
            [
                'assignment_id'   => null,
                'property_number' => 'PROP-023',
                'name'            => 'مزرعة أعلاف وأشجار زيتون بعنيزة',
                'type'            => 'land',
                'usage_classification' => 'agricultural',
                'city'            => 'عنيزة',
                'district'        => 'الوهلان',
                'area'            => 52000.00,
                'deed_number'     => '640108001130',
                'estimated_value' => 6800000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'مزرعة مستقلة تظهر عند اختيار تصنيف زراعي.',
            ],
            [
                'assignment_id'   => null,
                'property_number' => 'PROP-024',
                'name'            => 'أرض زراعية استثمارية بمحاور ري بالخرج',
                'type'            => 'land',
                'usage_classification' => 'agricultural',
                'city'            => 'الخرج',
                'district'        => 'السلمية',
                'area'            => 75000.00,
                'deed_number'     => '640108001131',
                'estimated_value' => 9200000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'أرض زراعية غير مرتبطة بإسناد.',
            ],

            // Unassigned: "industrial" classification
            [
                'assignment_id'   => null,
                'property_number' => 'PROP-025',
                'name'            => 'مستودع وهناجر تخزين بحي المشاعل',
                'type'            => 'warehouse',
                'usage_classification' => 'industrial',
                'city'            => 'الرياض',
                'district'        => 'المشاعل',
                'area'            => 6500.00,
                'deed_number'     => '310105009955',
                'estimated_value' => 15000000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'مستودع صناعي مستقل يظهر عند اختيار تصنيف صناعي.',
            ],
            [
                'assignment_id'   => null,
                'property_number' => 'PROP-026',
                'name'            => 'أرض صناعية مطورة بالمدينة الصناعية بجدة',
                'type'            => 'land',
                'usage_classification' => 'industrial',
                'city'            => 'جدة',
                'district'        => 'المدينة الصناعية الأولى',
                'area'            => 4800.00,
                'deed_number'     => '420206001296',
                'estimated_value' => 8900000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'أرض صناعية مستقلة جاهزة للربط.',
            ],

            // Unassigned: "mixed_use" classification
            [
                'assignment_id'   => null,
                'property_number' => 'PROP-027',
                'name'            => 'عمارة تجارية وسكنية بحي الياسمين',
                'type'            => 'building',
                'usage_classification' => 'mixed_use',
                'city'            => 'الرياض',
                'district'        => 'الياسمين',
                'area'            => 2800.00,
                'deed_number'     => '310105009956',
                'estimated_value' => 21000000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'مبنى متعدد الاستخدامات مستقل يظهر عند اختيار متعدد الاستخدامات.',
            ],
            [
                'assignment_id'   => null,
                'property_number' => 'PROP-028',
                'name'            => 'أرض متعددة الاستخدامات بحي الحزام الذهبي',
                'type'            => 'land',
                'usage_classification' => 'mixed_use',
                'city'            => 'الخبر',
                'district'        => 'الحزام الذهبي',
                'area'            => 3900.00,
                'deed_number'     => '530307009852',
                'estimated_value' => 17200000.00,
                'data_status'     => 'completed',
                'status'          => 'available',
                'is_active'       => true,
                'notes'           => 'أرض استثمارية متعددة الاستخدامات مستقلة جاهزة للربط.',
            ],
        ];

        $createdProperties = [];
        foreach ($propertiesData as $pData) {
            $isArchived = ($pData['property_number'] === 'PROP-016');
            $prop = Property::withTrashed()->updateOrCreate(
                ['property_number' => $pData['property_number']],
                array_merge($pData, ['created_by' => $userId])
            );

            // Boundaries
            if ($prop->boundaries()->count() === 0) {
                $prop->boundaries()->createMany([
                    ['direction' => 'north', 'border_type' => 'شارع', 'border_description' => 'شارع رئيسي عرض 30 متر', 'length' => 40.0, 'sort_order' => 0],
                    ['direction' => 'south', 'border_type' => 'قطعة', 'border_description' => 'رقم القطعة المجاورة 1450', 'length' => 40.0, 'sort_order' => 1],
                    ['direction' => 'east', 'border_type' => 'قطعة', 'border_description' => 'رقم القطعة المجاورة 1452', 'length' => 25.0, 'sort_order' => 2],
                    ['direction' => 'west', 'border_type' => 'شارع', 'border_description' => 'شارع فرعي عرض 15 متر', 'length' => 25.0, 'sort_order' => 3],
                ]);
            }

            // Owners
            if ($prop->owners()->count() === 0) {
                $prop->owners()->create([
                    'owner_name' => 'شركة أصول للتنمية العقارية والاستثمار',
                    'national_id_or_cr' => '1010998877',
                    'sort_order' => 0,
                ]);
            }

            // Units (for buildings)
            if ($prop->type->value === 'building' && $prop->units()->count() === 0) {
                $prop->units()->createMany([
                    [
                        'unit_number' => 'U-01',
                        'unit_type' => PropertyUnitType::SHOP,
                        'area' => 120.00,
                        'current_use' => PropertyUnitUsage::COMMERCIAL,
                        'status' => PropertyUnitStatus::RENTED,
                        'sort_order' => 1,
                    ],
                    [
                        'unit_number' => 'U-02',
                        'unit_type' => PropertyUnitType::SHOP,
                        'area' => 150.00,
                        'current_use' => PropertyUnitUsage::COMMERCIAL,
                        'status' => PropertyUnitStatus::AVAILABLE,
                        'sort_order' => 2,
                    ],
                    [
                        'unit_number' => 'U-03',
                        'unit_type' => PropertyUnitType::APARTMENT,
                        'area' => 200.00,
                        'current_use' => PropertyUnitUsage::RESIDENTIAL,
                        'status' => PropertyUnitStatus::AVAILABLE,
                        'sort_order' => 3,
                    ],
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
        $sampleBrochurePath = 'auctions/attachments/sample_brochure.pdf';
        if (! Storage::disk('tenant_public')->exists($sampleBrochurePath)) {
            Storage::disk('tenant_public')->put($sampleBrochurePath, File::get(database_path('seeders/fixtures/auction-demo-document.pdf')));
        }
        if (! Storage::disk('tenant_public')->exists($sampleReportPath)) {
            $sourceFile = public_path('static/samples/auction-templates/brochure-1.png');
            if (File::exists($sourceFile)) {
                Storage::disk('tenant_public')->put($sampleReportPath, File::get($sourceFile));
            }
        }

        $auctionsData = [
            // Auction 1: Electronic, Upcoming/Active, Residential
            [
                'assignment_id'            => $assignment1->id,
                'name'                     => 'مزاد درة الرياض',
                'legacy_name'              => 'مزاد درة الرياض العقاري الإلكتروني',
                'description'              => 'فرص عقارية سكنية كبرى في أرقى أحياء العاصمة الرياض تحت إشراف مركز الإسناد والتصفية (إنفاذ).',
                'start_date'               => Carbon::now()->addDays(5)->format('Y-m-d'),
                'start_time'               => '16:00:00',
                'end_date'                 => Carbon::now()->addDays(8)->format('Y-m-d'),
                'end_time'                 => '21:00:00',
                'type'                     => 'electronic',
                'category'                 => 'residential',
                'city'                     => 'الرياض',
                'expected_properties_count'=> 2,
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
                'is_active'                => true,
                'properties_to_sync'       => [
                    'PROP-001' => ['sort_order' => 1, 'result_status' => 'pending', 'opening_price' => 2500000.00, 'sale_price' => null, 'participants_count' => null, 'bids_count' => null],
                    'PROP-002' => ['sort_order' => 2, 'result_status' => 'pending', 'opening_price' => 4500000.00, 'sale_price' => null, 'participants_count' => null, 'bids_count' => null],
                ],
            ],

            // Auction 2: In-Person, Commercial
            [
                'assignment_id'            => $assignment2->id,
                'name'                     => 'مزاد جوهرة الغربية',
                'legacy_name'              => 'مزاد جوهرة الغربية الحضوري',
                'description'              => 'مزاد علني حضوري لبيع مبانٍ وأراضٍ تجارية واستثمارية بارزة بمدينة جدة.',
                'start_date'               => Carbon::now()->addDays(15)->format('Y-m-d'),
                'start_time'               => '18:30:00',
                'end_date'                 => Carbon::now()->addDays(16)->format('Y-m-d'),
                'end_time'                 => '22:00:00',
                'type'                     => 'in_person',
                'category'                 => 'commercial',
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
                'is_active'                => true,
                'properties_to_sync'       => [
                    'PROP-006' => ['sort_order' => 1, 'result_status' => 'pending', 'opening_price' => 15000000.00, 'sale_price' => null, 'participants_count' => null, 'bids_count' => null],
                    'PROP-007' => ['sort_order' => 2, 'result_status' => 'pending', 'opening_price' => 18000000.00, 'sale_price' => null, 'participants_count' => null, 'bids_count' => null],
                ],
            ],

            // Auction 3: Hybrid, Residential
            [
                'assignment_id'            => $assignment4->id,
                'name'                     => 'مزاد فلل النرجس',
                'legacy_name'              => 'مزاد فلل النرجس الهجين',
                'description'              => 'مزاد هجين يجمع بين الحضور الشخصي والمزايدة الإلكترونية المتزامنة.',
                'start_date'               => Carbon::now()->addDays(25)->format('Y-m-d'),
                'start_time'               => '17:00:00',
                'end_date'                 => Carbon::now()->addDays(27)->format('Y-m-d'),
                'end_time'                 => '21:00:00',
                'type'                     => 'hybrid',
                'category'                 => 'residential',
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
                'is_active'                => true,
                'properties_to_sync'       => [
                    'PROP-010' => ['sort_order' => 1, 'result_status' => 'pending', 'opening_price' => 3500000.00, 'sale_price' => null, 'participants_count' => null, 'bids_count' => null],
                    'PROP-011' => ['sort_order' => 2, 'result_status' => 'pending', 'opening_price' => 3900000.00, 'sale_price' => null, 'participants_count' => null, 'bids_count' => null],
                ],
            ],

            // Auction 4: Completed / Closed Auction with Sold & Unsold properties
            [
                'assignment_id'            => $assignment3->id,
                'name'                     => 'مزاد الشرقية الصناعي',
                'legacy_name'              => 'مزاد مساهمات الشرقية الصناعية المنتهي',
                'description'              => 'مزاد صناعي مكتمل تم إغلاقه وتوثيق نتائج الترسية والمزايدات.',
                'start_date'               => Carbon::now()->subMonths(2)->format('Y-m-d'),
                'start_time'               => '16:00:00',
                'end_date'                 => Carbon::now()->subMonths(1)->format('Y-m-d'),
                'end_time'                 => '21:00:00',
                'type'                     => 'electronic',
                'category'                 => 'industrial',
                'city'                     => 'الدمام',
                'expected_properties_count'=> 2,
                'supervising_entity'       => 'الهيئة العامة لعقارات الدولة',
                'project_manager_name'     => 'م. فيصل الغامدي',
                'platform_name'            => 'منصة سومات',
                'platform_url'             => 'https://soum.sa',
                'report_image'             => $sampleReportPath,
                'marketing_data'           => [
                    'campaigns' => [
                        ['platform' => 'Instagram', 'reach' => 45000, 'impressions' => 82000, 'engagements' => 3800, 'clicks' => 1450],
                    ],
                ],
                'is_active'                => false,
                'properties_to_sync'       => [
                    'PROP-008' => [
                        'sort_order'         => 1,
                        'result_status'      => 'sold',
                        'opening_price'      => 28000000.00,
                        'sale_price'         => 34500000.00,
                        'participants_count' => 24,
                        'bids_count'         => 67,
                        'buyer_name'         => 'شركة النماء للاستثمار والتطوير',
                        'result_notes'       => 'تمت الترسية ودفع العربون بنجاح.',
                    ],
                    'PROP-009' => [
                        'sort_order'         => 2,
                        'result_status'      => 'unsold',
                        'opening_price'      => 10000000.00,
                        'sale_price'         => null,
                        'participants_count' => 6,
                        'bids_count'         => 14,
                        'unsold_reason'      => 'عدم وصول المزايدات للسعر التحفظي المحدد من المحكمة.',
                    ],
                ],
            ],

            // Auction 5: Archived for testing trash
            [
                'assignment_id'            => $assignment1->id,
                'name'                     => 'مزاد مؤرشف تجريبي للاختبار',
                'description'              => 'مزاد تمت أرشفته لتجربة جدول سلة المحذوفات.',
                'start_date'               => Carbon::now()->subMonths(1)->format('Y-m-d'),
                'start_time'               => '16:00:00',
                'end_date'                 => Carbon::now()->subDays(25)->format('Y-m-d'),
                'end_time'                 => '20:00:00',
                'type'                     => 'electronic',
                'category'                 => 'residential',
                'city'                     => 'الرياض',
                'expected_properties_count'=> 0,
                'supervising_entity'       => 'مركز الإسناد والتصفية',
                'project_manager_name'     => 'مدير تجريبي',
                'platform_name'            => 'منصة سومات',
                'platform_url'             => 'https://soum.sa',
                'report_image'             => null,
                'is_active'                => false,
                'properties_to_sync'       => [],
            ],
        ];

        $seededAuctionIds = [];
        foreach ($auctionsData as $aucData) {
            $isArchived = ($aucData['name'] === 'مزاد مؤرشف تجريبي للاختبار');
            $propsToSync = $aucData['properties_to_sync'];
            $names = array_filter([$aucData['name'], $aucData['legacy_name'] ?? null]);
            unset($aucData['properties_to_sync'], $aucData['legacy_name']);

            $auction = Auction::withTrashed()->whereIn('name', $names)->first() ?? new Auction;
            $auction->fill(array_merge($aucData, ['created_by' => $userId]));
            $auction->save();

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
                        $newStatus = ($pivotAttrs['result_status'] ?? null) === 'sold'
                            ? 'sold'
                            : 'assigned_to_auction';
                        $prop->update(['status' => $newStatus]);
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
                    'file_path'  => $sampleBrochurePath,
                    'file_type'  => 'application/pdf',
                    'file_size'  => Storage::disk('tenant_public')->size($sampleBrochurePath),
                    'category'   => 'brochure',
                    'channels'   => ['web', 'social', 'whatsapp'],
                ]
            );

            if (! $isArchived) {
                if (! app(AuctionReportService::class)->requestGeneration($auction, (string) tenant('id'))) {
                    throw new \RuntimeException('Could not prepare seeded auction reports for auction '.$auction->id.'.');
                }
                $seededAuctionIds[] = $auction->id;
            }

            if ($isArchived && ! $auction->trashed()) {
                $auction->delete();
            }
        }
        $this->call(AuctionWorkflowScenariosSeeder::class);
        foreach (Auction::whereKey($seededAuctionIds)->get() as $auction) {
            $this->command?->info('Generating auction documents: '.$auction->name);
            app(SeededAuctionDocuments::class)->generate($auction);
        }
    }
}
