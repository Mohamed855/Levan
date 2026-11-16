<?php

declare(strict_types=1);

namespace Database\Seeders\tenant;

use App\Enums\Tenants\Assignments\AssignmentAttachmentCategory;
use App\Enums\Tenants\Assignments\AssignmentCategory;
use App\Enums\Tenants\Auctions\AuctionCategory;
use App\Enums\Tenants\Auctions\AuctionType;
use App\Enums\Tenants\Properties\PropertyAttachmentCategory;
use App\Enums\Tenants\Properties\PropertyBoundaryDirection;
use App\Enums\Tenants\Properties\PropertyDataStatus;
use App\Enums\Tenants\Properties\PropertyOwnershipType;
use App\Enums\Tenants\Properties\PropertyType;
use App\Enums\Tenants\Properties\PropertyUnitStatus;
use App\Enums\Tenants\Properties\PropertyUnitType;
use App\Enums\Tenants\Properties\PropertyUnitUsage;
use App\Enums\Tenants\Properties\PropertyUsageClassification;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Assignments\IssuingEntity;
use App\Models\Tenants\AuctionClosings\AuctionClosing;
use App\Models\Tenants\AuctionEstablishments\AuctionEstablishmentRequest;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionAttachment;
use App\Models\Tenants\MarketingCampaigns\MarketingCampaign;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\Properties\PropertyBoundary;
use App\Models\Tenants\Properties\PropertyOwner;
use App\Models\Tenants\Properties\PropertyUnit;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Auctions\Reports\AuctionReportDataBuilder;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TestTenantDataCycleSeeder extends Seeder
{
    private const DEMO_ROOT = 'D:/_demos';

    private const LEVAN_ROOT = 'D:/_demos/Levan';

    private ?int $userId = null;

    private array $users = [];

    private array $issuingEntities = [];

    public function run(): void
    {
        ini_set('memory_limit', '1024M');

        if (! tenancy()->initialized) {
            $tenant = Tenant::find('test');
            if (! $tenant) {
                $this->command?->error("Tenant 'test' not found!");

                return;
            }
            tenancy()->initialize($tenant);
        }

        $this->command?->info('Initializing Test Tenant Data Cycle Seeder for tenant ['.tenant('id').']...');

        $this->initUsersAndEntities();
        $this->cleanupExistingData();

        $assignments = $this->createAssignments();
        $properties = $this->createProperties($assignments);
        $this->createAuctions($assignments, $properties);

        $this->command?->info('Successfully seeded complete auction, property, assignment, and campaign lifecycle data!');
    }

    private function initUsersAndEntities(): void
    {
        $allUsers = User::all();
        $this->users = $allUsers->all();
        $this->userId = $allUsers->first()?->id ?? 1;

        $entities = IssuingEntity::all();
        foreach ($entities as $entity) {
            $this->issuingEntities[$entity->name] = $entity->id;
        }
    }

    private function cleanupExistingData(): void
    {
        $this->command?->info('Cleaning up previous auction and property data...');

        DB::table('auction_properties')->delete();
        DB::table('auction_attachments')->delete();
        DB::table('auction_marketing_campaigns')->delete();
        DB::table('auction_closings')->delete();
        DB::table('auction_establishment_requests')->delete();
        DB::table('auction_form_generations')->delete();
        DB::table('auction_report_generations')->delete();
        DB::table('auctions')->delete();

        DB::table('property_boundaries')->delete();
        DB::table('property_owners')->delete();
        DB::table('property_units')->delete();
        DB::table('attachments')->where('attachable_type', Property::class)->delete();
        DB::table('properties')->delete();

        DB::table('attachments')->where('attachable_type', Assignment::class)->delete();
        DB::table('assignments')->delete();
    }

    /**
     * Copy a file from source to tenant_public disk and return the relative path.
     */
    private function copyPublicFile(string $sourcePath, string $targetRelativePath): string
    {
        if (file_exists($sourcePath)) {
            $content = file_get_contents($sourcePath);
            Storage::disk('tenant_public')->put($targetRelativePath, $content);

            return $targetRelativePath;
        }

        if (file_exists(self::DEMO_ROOT.'/images.jpg')) {
            Storage::disk('tenant_public')->put($targetRelativePath, file_get_contents(self::DEMO_ROOT.'/images.jpg'));

            return $targetRelativePath;
        }

        Storage::disk('tenant_public')->put($targetRelativePath, 'placeholder');

        return $targetRelativePath;
    }

    /**
     * Copy a file to tenant_private disk for establishment documents.
     */
    private function copyPrivateFile(string $sourcePath, string $targetRelativePath): string
    {
        if (file_exists($sourcePath)) {
            $content = file_get_contents($sourcePath);
            Storage::disk('tenant_private')->put($targetRelativePath, $content);

            return $targetRelativePath;
        }

        if (file_exists(self::DEMO_ROOT.'/sample.pdf')) {
            Storage::disk('tenant_private')->put($targetRelativePath, file_get_contents(self::DEMO_ROOT.'/sample.pdf'));

            return $targetRelativePath;
        }

        Storage::disk('tenant_private')->put($targetRelativePath, '%PDF-1.4 demo');

        return $targetRelativePath;
    }

    private function getIssuingEntityId(string $name): int
    {
        if (isset($this->issuingEntities[$name])) {
            return $this->issuingEntities[$name];
        }

        $entity = IssuingEntity::firstOrCreate(
            ['name' => $name],
            ['is_active' => true, 'created_by' => $this->userId]
        );
        $this->issuingEntities[$name] = $entity->id;

        return $entity->id;
    }

    private function createAssignments(): array
    {
        $this->command?->info('Seeding assignments with documents and media...');

        $configs = [
            'infath_riyadh' => [
                'assignment_number' => 'ASN-2026-00101',
                'name' => 'إسناد تصفية أصول تركة الرياض الشرقية',
                'entity' => 'مركز الإسناد والتصفية (إنفاذ)',
                'city' => 'الرياض',
                'district' => 'حطين والنرجس',
                'category' => [AssignmentCategory::RESIDENTIAL->value, AssignmentCategory::COMMERCIAL->value],
                'start_date' => Carbon::parse('2026-01-10'),
                'end_date' => Carbon::parse('2026-12-31'),
                'notes' => 'إسناد رسمي بموجب قرار الدائرة القضائية لبيع عقارات سكنية وتجارية مميزة.',
                'image' => self::LEVAN_ROOT.'/Social Mediea/Design 1-Squere.png',
                'letter' => self::LEVAN_ROOT.'/دليل التسويق والهوية البصرية ( انفاذ )/القوالب الإضافية - مواقع التواصل الإجتماعي.pdf',
            ],
            'court_makkah' => [
                'assignment_number' => 'ASN-2026-00204',
                'name' => 'إسناد محكمة التنفيذ بمكة المكرمة - عقارات الحرم',
                'entity' => 'محكمة التنفيذ بمكة المكرمة',
                'city' => 'مكة المكرمة',
                'district' => 'العزيزية والشبيكة',
                'category' => [AssignmentCategory::COMMERCIAL->value, AssignmentCategory::MIXED_USE->value],
                'start_date' => Carbon::parse('2026-02-01'),
                'end_date' => Carbon::parse('2026-12-30'),
                'notes' => 'إسناد لتصفية عقارات تجارية فندقية بالقرب من المنطقة المركزية للحرم المكي.',
                'image' => self::LEVAN_ROOT.'/Social Mediea/Design 2-Squere.png',
                'letter' => self::LEVAN_ROOT.'/نموذج اقفال المزاد للهيئة العامة للعقار/طلب اقفال المزاد .pdf',
            ],
            'state_dammam' => [
                'assignment_number' => 'ASN-2026-00315',
                'name' => 'إسناد عقارات الدولة بالمنطقة الشرقية',
                'entity' => 'الهيئة العامة لعقارات الدولة',
                'city' => 'الدمام',
                'district' => 'المدينة الصناعية الثانية',
                'category' => [AssignmentCategory::INDUSTRIAL->value, AssignmentCategory::COMMERCIAL->value],
                'start_date' => Carbon::parse('2026-03-01'),
                'end_date' => Carbon::parse('2026-11-30'),
                'notes' => 'إسناد بيع وتأجير مستودعات وأراضي صناعية ومرافق لوجستية بالمنطقة الشرقية.',
                'image' => self::LEVAN_ROOT.'/Social Mediea/Design 3-Squere.png',
                'letter' => self::LEVAN_ROOT.'/نموذج للوحات على عين العقار/العقار 1 مقاس 3ف6.pdf',
            ],
            'court_kharj' => [
                'assignment_number' => 'ASN-2026-00428',
                'name' => 'إسناد محكمة التنفيذ بالرياض - أصول ومزارع الخرج',
                'entity' => 'محكمة التنفيذ بالرياض',
                'city' => 'الخرج',
                'district' => 'الهياثم والرفيعة',
                'category' => [AssignmentCategory::AGRICULTURAL->value, AssignmentCategory::RESIDENTIAL->value],
                'start_date' => Carbon::parse('2026-02-15'),
                'end_date' => Carbon::parse('2026-12-15'),
                'notes' => 'إسناد تصفية مزارع نموذجية ومنتجعات سكنية بمحافظة الخرج.',
                'image' => self::LEVAN_ROOT.'/Social Mediea/Design 4-Squere.png',
                'letter' => self::DEMO_ROOT.'/sample.pdf',
            ],
            'court_jeddah' => [
                'assignment_number' => 'ASN-2026-00539',
                'name' => 'إسناد محكمة التنفيذ بجدة - أملاك الشاطئ وأبحر',
                'entity' => 'محكمة التنفيذ بجدة',
                'city' => 'جدة',
                'district' => 'الشاطئ وأبحر الشمالية',
                'category' => [AssignmentCategory::RESIDENTIAL->value, AssignmentCategory::MIXED_USE->value],
                'start_date' => Carbon::parse('2026-01-20'),
                'end_date' => Carbon::parse('2026-12-25'),
                'notes' => 'إسناد تصفية عقارات سكنية وقصور مطلة على واجهة البحر بجدة.',
                'image' => self::LEVAN_ROOT.'/Social Mediea/Design 5-Squere.png',
                'letter' => self::LEVAN_ROOT.'/نموذج للوحات على عين العقار/العقار 6 مقاس 5ف10.pdf',
            ],
        ];

        $assignments = [];
        foreach ($configs as $key => $conf) {
            $entityId = $this->getIssuingEntityId($conf['entity']);

            $assignment = Assignment::create([
                'assignment_number' => $conf['assignment_number'],
                'name' => $conf['name'],
                'issuing_entity_id' => $entityId,
                'issuing_entity' => $conf['entity'],
                'city' => $conf['city'],
                'district' => $conf['district'],
                'category' => $conf['category'],
                'start_date' => $conf['start_date'],
                'end_date' => $conf['end_date'],
                'notes' => $conf['notes'],
                'is_active' => true,
                'created_by' => $this->userId,
            ]);

            // Copy and attach media
            $imagePath = $this->copyPublicFile($conf['image'], "demo/assignments/{$assignment->id}/image.png");
            $letterPath = $this->copyPublicFile($conf['letter'], "demo/assignments/{$assignment->id}/letter.pdf");

            $assignment->attachments()->create([
                'disk' => 'tenant_public',
                'file_path' => $imagePath,
                'original_name' => basename($conf['image']),
                'mime_type' => 'image/png',
                'extension' => 'png',
                'file_size' => Storage::disk('tenant_public')->size($imagePath),
                'category' => AssignmentAttachmentCategory::IMAGE->value,
            ]);

            $assignment->attachments()->create([
                'disk' => 'tenant_public',
                'file_path' => $letterPath,
                'original_name' => basename($conf['letter']),
                'mime_type' => 'application/pdf',
                'extension' => 'pdf',
                'file_size' => Storage::disk('tenant_public')->size($letterPath),
                'category' => AssignmentAttachmentCategory::LETTER->value,
            ]);

            $assignments[$key] = $assignment;
        }

        return $assignments;
    }

    private function createProperties(array $assignments): array
    {
        $this->command?->info('Seeding diverse properties with boundaries, owners, units, and documents...');

        $propertyConfigs = [
            // Assignment: infath_riyadh
            [
                'assignment_key' => 'infath_riyadh',
                'property_number' => 'PROP-RYD-001',
                'name' => 'فيلا فاخرة مودرن بحي حطين النموذجي',
                'type' => PropertyType::VILLA,
                'usage_classification' => PropertyUsageClassification::RESIDENTIAL,
                'ownership_type' => PropertyOwnershipType::SOLE_OWNERSHIP,
                'short_description' => 'فيلا بتصميم عصري راقي مع مسبح ومصعد وحديقة وموقف سيارات.',
                'city' => 'الرياض',
                'district' => 'حطين',
                'administrative_region' => 'منطقة الرياض',
                'plan_number' => '2450/أ',
                'parcel_number' => '114',
                'area' => 650.00,
                'deed_number' => '310105009871',
                'deed_date' => '2022-04-12',
                'issuing_authority' => 'كتابة العدل الأولى بالرياض',
                'estimated_value' => 4500000.00,
                'main_image' => self::LEVAN_ROOT.'/Social Mediea/Design 1-Squere.png',
                'aerial_image' => self::LEVAN_ROOT.'/A4-Horizintal/Design 1-A4-H.png',
                'gallery' => [
                    self::LEVAN_ROOT.'/A4-Virtical/Design 3-A4-V.png',
                ],
                'owners' => [
                    ['name' => 'عبد العزيز بن محمد الراجحي', 'id' => '1029384756'],
                ],
                'units' => [
                    ['unit_number' => 'V-01', 'type' => PropertyUnitType::VILLA, 'area' => 650.00, 'current_use' => PropertyUnitUsage::RESIDENTIAL, 'status' => PropertyUnitStatus::AVAILABLE],
                ],
            ],
            [
                'assignment_key' => 'infath_riyadh',
                'property_number' => 'PROP-RYD-002',
                'name' => 'شقة تمليك فاخرة مطلة بحي النرجس',
                'type' => PropertyType::APARTMENT,
                'usage_classification' => PropertyUsageClassification::RESIDENTIAL,
                'ownership_type' => PropertyOwnershipType::SOLE_OWNERSHIP,
                'short_description' => 'شقة دور كامل بإطلالة مميزة وموقف خاص ضمن مجمع سكني حديث.',
                'city' => 'الرياض',
                'district' => 'النرجس',
                'administrative_region' => 'منطقة الرياض',
                'plan_number' => '3120/ب',
                'parcel_number' => '58',
                'area' => 220.00,
                'deed_number' => '310108004523',
                'deed_date' => '2023-01-18',
                'issuing_authority' => 'كتابة العدل الأولى بالرياض',
                'estimated_value' => 1250000.00,
                'main_image' => self::LEVAN_ROOT.'/Social Mediea/Design 2-Squere.png',
                'aerial_image' => self::LEVAN_ROOT.'/A4-Horizintal/Design 2-A4-H.png',
                'gallery' => [
                    self::LEVAN_ROOT.'/A4-Virtical/Design 4-A4-V.png',
                ],
                'owners' => [
                    ['name' => 'سارة بنت عبد الله السالم', 'id' => '1098765432'],
                ],
                'units' => [
                    ['unit_number' => 'APT-04', 'type' => PropertyUnitType::APARTMENT, 'area' => 220.00, 'current_use' => PropertyUnitUsage::RESIDENTIAL, 'status' => PropertyUnitStatus::AVAILABLE],
                ],
            ],
            [
                'assignment_key' => 'infath_riyadh',
                'property_number' => 'PROP-RYD-003',
                'name' => 'عمارة تجارية سكنية على طريق الملك فهد',
                'type' => PropertyType::BUILDING,
                'usage_classification' => PropertyUsageClassification::COMMERCIAL,
                'ownership_type' => PropertyOwnershipType::CORPORATE,
                'short_description' => 'عمارة تجارية مكونة من 4 معارض تجارية و8 مكاتب إدارية بدخل ممتاز.',
                'city' => 'الرياض',
                'district' => 'العليا',
                'administrative_region' => 'منطقة الرياض',
                'plan_number' => '1022/م',
                'parcel_number' => '15',
                'area' => 1100.00,
                'deed_number' => '310103007612',
                'deed_date' => '2021-08-05',
                'issuing_authority' => 'كتابة العدل الأولى بالرياض',
                'estimated_value' => 12000000.00,
                'main_image' => self::LEVAN_ROOT.'/Social Mediea/Design 3-Squere.png',
                'aerial_image' => self::LEVAN_ROOT.'/A4-Horizintal/Design 3-A4-H.png',
                'gallery' => [
                    self::LEVAN_ROOT.'/A4-Virtical/Design 5-A4-V.png',
                ],
                'owners' => [
                    ['name' => 'شركة أوتاد النماء العقارية', 'id' => '7001928374'],
                ],
                'units' => [
                    ['unit_number' => 'SH-01', 'type' => PropertyUnitType::SHOP, 'area' => 120.00, 'current_use' => PropertyUnitUsage::COMMERCIAL, 'status' => PropertyUnitStatus::RENTED],
                    ['unit_number' => 'SH-02', 'type' => PropertyUnitType::SHOP, 'area' => 120.00, 'current_use' => PropertyUnitUsage::COMMERCIAL, 'status' => PropertyUnitStatus::RENTED],
                    ['unit_number' => 'OF-101', 'type' => PropertyUnitType::OTHER, 'area' => 200.00, 'current_use' => PropertyUnitUsage::COMMERCIAL, 'status' => PropertyUnitStatus::AVAILABLE],
                ],
            ],
            [
                'assignment_key' => 'infath_riyadh',
                'property_number' => 'PROP-RYD-004',
                'name' => 'أرض تجارية استثمارية بمخطط الخير',
                'type' => PropertyType::LAND,
                'usage_classification' => PropertyUsageClassification::COMMERCIAL,
                'ownership_type' => PropertyOwnershipType::SHARED_OWNERSHIP,
                'short_description' => 'أرض تجارية على شارعين رئيسيين عرض 36م بموقع واعد للاستثمار.',
                'city' => 'الرياض',
                'district' => 'الخير',
                'administrative_region' => 'منطقة الرياض',
                'plan_number' => '3312/أ',
                'parcel_number' => '890',
                'area' => 1500.00,
                'deed_number' => '310109002341',
                'deed_date' => '2023-05-20',
                'issuing_authority' => 'كتابة العدل الأولى بالرياض',
                'estimated_value' => 3200000.00,
                'main_image' => self::LEVAN_ROOT.'/Social Mediea/Design 4-Squere.png',
                'aerial_image' => self::LEVAN_ROOT.'/A4-Horizintal/Design 4-A4-H.png',
                'gallery' => [
                    self::LEVAN_ROOT.'/A4-Virtical/Design 6-A4-V.png',
                ],
                'owners' => [
                    ['name' => 'فهد بن سلطان المطيري', 'id' => '1048291038'],
                    ['name' => 'بندر بن سلطان المطيري', 'id' => '1048291046'],
                ],
                'units' => [],
            ],

            // Assignment: court_makkah
            [
                'assignment_key' => 'court_makkah',
                'property_number' => 'PROP-MAK-001',
                'name' => 'برج فندقي وتجاري ريادي بحي العزيزية',
                'type' => PropertyType::BUILDING,
                'usage_classification' => PropertyUsageClassification::COMMERCIAL,
                'ownership_type' => PropertyOwnershipType::CORPORATE,
                'short_description' => 'مبنى فندقي مرخص للحج والعمرة 10 أدوار مع مواقف وقاعات استقبال.',
                'city' => 'مكة المكرمة',
                'district' => 'العزيزية',
                'administrative_region' => 'منطقة مكة المكرمة',
                'plan_number' => '780/ع',
                'parcel_number' => '203',
                'area' => 1850.00,
                'deed_number' => '420101008761',
                'deed_date' => '2020-11-10',
                'issuing_authority' => 'كتابة العدل بمكة المكرمة',
                'estimated_value' => 18500000.00,
                'main_image' => self::LEVAN_ROOT.'/Social Mediea/Design 5-Squere.png',
                'aerial_image' => self::LEVAN_ROOT.'/A4-Horizintal/Design 5-A4-H.png',
                'gallery' => [
                    self::LEVAN_ROOT.'/A4-Virtical/Design 7-A4-V.png',
                ],
                'owners' => [
                    ['name' => 'شركة ضيافة المشاعر للاستثمار', 'id' => '7002938471'],
                ],
                'units' => [
                    ['unit_number' => 'HTL-G', 'type' => PropertyUnitType::SHOP, 'area' => 300.00, 'current_use' => PropertyUnitUsage::COMMERCIAL, 'status' => PropertyUnitStatus::AVAILABLE],
                ],
            ],
            [
                'assignment_key' => 'court_makkah',
                'property_number' => 'PROP-MAK-002',
                'name' => 'عمارة سكنية تجارية استثمارية بالشبيكة',
                'type' => PropertyType::BUILDING,
                'usage_classification' => PropertyUsageClassification::MIXED_USE,
                'ownership_type' => PropertyOwnershipType::WAQF,
                'short_description' => 'عمارة وقفية 6 أدوار موقع استراتيجي مطلة على شوارع حيوية.',
                'city' => 'مكة المكرمة',
                'district' => 'الشبيكة',
                'administrative_region' => 'منطقة مكة المكرمة',
                'plan_number' => '440/ش',
                'parcel_number' => '94',
                'area' => 820.00,
                'deed_number' => '420104001928',
                'deed_date' => '2019-03-14',
                'issuing_authority' => 'كتابة العدل بمكة المكرمة',
                'estimated_value' => 9500000.00,
                'main_image' => self::LEVAN_ROOT.'/Social Mediea/Design 6-Squere.png',
                'aerial_image' => self::LEVAN_ROOT.'/A4-Horizintal/Design 6-A4-H.png',
                'gallery' => [
                    self::LEVAN_ROOT.'/A4-Virtical/Design 8-A4-V.png',
                ],
                'owners' => [
                    ['name' => 'ناظر وقف صالح بن إبراهيم الحكيم', 'id' => '1019283746'],
                ],
                'units' => [],
            ],
            [
                'assignment_key' => 'court_makkah',
                'property_number' => 'PROP-MAK-003',
                'name' => 'مجمع تجاري ومحلات حيوية بالعتيبية',
                'type' => PropertyType::BUILDING,
                'usage_classification' => PropertyUsageClassification::COMMERCIAL,
                'ownership_type' => PropertyOwnershipType::SOLE_OWNERSHIP,
                'short_description' => 'مجمع تجاري يضم 6 محلات ومعارض تجارية بواجهات زجاجية واسعة.',
                'city' => 'مكة المكرمة',
                'district' => 'العتيبية',
                'administrative_region' => 'منطقة مكة المكرمة',
                'plan_number' => '310/ت',
                'parcel_number' => '45',
                'area' => 980.00,
                'deed_number' => '420108006129',
                'deed_date' => '2021-07-22',
                'issuing_authority' => 'كتابة العدل بمكة المكرمة',
                'estimated_value' => 7800000.00,
                'main_image' => self::LEVAN_ROOT.'/Social Mediea/Design 7-Squere.png',
                'aerial_image' => self::LEVAN_ROOT.'/A4-Horizintal/Design 7-A4-H.png',
                'gallery' => [
                    self::LEVAN_ROOT.'/A4-Virtical/Design 9-A4-V.png',
                ],
                'owners' => [
                    ['name' => 'منصور بن حميد القرشي', 'id' => '1038475629'],
                ],
                'units' => [],
            ],

            // Assignment: state_dammam
            [
                'assignment_key' => 'state_dammam',
                'property_number' => 'PROP-DMM-001',
                'name' => 'مستودع لوجستي مجهز بالصناعية الثانية',
                'type' => PropertyType::WAREHOUSE,
                'usage_classification' => PropertyUsageClassification::INDUSTRIAL,
                'ownership_type' => PropertyOwnershipType::CORPORATE,
                'short_description' => 'مستودع بمواصفات الدفاع المدني وأرصفة تحميل ومكاتب إدارية مكيفة.',
                'city' => 'الدمام',
                'district' => 'الصناعية الثانية',
                'administrative_region' => 'المنطقة الشرقية',
                'plan_number' => 'IND-88',
                'parcel_number' => '12',
                'area' => 3500.00,
                'deed_number' => '230101004928',
                'deed_date' => '2022-09-01',
                'issuing_authority' => 'كتابة العدل بالدمام',
                'estimated_value' => 6500000.00,
                'main_image' => self::LEVAN_ROOT.'/Social Mediea/Design 8-Squere.png',
                'aerial_image' => self::LEVAN_ROOT.'/A4-Horizintal/Design 8-A4-H.png',
                'gallery' => [
                    self::LEVAN_ROOT.'/A4-Virtical/Design 10-A4-V.png',
                ],
                'owners' => [
                    ['name' => 'شركة المسار اللوجستي للصناعة', 'id' => '7003847561'],
                ],
                'units' => [],
            ],
            [
                'assignment_key' => 'state_dammam',
                'property_number' => 'PROP-DMM-002',
                'name' => 'أرض صناعية مخصصة للمصانع والورش',
                'type' => PropertyType::LAND,
                'usage_classification' => PropertyUsageClassification::INDUSTRIAL,
                'ownership_type' => PropertyOwnershipType::SOLE_OWNERSHIP,
                'short_description' => 'أرض صناعية مسورة بالكامل مجهزة بالكهرباء والماء ومدخل شاحنات واسع.',
                'city' => 'الدمام',
                'district' => 'الصناعية الأولى',
                'administrative_region' => 'المنطقة الشرقية',
                'plan_number' => 'IND-12',
                'parcel_number' => '304',
                'area' => 4200.00,
                'deed_number' => '230103001847',
                'deed_date' => '2023-02-11',
                'issuing_authority' => 'كتابة العدل بالدمام',
                'estimated_value' => 5200000.00,
                'main_image' => self::LEVAN_ROOT.'/Social Mediea/Design 9-Squere.png',
                'aerial_image' => self::LEVAN_ROOT.'/A4-Horizintal/Design 9-A4-H.png',
                'gallery' => [
                    self::LEVAN_ROOT.'/A4-Virtical/Design 3-A4-V.png',
                ],
                'owners' => [
                    ['name' => 'تركي بن عبد المحسن الفوزان', 'id' => '1058392019'],
                ],
                'units' => [],
            ],

            // Assignment: court_kharj
            [
                'assignment_key' => 'court_kharj',
                'property_number' => 'PROP-KHJ-001',
                'name' => 'مزرعة نموذجية منتجة مع فيلا ريفية وبئر ارتوازي',
                'type' => PropertyType::LAND,
                'usage_classification' => PropertyUsageClassification::AGRICULTURAL,
                'ownership_type' => PropertyOwnershipType::SOLE_OWNERSHIP,
                'short_description' => 'مزرعة نخيل تضم أكثر من 400 نخلة خلاص وسكن عمال وبئر مرخص.',
                'city' => 'الخرج',
                'district' => 'الهياثم',
                'administrative_region' => 'منطقة الرياض',
                'plan_number' => 'AGR-40',
                'parcel_number' => '7',
                'area' => 45000.00,
                'deed_number' => '320102008491',
                'deed_date' => '2020-06-15',
                'issuing_authority' => 'كتابة العدل بالخرج',
                'estimated_value' => 3800000.00,
                'main_image' => self::LEVAN_ROOT.'/Social Mediea/Design 10-Squere.png',
                'aerial_image' => self::LEVAN_ROOT.'/A4-Horizintal/Design 10-A4-H.png',
                'gallery' => [
                    self::LEVAN_ROOT.'/A4-Virtical/Design 4-A4-V.png',
                ],
                'owners' => [
                    ['name' => 'سعد بن عبد الرحمن العتيبي', 'id' => '1067283910'],
                ],
                'units' => [],
            ],
            [
                'assignment_key' => 'court_kharj',
                'property_number' => 'PROP-KHJ-002',
                'name' => 'قصر ريفي واستراحة عائلية متكاملة',
                'type' => PropertyType::PALACE,
                'usage_classification' => PropertyUsageClassification::RESIDENTIAL,
                'ownership_type' => PropertyOwnershipType::SOLE_OWNERSHIP,
                'short_description' => 'قصر ريفي فاخر بمسطحات خضراء ومجالس ضيافة ومسبح أولمبي.',
                'city' => 'الخرج',
                'district' => 'الرفيعة',
                'administrative_region' => 'منطقة الرياض',
                'plan_number' => '512/خ',
                'parcel_number' => '19',
                'area' => 7500.00,
                'deed_number' => '320105001239',
                'deed_date' => '2021-12-05',
                'issuing_authority' => 'كتابة العدل بالخرج',
                'estimated_value' => 5400000.00,
                'main_image' => self::LEVAN_ROOT.'/Social Mediea/Design 11-Squere.png',
                'aerial_image' => self::LEVAN_ROOT.'/A4-Horizintal/Design 1-A4-H.png',
                'gallery' => [
                    self::LEVAN_ROOT.'/A4-Virtical/Design 5-A4-V.png',
                ],
                'owners' => [
                    ['name' => 'خالد بن ناصر الدوسري', 'id' => '1078291047'],
                ],
                'units' => [],
            ],

            // Assignment: court_jeddah
            [
                'assignment_key' => 'court_jeddah',
                'property_number' => 'PROP-JED-001',
                'name' => 'قصر بحري فاخر بواجهة شاطئية مباشرة بأبحر',
                'type' => PropertyType::PALACE,
                'usage_classification' => PropertyUsageClassification::RESIDENTIAL,
                'ownership_type' => PropertyOwnershipType::SOLE_OWNERSHIP,
                'short_description' => 'قصر فخم على البحر مباشرة برصيف يخوت ومصعد بانورامي وحدائق غناء.',
                'city' => 'جدة',
                'district' => 'أبحر الشمالية',
                'administrative_region' => 'منطقة مكة المكرمة',
                'plan_number' => '801/ب',
                'parcel_number' => '33',
                'area' => 2800.00,
                'deed_number' => '410103009482',
                'deed_date' => '2022-01-25',
                'issuing_authority' => 'كتابة العدل الأولى بجدة',
                'estimated_value' => 22000000.00,
                'main_image' => self::LEVAN_ROOT.'/Social Mediea/Design 1-Squere.png',
                'aerial_image' => self::LEVAN_ROOT.'/A4-Horizintal/Design 2-A4-H.png',
                'gallery' => [
                    self::LEVAN_ROOT.'/A4-Virtical/Design 6-A4-V.png',
                ],
                'owners' => [
                    ['name' => 'هشام بن طلال العمودي', 'id' => '1082938475'],
                ],
                'units' => [],
            ],
            [
                'assignment_key' => 'court_jeddah',
                'property_number' => 'PROP-JED-002',
                'name' => 'شقة بنتهاوس دوبلكس بحي الشاطئ',
                'type' => PropertyType::APARTMENT,
                'usage_classification' => PropertyUsageClassification::RESIDENTIAL,
                'ownership_type' => PropertyOwnershipType::SOLE_OWNERSHIP,
                'short_description' => 'بنتهاوس مع تراس خاص واسع وإطلالة بحرية كاملة وتشطيبات فندقية.',
                'city' => 'جدة',
                'district' => 'الشاطئ',
                'administrative_region' => 'منطقة مكة المكرمة',
                'plan_number' => '650/ش',
                'parcel_number' => '8',
                'area' => 380.00,
                'deed_number' => '410108003719',
                'deed_date' => '2023-04-10',
                'issuing_authority' => 'كتابة العدل الأولى بجدة',
                'estimated_value' => 3500000.00,
                'main_image' => self::LEVAN_ROOT.'/Social Mediea/Design 2-Squere.png',
                'aerial_image' => self::LEVAN_ROOT.'/A4-Horizintal/Design 3-A4-H.png',
                'gallery' => [
                    self::LEVAN_ROOT.'/A4-Virtical/Design 7-A4-V.png',
                ],
                'owners' => [
                    ['name' => 'ريما بنت كمال البسام', 'id' => '1091827364'],
                ],
                'units' => [],
            ],
            [
                'assignment_key' => 'court_jeddah',
                'property_number' => 'PROP-JED-003',
                'name' => 'مستودع تجاري وهناجر تخزين بحي الخمرة',
                'type' => PropertyType::WAREHOUSE,
                'usage_classification' => PropertyUsageClassification::INDUSTRIAL,
                'ownership_type' => PropertyOwnershipType::CORPORATE,
                'short_description' => 'هناجر تخزين جاف واسعة مع ساحة تفريغ وميزان بسكول للشاحنات.',
                'city' => 'جدة',
                'district' => 'الخمرة',
                'administrative_region' => 'منطقة مكة المكرمة',
                'plan_number' => 'IND-KHM',
                'parcel_number' => '51',
                'area' => 5000.00,
                'deed_number' => '410109001428',
                'deed_date' => '2021-10-18',
                'issuing_authority' => 'كتابة العدل الأولى بجدة',
                'estimated_value' => 6800000.00,
                'main_image' => self::LEVAN_ROOT.'/Social Mediea/Design 3-Squere.png',
                'aerial_image' => self::LEVAN_ROOT.'/A4-Horizintal/Design 4-A4-H.png',
                'gallery' => [
                    self::LEVAN_ROOT.'/A4-Virtical/Design 8-A4-V.png',
                ],
                'owners' => [
                    ['name' => 'شركة الموانئ والمستودعات العالمية', 'id' => '7004829103'],
                ],
                'units' => [],
            ],
        ];

        $properties = [];
        $samplePdf = self::DEMO_ROOT.'/sample.pdf';

        foreach ($propertyConfigs as $cfg) {
            $assignment = $assignments[$cfg['assignment_key']];

            $mainImgPath = $this->copyPublicFile($cfg['main_image'], "demo/properties/{$cfg['property_number']}/main.png");
            $aerialImgPath = $this->copyPublicFile($cfg['aerial_image'], "demo/properties/{$cfg['property_number']}/aerial.png");

            $property = Property::create([
                'assignment_id' => $assignment->id,
                'property_number' => $cfg['property_number'],
                'name' => $cfg['name'],
                'type' => $cfg['type'],
                'usage_classification' => $cfg['usage_classification'],
                'ownership_type' => $cfg['ownership_type'],
                'short_description' => $cfg['short_description'],
                'city' => $cfg['city'],
                'district' => $cfg['district'],
                'administrative_region' => $cfg['administrative_region'],
                'plan_number' => $cfg['plan_number'],
                'parcel_number' => $cfg['parcel_number'],
                'google_maps_url' => 'https://maps.google.com/?q=24.7136,46.6753',
                'latitude' => 24.7136000,
                'longitude' => 46.6753000,
                'area' => $cfg['area'],
                'deed_number' => $cfg['deed_number'],
                'deed_date' => $cfg['deed_date'],
                'issuing_authority' => $cfg['issuing_authority'],
                'estimated_value' => $cfg['estimated_value'],
                'data_status' => PropertyDataStatus::COMPLETED,
                'status' => 'available',
                'is_active' => true,
                'main_image' => $mainImgPath,
                'aerial_image' => $aerialImgPath,
                'notes' => 'بيانات العقار مكتملة ومطابقة للصك والمخطط التنظيمي المعتمد.',
                'created_by' => $this->userId,
            ]);

            // Boundaries
            $directions = [
                ['dir' => PropertyBoundaryDirection::NORTH, 'desc' => 'شارع عرض 20 متر', 'type' => 'شارع', 'len' => 30.00],
                ['dir' => PropertyBoundaryDirection::SOUTH, 'desc' => 'القطعة رقم 115 المجاورة', 'type' => 'جار', 'len' => 30.00],
                ['dir' => PropertyBoundaryDirection::EAST, 'desc' => 'ممر مشاة وحديقة عامة', 'type' => 'ممر', 'len' => 25.00],
                ['dir' => PropertyBoundaryDirection::WEST, 'desc' => 'القطعة رقم 113 المجاورة', 'type' => 'جار', 'len' => 25.00],
            ];
            foreach ($directions as $idx => $b) {
                PropertyBoundary::create([
                    'property_id' => $property->id,
                    'direction' => $b['dir'],
                    'border_type' => $b['type'],
                    'border_description' => $b['desc'],
                    'length' => $b['len'],
                    'sort_order' => $idx + 1,
                ]);
            }

            // Owners
            foreach ($cfg['owners'] as $idx => $owner) {
                PropertyOwner::create([
                    'property_id' => $property->id,
                    'owner_name' => $owner['name'],
                    'national_id_or_cr' => $owner['id'],
                    'sort_order' => $idx + 1,
                ]);
            }

            // Units
            foreach ($cfg['units'] as $idx => $u) {
                PropertyUnit::create([
                    'property_id' => $property->id,
                    'unit_number' => $u['unit_number'],
                    'unit_type' => $u['type'],
                    'area' => $u['area'],
                    'current_use' => $u['current_use'],
                    'status' => $u['status'],
                    'sort_order' => $idx + 1,
                ]);
            }

            // Morph Attachments
            $property->attachments()->create([
                'disk' => 'tenant_public',
                'file_path' => $mainImgPath,
                'original_name' => basename($cfg['main_image']),
                'mime_type' => 'image/png',
                'extension' => 'png',
                'file_size' => Storage::disk('tenant_public')->size($mainImgPath),
                'category' => PropertyAttachmentCategory::MAIN_IMAGE->value,
            ]);

            $property->attachments()->create([
                'disk' => 'tenant_public',
                'file_path' => $aerialImgPath,
                'original_name' => basename($cfg['aerial_image']),
                'mime_type' => 'image/png',
                'extension' => 'png',
                'file_size' => Storage::disk('tenant_public')->size($aerialImgPath),
                'category' => PropertyAttachmentCategory::AERIAL_PHOTO->value,
            ]);

            foreach ($cfg['gallery'] as $gIdx => $gSrc) {
                $gPath = $this->copyPublicFile($gSrc, "demo/properties/{$property->id}/gallery_{$gIdx}.png");
                $property->attachments()->create([
                    'disk' => 'tenant_public',
                    'file_path' => $gPath,
                    'original_name' => basename($gSrc),
                    'mime_type' => 'image/png',
                    'extension' => 'png',
                    'file_size' => Storage::disk('tenant_public')->size($gPath),
                    'category' => PropertyAttachmentCategory::GALLERY_IMAGE->value,
                ]);
            }

            // PDF Documents
            $docPdf = $this->copyPublicFile($samplePdf, "demo/properties/{$property->id}/deed.pdf");
            $property->attachments()->create([
                'disk' => 'tenant_public',
                'file_path' => $docPdf,
                'original_name' => 'صك_الملكية_'.$property->property_number.'.pdf',
                'mime_type' => 'application/pdf',
                'extension' => 'pdf',
                'file_size' => Storage::disk('tenant_public')->size($docPdf),
                'category' => PropertyAttachmentCategory::DEED_DOCUMENT->value,
            ]);

            $planPdf = $this->copyPublicFile($samplePdf, "demo/properties/{$property->id}/plan.pdf");
            $property->attachments()->create([
                'disk' => 'tenant_public',
                'file_path' => $planPdf,
                'original_name' => 'المخطط_التنظيمي_'.$property->property_number.'.pdf',
                'mime_type' => 'application/pdf',
                'extension' => 'pdf',
                'file_size' => Storage::disk('tenant_public')->size($planPdf),
                'category' => PropertyAttachmentCategory::CADASTRAL_PLAN->value,
            ]);

            $properties[$cfg['property_number']] = $property;
        }

        return $properties;
    }

    private function createAuctions(array $assignments, array $properties): void
    {
        $this->command?->info('Seeding the 6 auctions with distinct lifecycle stages...');

        $platformEntityId = $this->getIssuingEntityId('مركز الإسناد والتصفية (إنفاذ)');

        // =========================================================================
        // Auction 1:
        // واحد لم يتم تقديم طلبه او عمل حملات تسويقية عليه
        // =========================================================================
        $this->command?->info('1. Creating Auction 1: No establishment request, No marketing campaigns...');
        $auc1 = Auction::create([
            'assignment_id' => $assignments['infath_riyadh']->id,
            'name' => 'مزاد نجد السكني الأول',
            'description' => 'مزاد لبيع فلل وشقق سكنية راقية في أحياء شمال الرياض (حطين والنرجس).',
            'start_date' => Carbon::now()->addDays(14)->toDateString(),
            'start_time' => '16:00:00',
            'end_date' => Carbon::now()->addDays(16)->toDateString(),
            'end_time' => '21:00:00',
            'type' => AuctionType::ELECTRONIC->value,
            'category' => AuctionCategory::RESIDENTIAL->value,
            'city' => 'الرياض',
            'expected_properties_count' => 2,
            'supervising_entity' => 'مركز الإسناد والتصفية (إنفاذ)',
            'project_manager_name' => 'أحمد السعيد',
            'platform_entity_id' => $platformEntityId,
            'platform_name' => 'منصة الدار للمزادات',
            'platform_url' => 'https://aldar.sa/auctions/najd-1',
            'cover_image' => $this->copyPublicFile(self::LEVAN_ROOT.'/Social Mediea/Design 1-Squere.png', 'demo/auctions/1/cover.png'),
            'report_image' => $this->copyPublicFile(self::LEVAN_ROOT.'/A4-Virtical/Design 3-A4-V.png', 'demo/auctions/1/report.png'),
            'notes' => 'المزاد في مرحلة الإعداد والتجهيز الأولي، لم يتم تقديم طلبه ولم تنطلق حملاته التسويقية بعد.',
            'is_active' => true,
            'created_by' => $this->userId,
        ]);

        $auc1Props = [$properties['PROP-RYD-001'], $properties['PROP-RYD-002']];
        foreach ($auc1Props as $idx => $p) {
            $auc1->properties()->attach($p->id, [
                'sort_order' => $idx + 1,
                'opening_price' => $p->estimated_value * 0.8,
                'result_status' => null,
            ]);
            $p->update(['status' => 'assigned_to_auction']);
        }

        // Auction attachment: brochure
        $brochure1 = $this->copyPublicFile(self::LEVAN_ROOT.'/نموذج للوحات على عين العقار/العقار 1 مقاس 3ف6.pdf', "demo/auctions/{$auc1->id}/brochure.pdf");
        AuctionAttachment::create([
            'auction_id' => $auc1->id,
            'file_path' => $brochure1,
            'file_name' => 'بروشور_مزاد_نجد_السكني.pdf',
            'file_type' => 'application/pdf',
            'file_size' => Storage::disk('tenant_public')->size($brochure1),
            'category' => 'auction_doc',
            'report_section' => 'brochure',
            'display_order' => 1,
            'channels' => ['report'],
            'report_metadata' => ['kind' => 'document', 'display_name' => 'البروشور التعريفي للمزاد'],
        ]);

        // =========================================================================
        // Auction 2:
        // واحد لم يتم تقديم طلبه ولكن تم عمل حملات تسويقية عليه
        // =========================================================================
        $this->command?->info('2. Creating Auction 2: No establishment request, Has marketing campaigns...');
        $auc2 = Auction::create([
            'assignment_id' => $assignments['infath_riyadh']->id,
            'name' => 'مزاد درة العاصمة التجاري',
            'description' => 'مزاد تجاري ضخم يضم عمائر وأراضي تجارية استثمارية في قلب العاصمة الرياض.',
            'start_date' => Carbon::now()->addDays(20)->toDateString(),
            'start_time' => '17:00:00',
            'end_date' => Carbon::now()->addDays(22)->toDateString(),
            'end_time' => '22:00:00',
            'type' => AuctionType::HYBRID->value,
            'category' => AuctionCategory::COMMERCIAL->value,
            'city' => 'الرياض',
            'expected_properties_count' => 2,
            'supervising_entity' => 'مركز الإسناد والتصفية (إنفاذ)',
            'project_manager_name' => 'أحمد السعيد',
            'platform_entity_id' => $platformEntityId,
            'platform_name' => 'منصة المزاد الإلكتروني',
            'platform_url' => 'https://mazad.sa/auctions/durra-capital',
            'cover_image' => $this->copyPublicFile(self::LEVAN_ROOT.'/Social Mediea/Design 2-Squere.png', 'demo/auctions/2/cover.png'),
            'report_image' => $this->copyPublicFile(self::LEVAN_ROOT.'/A4-Virtical/Design 4-A4-V.png', 'demo/auctions/2/report.png'),
            'notes' => 'انطلقت الحملة الترويجية للمزاد عبر منصات التواصل، ولم يرفع طلب الإقامة بعد.',
            'is_active' => true,
            'created_by' => $this->userId,
        ]);

        $auc2Props = [$properties['PROP-RYD-003'], $properties['PROP-RYD-004']];
        foreach ($auc2Props as $idx => $p) {
            $auc2->properties()->attach($p->id, [
                'sort_order' => $idx + 1,
                'opening_price' => $p->estimated_value * 0.85,
                'result_status' => null,
            ]);
            $p->update(['status' => 'assigned_to_auction']);
        }

        $this->seedMarketingCampaignsFor($auc2, [
            'x' => ['reach' => 125000, 'impressions' => 340000, 'engagements' => 18400, 'clicks' => 5200, 'posts' => 12],
            'snapchat' => ['reach' => 210000, 'impressions' => 480000, 'views' => 195000, 'clicks' => 8400],
            'tiktok' => ['reach' => 180000, 'views' => 250000, 'engagements' => 14200, 'clicks' => 6100],
            'whatsapp' => ['messages' => 1500, 'delivered' => 1480, 'clicks' => 890, 'total' => 1500],
            'google' => ['impressions' => 95000, 'clicks' => 4300],
        ]);

        // =========================================================================
        // Auction 3:
        // واحد تم تقديم طلبه ولم يتم عمل حملات تسويقية عليه ولم يتم اقفاله
        // =========================================================================
        $this->command?->info('3. Creating Auction 3: Has establishment request, No marketing campaigns, Not closed...');
        $auc3 = Auction::create([
            'assignment_id' => $assignments['court_makkah']->id,
            'name' => 'مزاد أصالة الحجاز العقاري',
            'description' => 'مزاد مميز لبيع عمائر ومجمعات استثمارية في العاصمة المقدسة.',
            'start_date' => Carbon::now()->addDays(25)->toDateString(),
            'start_time' => '16:30:00',
            'end_date' => Carbon::now()->addDays(27)->toDateString(),
            'end_time' => '21:30:00',
            'type' => AuctionType::IN_PERSON->value,
            'category' => AuctionCategory::MIXED_USE->value,
            'city' => 'مكة المكرمة',
            'expected_properties_count' => 2,
            'supervising_entity' => 'محكمة التنفيذ بمكة المكرمة',
            'project_manager_name' => 'خالد القحطاني',
            'platform_entity_id' => $this->getIssuingEntityId('محكمة التنفيذ بمكة المكرمة'),
            'platform_name' => 'قاعة مكة الكبرى للمزادات',
            'platform_url' => null,
            'cover_image' => $this->copyPublicFile(self::LEVAN_ROOT.'/Social Mediea/Design 3-Squere.png', 'demo/auctions/3/cover.png'),
            'report_image' => $this->copyPublicFile(self::LEVAN_ROOT.'/A4-Virtical/Design 5-A4-V.png', 'demo/auctions/3/report.png'),
            'notes' => 'تم رفع طلب إقامة المزاد واعتماده نظامياً، ولم يتم إطلاق الحملات التسويقية بعد.',
            'is_active' => true,
            'created_by' => $this->userId,
        ]);

        $auc3Props = [$properties['PROP-MAK-002'], $properties['PROP-MAK-003']];
        foreach ($auc3Props as $idx => $p) {
            $auc3->properties()->attach($p->id, [
                'sort_order' => $idx + 1,
                'opening_price' => $p->estimated_value * 0.8,
                'result_status' => null,
            ]);
            $p->update(['status' => 'assigned_to_auction']);
        }

        $this->seedEstablishmentRequestFor($auc3, 'EST-2026-003891', true);

        // =========================================================================
        // Auction 4:
        // واحد تم تقديم طلبه و عمل حملات تسويقية عليه ولكن لم يتم اقفاله
        // =========================================================================
        $this->command?->info('4. Creating Auction 4: Has establishment request, Has marketing campaigns, Not closed...');
        $auc4 = Auction::create([
            'assignment_id' => $assignments['state_dammam']->id,
            'name' => 'مزاد واجهة الساحل الشرقي اللوجستي',
            'description' => 'مزاد لبيع وتأجير مستودعات نموذجية وأراضي صناعية بالمدينة الصناعية بالدمام.',
            'start_date' => Carbon::now()->addDays(5)->toDateString(),
            'start_time' => '15:00:00',
            'end_date' => Carbon::now()->addDays(7)->toDateString(),
            'end_time' => '20:00:00',
            'type' => AuctionType::ELECTRONIC->value,
            'category' => AuctionCategory::INDUSTRIAL->value,
            'city' => 'الدمام',
            'expected_properties_count' => 2,
            'supervising_entity' => 'الهيئة العامة لعقارات الدولة',
            'project_manager_name' => 'أحمد السعيد',
            'platform_entity_id' => $platformEntityId,
            'platform_name' => 'منصة إتمام للمزادات',
            'platform_url' => 'https://etmam.sa/auctions/east-coast',
            'cover_image' => $this->copyPublicFile(self::LEVAN_ROOT.'/Social Mediea/Design 4-Squere.png', 'demo/auctions/4/cover.png'),
            'report_image' => $this->copyPublicFile(self::LEVAN_ROOT.'/A4-Virtical/Design 6-A4-V.png', 'demo/auctions/4/report.png'),
            'notes' => 'المزاد معتمد رسمياً وحملاته التسويقية نشطة بالكامل وفي فترة المعاينة والمزايدة.',
            'is_active' => true,
            'created_by' => $this->userId,
        ]);

        $auc4Props = [$properties['PROP-DMM-001'], $properties['PROP-DMM-002']];
        foreach ($auc4Props as $idx => $p) {
            $auc4->properties()->attach($p->id, [
                'sort_order' => $idx + 1,
                'opening_price' => $p->estimated_value * 0.85,
                'result_status' => null,
            ]);
            $p->update(['status' => 'assigned_to_auction']);
        }

        $this->seedEstablishmentRequestFor($auc4, 'EST-2026-004512', true);
        $this->seedMarketingCampaignsFor($auc4, [
            'x' => ['reach' => 190000, 'impressions' => 450000, 'engagements' => 22000, 'clicks' => 6800, 'posts' => 15],
            'snapchat' => ['reach' => 280000, 'impressions' => 620000, 'views' => 280000, 'clicks' => 11000],
            'tiktok' => ['reach' => 240000, 'views' => 390000, 'engagements' => 19500, 'clicks' => 8400],
            'whatsapp' => ['messages' => 2200, 'delivered' => 2180, 'clicks' => 1340, 'total' => 2200],
            'google' => ['impressions' => 140000, 'clicks' => 7600],
        ]);

        // =========================================================================
        // Auction 5:
        // واحد تم تقديم طلبه و عمل حملات تسويقية عليه تم اقفاله جزئيا (عقار واحد بس اتحدد نتيجته)
        // =========================================================================
        $this->command?->info('5. Creating Auction 5: Has establishment request, Has marketing campaigns, Partially closed...');
        $auc5 = Auction::create([
            'assignment_id' => $assignments['court_kharj']->id,
            'name' => 'مزاد ريف الرياض الزراعي والسكني',
            'description' => 'مزاد لتصفية مزارع منتجة وقصور واستراحات ريفية بمحافظة الخرج.',
            'start_date' => Carbon::now()->subDays(5)->toDateString(),
            'start_time' => '16:00:00',
            'end_date' => Carbon::now()->subDays(2)->toDateString(),
            'end_time' => '21:00:00',
            'type' => AuctionType::HYBRID->value,
            'category' => AuctionCategory::AGRICULTURAL->value,
            'city' => 'الخرج',
            'expected_properties_count' => 2,
            'supervising_entity' => 'محكمة التنفيذ بالرياض',
            'project_manager_name' => 'خالد القحطاني',
            'platform_entity_id' => $this->getIssuingEntityId('محكمة التنفيذ بالرياض'),
            'platform_name' => 'منصة مباشر للمزادات',
            'platform_url' => 'https://mubasher.sa/auctions/kharj-reef',
            'cover_image' => $this->copyPublicFile(self::LEVAN_ROOT.'/Social Mediea/Design 5-Squere.png', 'demo/auctions/5/cover.png'),
            'report_image' => $this->copyPublicFile(self::LEVAN_ROOT.'/A4-Virtical/Design 7-A4-V.png', 'demo/auctions/5/report.png'),
            'marketing_data' => [
                'results_narrative' => 'تم ترسية العقار الأول (المزرعة النموذجية) بنجاح وجاري البت في العطاءات المقدمة للعقار الثاني.',
                'board_removal_date' => Carbon::now()->addDays(3)->toDateString(),
            ],
            'notes' => 'تم إقفال المزاد جزئياً، تم بيع وترسية العقار الأول وما زال العقار الثاني تحت الإجراء.',
            'is_active' => true,
            'created_by' => $this->userId,
        ]);

        // Attach 2 properties: 1 sold, 1 pending (null)
        $propKharj1 = $properties['PROP-KHJ-001'];
        $propKharj2 = $properties['PROP-KHJ-002'];

        $auc5->properties()->attach($propKharj1->id, [
            'sort_order' => 1,
            'result_status' => 'sold',
            'opening_price' => 3000000.00,
            'sale_price' => 3650000.00,
            'participants_count' => 14,
            'bids_count' => 38,
            'buyer_name' => 'سعد بن عبد الرحمن العتيبي',
            'result_notes' => 'تمت الترسية بالسعر النهائي بعد منافسة قوية بين 14 مزايد.',
        ]);
        $propKharj1->update(['status' => 'sold']);

        $auc5->properties()->attach($propKharj2->id, [
            'sort_order' => 2,
            'result_status' => null,
            'opening_price' => 4500000.00,
            'participants_count' => 6,
            'bids_count' => 12,
        ]);
        $propKharj2->update(['status' => 'assigned_to_auction']);

        $this->seedEstablishmentRequestFor($auc5, 'EST-2026-005230', true);
        $this->seedMarketingCampaignsFor($auc5, [
            'x' => ['reach' => 160000, 'impressions' => 380000, 'engagements' => 19200, 'clicks' => 5400, 'posts' => 10],
            'snapchat' => ['reach' => 230000, 'impressions' => 510000, 'views' => 210000, 'clicks' => 9200],
            'tiktok' => ['reach' => 195000, 'views' => 290000, 'engagements' => 16100, 'clicks' => 7100],
            'whatsapp' => ['messages' => 1800, 'delivered' => 1760, 'clicks' => 1100, 'total' => 1800],
        ]);

        // Partial closing record
        AuctionClosing::create([
            'auction_id' => $auc5->id,
            'closing_date' => Carbon::now()->subDays(2)->toDateString(),
            'approval_number' => 'EST-2026-005230',
            'saved_at' => Carbon::now()->subDays(1),
            'finalized_snapshot' => null,
            'created_by' => $this->userId,
            'updated_by' => $this->userId,
        ]);

        // =========================================================================
        // Auction 6:
        // اخيرا واحد تم تقديم طلبه و عمل حملات تسويقية عليه تم اقفاله
        // =========================================================================
        $this->command?->info('6. Creating Auction 6: Has establishment request, Has marketing campaigns, Fully closed...');
        $auc6 = Auction::create([
            'assignment_id' => $assignments['court_makkah']->id,
            'name' => 'مزاد جوار الحرم المكي الشامل',
            'description' => 'مزاد عقاري استثنائي لبيع أبراج فندقية ومجمعات تجارية بالقرب من الحرم المكي الشريف.',
            'start_date' => Carbon::now()->subDays(10)->toDateString(),
            'start_time' => '16:00:00',
            'end_date' => Carbon::now()->subDays(8)->toDateString(),
            'end_time' => '22:00:00',
            'type' => AuctionType::ELECTRONIC->value,
            'category' => AuctionCategory::COMMERCIAL->value,
            'city' => 'مكة المكرمة',
            'expected_properties_count' => 3,
            'supervising_entity' => 'مركز الإسناد والتصفية (إنفاذ)',
            'project_manager_name' => 'أحمد السعيد',
            'platform_entity_id' => $platformEntityId,
            'platform_name' => 'منصة الدار للمزادات',
            'platform_url' => 'https://aldar.sa/auctions/jewar-alharam',
            'cover_image' => $this->copyPublicFile(self::LEVAN_ROOT.'/Social Mediea/Design 6-Squere.png', 'demo/auctions/6/cover.png'),
            'report_image' => $this->copyPublicFile(self::LEVAN_ROOT.'/A4-Virtical/Design 8-A4-V.png', 'demo/auctions/6/report.png'),
            'marketing_data' => [
                'results_narrative' => 'شهد المزاد إقبالاً قياسياً ومنافسة ساخنة أسفرت عن بيع عقارين بقيمة إجمالية تجاوزت 28 مليون ريال بنسبة إنجاز متميزة.',
                'board_removal_date' => Carbon::now()->subDays(6)->toDateString(),
            ],
            'notes' => 'تم إقفال المزاد نهائياً واستكمال جميع متطلبات الترسية وإعداد التقارير الختامية.',
            'is_active' => true,
            'created_by' => $this->userId,
        ]);

        $propMak1 = $properties['PROP-MAK-001'];
        $propMak2 = $properties['PROP-MAK-002'];
        $propMak3 = $properties['PROP-MAK-003'];

        // Property 1: Sold
        $auc6->properties()->attach($propMak1->id, [
            'sort_order' => 1,
            'result_status' => 'sold',
            'opening_price' => 15000000.00,
            'sale_price' => 19200000.00,
            'participants_count' => 22,
            'bids_count' => 64,
            'buyer_name' => 'شركة الأفق الفندقية القابضة',
            'result_notes' => 'تمت الترسية بالسعر الأعلى بعد منافسة وتمديد المزاد 3 مرات.',
        ]);
        $propMak1->update(['status' => 'sold']);

        // Property 2: Sold
        $auc6->properties()->attach($propMak2->id, [
            'sort_order' => 2,
            'result_status' => 'sold',
            'opening_price' => 8000000.00,
            'sale_price' => 9750000.00,
            'participants_count' => 16,
            'bids_count' => 45,
            'buyer_name' => 'محمد بن عبد الله الراجحي',
            'result_notes' => 'تمت الترسية بنجاح واعتماد الشيك المصدق وقيمة السعي.',
        ]);
        $propMak2->update(['status' => 'sold']);

        // Property 3: Unsold
        $auc6->properties()->attach($propMak3->id, [
            'sort_order' => 3,
            'result_status' => 'unsold',
            'opening_price' => 7000000.00,
            'sale_price' => null,
            'participants_count' => 5,
            'bids_count' => 11,
            'buyer_name' => null,
            'unsold_reason' => 'لم يصل العطاء الأخير إلى السعر التحفظي المعتمد من قبل دائرة التنفيذ.',
            'result_notes' => 'تقرر إعادة جدولة طرح العقار في مزاد قادم.',
        ]);
        $propMak3->update(['status' => 'available']);

        $this->seedEstablishmentRequestFor($auc6, 'EST-2026-006194', true);
        $this->seedMarketingCampaignsFor($auc6, [
            'x' => ['reach' => 320000, 'impressions' => 780000, 'engagements' => 42000, 'clicks' => 14800, 'posts' => 24],
            'snapchat' => ['reach' => 450000, 'impressions' => 980000, 'views' => 490000, 'clicks' => 21000],
            'tiktok' => ['reach' => 380000, 'views' => 620000, 'engagements' => 31000, 'clicks' => 16500],
            'whatsapp' => ['messages' => 3500, 'delivered' => 3470, 'clicks' => 2400, 'total' => 3500],
            'google' => ['impressions' => 220000, 'clicks' => 12400],
        ]);

        // Fully finalize closing record
        $closing = AuctionClosing::create([
            'auction_id' => $auc6->id,
            'closing_date' => Carbon::now()->subDays(8)->toDateString(),
            'approval_number' => 'EST-2026-006194',
            'saved_at' => Carbon::now()->subDays(7),
            'finalized_snapshot' => null,
            'created_by' => $this->userId,
            'updated_by' => $this->userId,
        ]);

        // Build snapshot
        try {
            $snapshot = app(AuctionReportDataBuilder::class)->build($auc6->fresh());
            $closing->update(['finalized_snapshot' => $snapshot]);
        } catch (\Throwable $e) {
            $this->command?->warn('Could not generate finalized_snapshot for Auction 6: '.$e->getMessage());
        }
    }

    private function seedMarketingCampaignsFor(Auction $auction, array $channels): void
    {
        $platformLabels = [
            'x' => 'منصة إكس (تويتر)',
            'snapchat' => 'سناب شات',
            'tiktok' => 'تيك توك',
            'whatsapp' => 'واتساب بزنس',
            'google' => 'إعلانات جوجل',
        ];

        $designs = [
            self::LEVAN_ROOT.'/Social Mediea/Design 7-Squere.png',
            self::LEVAN_ROOT.'/Social Mediea/Design 8-Squere.png',
            self::LEVAN_ROOT.'/Social Mediea/Design 9-Squere.png',
            self::LEVAN_ROOT.'/Social Mediea/Design 10-Squere.png',
            self::LEVAN_ROOT.'/Social Mediea/Design 11-Squere.png',
        ];

        $idx = 0;
        foreach ($channels as $channel => $metrics) {
            $campaign = MarketingCampaign::create([
                'auction_id' => $auction->id,
                'channel' => $channel,
                'platform_label' => $platformLabels[$channel] ?? $channel,
                'campaign_date' => $auction->start_date ? $auction->start_date->copy()->subDays(3) : Carbon::now()->subDays(2),
                'metrics' => $metrics,
                'url' => "https://{$channel}.com/levan_auctions/status/{$auction->id}",
                'link_label' => "رابط حملة {$auction->name} على {$platformLabels[$channel]}",
                'link_role' => 'marketing_evidence',
                'created_by' => $this->userId,
            ]);

            // Add evidence attachments
            $src = $designs[$idx % count($designs)];
            $idx++;

            $tgt = "demo/auctions/{$auction->id}/campaigns/{$channel}.png";
            $path = $this->copyPublicFile($src, $tgt);

            AuctionAttachment::create([
                'auction_id' => $auction->id,
                'marketing_campaign_id' => $campaign->id,
                'file_path' => $path,
                'file_name' => basename($src),
                'file_type' => 'image/png',
                'file_size' => Storage::disk('tenant_public')->size($path),
                'category' => 'marketing',
                'report_section' => $channel === 'x' ? 'x' : ($channel === 'whatsapp' ? 'whatsapp' : 'social'),
                'display_order' => 1,
                'channels' => ['report'],
                'report_metadata' => [
                    'channel' => $channel,
                    'kind' => 'artwork',
                    'display_name' => "تصميم بوست ترويجي ({$platformLabels[$channel]})",
                ],
            ]);
        }

        // Add general brochure & board docs to auction attachments
        $brochurePath = $this->copyPublicFile(self::LEVAN_ROOT.'/نموذج للوحات على عين العقار/العقار 1 مقاس 3ف6.pdf', "demo/auctions/{$auction->id}/brochure.pdf");
        AuctionAttachment::create([
            'auction_id' => $auction->id,
            'file_path' => $brochurePath,
            'file_name' => 'بروشور_تسويقي_'.$auction->id.'.pdf',
            'file_type' => 'application/pdf',
            'file_size' => Storage::disk('tenant_public')->size($brochurePath),
            'category' => 'auction_doc',
            'report_section' => 'brochure',
            'display_order' => 1,
            'channels' => ['report'],
            'report_metadata' => ['kind' => 'document', 'display_name' => 'بروشور المزاد التسويقي المعتمد'],
        ]);

        $boardPath = $this->copyPublicFile(self::LEVAN_ROOT.'/نموذج للوحات على عين العقار/العقار 6 مقاس 5ف10.pdf', "demo/auctions/{$auction->id}/board_doc.pdf");
        AuctionAttachment::create([
            'auction_id' => $auction->id,
            'file_path' => $boardPath,
            'file_name' => 'لوحة_عين_العقار_'.$auction->id.'.pdf',
            'file_type' => 'application/pdf',
            'file_size' => Storage::disk('tenant_public')->size($boardPath),
            'category' => 'marketing',
            'report_section' => 'board_before',
            'display_order' => 2,
            'channels' => ['report'],
            'report_metadata' => ['kind' => 'photo', 'display_name' => 'لوحة المزاد المنفذة على عين العقار'],
        ]);
    }

    private function seedEstablishmentRequestFor(Auction $auction, string $approvalNumber, bool $approved = true): void
    {
        $auction->loadMissing(['properties.owners', 'assignment']);

        $manager = $this->users[1] ?? $this->users[0] ?? null;
        $auctioneer = $this->users[3] ?? $this->users[0] ?? null;

        $certPdf = self::DEMO_ROOT.'/sample.pdf';

        // Copy private files
        $tenantId = (string) tenant('id');
        $hashDir = 'auction-establishments/'.hash('sha256', $tenantId).'/'.$auction->id;

        $mgrCertPath = $this->copyPrivateFile($certPdf, "{$hashDir}/manager_certificate_0.pdf");
        $aucCertPath = $this->copyPrivateFile($certPdf, "{$hashDir}/auctioneer_certificate_0.pdf");

        $attachmentsData = [
            'manager_certificate' => [
                '0' => [
                    'path' => $mgrCertPath,
                    'name' => 'شهادة_مدير_المزاد.pdf',
                    'mime' => 'application/pdf',
                ],
            ],
            'auctioneer_certificate' => [
                '0' => [
                    'path' => $aucCertPath,
                    'name' => 'شهادة_محرج_المزاد.pdf',
                    'mime' => 'application/pdf',
                ],
            ],
            'deed' => [],
            'valuation' => [],
        ];

        $propertyRows = [];
        foreach ($auction->properties as $prop) {
            $deedPath = $this->copyPrivateFile($certPdf, "{$hashDir}/deed_{$prop->id}.pdf");
            $valPath = $this->copyPrivateFile($certPdf, "{$hashDir}/valuation_{$prop->id}.pdf");

            $attachmentsData['deed'][(string) $prop->id] = [
                'path' => $deedPath,
                'name' => 'صك_العقار_'.$prop->property_number.'.pdf',
                'mime' => 'application/pdf',
            ];
            $attachmentsData['valuation'][(string) $prop->id] = [
                'path' => $valPath,
                'name' => 'تقييم_العقار_'.$prop->property_number.'.pdf',
                'mime' => 'application/pdf',
            ];

            $propertyRows[] = [
                'property_id' => $prop->id,
                'owner_name' => $prop->owners->pluck('owner_name')->implode('، ') ?: 'عبد الله بن عبد العزيز السالم',
                'deed_number' => $prop->deed_number ?: '310105009871',
                'agent_name' => null,
                'agency_number' => null,
                'contract_number' => $prop->assignment?->assignment_number ?: $auction->assignment?->assignment_number ?: 'ASN-2026-001',
                'area' => (string) $prop->area,
                'type' => $prop->type_label,
                'usage' => $prop->usage_classification_label,
                'usage_other' => null,
                'age' => 4,
                'city_district' => trim($prop->city.' / '.$prop->district, ' /'),
                'status' => 'clear',
                'status_other' => null,
                'ownership_share' => 100,
                'disclosure' => 'العقار سليم وخالي من أي موانع أو منازعات نظامية وصالح للمزايدة.',
                'opening_value' => (string) ($prop->pivot->opening_price ?: $prop->estimated_value * 0.8),
                'entry_amount' => '50000',
                'estimated_value' => (string) $prop->estimated_value,
            ];
        }

        $requestData = [
            'organization' => [
                'name' => 'شركة لوت للمزادات العقارية',
                'commercial_registration' => '2222222222',
                'unified_number' => '7001234567',
                'fal_license' => '1200018492',
            ],
            'manager' => [
                'name' => $manager?->name ?: 'أحمد السعيد',
                'identity_number' => '1082736451',
                'mobile' => '0501234567',
                'email' => $manager?->email ?: 'ahmed@lot.com',
            ],
            'auctioneer' => [
                'name' => $auctioneer?->name ?: 'خالد القحطاني',
                'identity_number' => '1093847562',
                'mobile' => '0559876543',
                'email' => $auctioneer?->email ?: 'khalid@lot.com',
            ],
            'auction' => [
                'name' => $auction->name,
                'properties_count' => count($propertyRows),
                'units_count' => 4,
                'method' => $auction->type instanceof \BackedEnum ? $auction->type->value : (string) $auction->type,
                'platform' => $auction->platform_name ?: 'منصة إتمام للمزادات',
                'city' => $auction->city,
                'venue' => $auction->type === 'in_person' ? 'قاعة المزادات الرئيسية' : null,
                'broadcast_url' => $auction->platform_url ?: 'https://auctions.example.com/live/'.$auction->id,
                'start_date' => $auction->start_date?->format('Y-m-d') ?: Carbon::now()->toDateString(),
                'end_date' => $auction->end_date?->format('Y-m-d') ?: Carbon::now()->addDays(2)->toDateString(),
                'start_time' => $auction->start_time ?: '16:00',
                'award_period' => 15,
                'disposition' => 'sale',
            ],
            'funds' => [
                'receipt_method' => 'transfer',
                'receipt_other' => null,
                'receipt_days' => 2,
                'refund_method' => 'transfer',
                'refund_other' => null,
                'refund_days' => 2,
                'entry_type' => 'refundable',
                'safekeeping' => 'حساب الضمان البنكي المعتمد لمزادات إنفاذ',
            ],
            'disclosures' => [
                'disposition_rights' => ['none'],
                'disposition_right_other' => null,
                'real_rights' => 'no',
                'real_rights_details' => null,
                'dispute' => 'no',
                'dispute_details' => null,
                'value_information' => 'no',
                'value_information_details' => null,
                'off_plan' => 'no',
                'off_plan_details' => null,
                'contribution' => 'no',
                'contribution_details' => null,
            ],
            'declaration' => [
                'representative_name' => 'مدير النظام',
            ],
            'submission_date' => Carbon::now()->subDays(12)->format('Y/m/d'),
            'property' => ! empty($propertyRows) ? Arr::except($propertyRows[0], ['property_id']) : [],
            'properties' => $propertyRows,
            'attachments' => $attachmentsData,
            'agents' => [],
        ];

        AuctionEstablishmentRequest::updateOrCreate(
            ['auction_id' => $auction->id],
            [
                'data' => $requestData,
                'submitted_at' => Carbon::now()->subDays(12),
                'approved_at' => $approved ? Carbon::now()->subDays(10) : null,
                'approval_number' => $approved ? $approvalNumber : null,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
                'approved_by' => $approved ? $this->userId : null,
            ]
        );
    }
}
