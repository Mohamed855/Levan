<?php

declare(strict_types=1);

namespace Database\Seeders\tenant;

use App\Models\Tenants\Assignments\IssuingEntity;
use App\Models\Tenants\UserMangment\User\User;
use Database\Seeders\Support\DemoAssets;
use Illuminate\Database\Seeder;

class IssuingEntitySeeder extends Seeder
{
    /**
     * Official fixture images for each issuing entity.
     * Keys match files in database/seeders/fixtures/issuing-entities/.
     * Falls back to the generic placeholder when the file is absent.
     */
    private const ENTITY_FIXTURES = [
        'مركز الإسناد والتصفية (إنفاذ)'     => 'issuing-entities/infath.png',
        'محكمة التنفيذ بالرياض'              => 'issuing-entities/courts.png',
        'محكمة التنفيذ بجدة'                 => 'issuing-entities/courts.png',
        'محكمة التنفيذ بالدمام'              => 'issuing-entities/courts.png',
        'محكمة الأحوال الشخصية بالرياض'     => 'issuing-entities/courts.png',
        'الهيئة العامة لعقارات الدولة'       => 'issuing-entities/rega.png',
        'وزارة العدل'                        => 'issuing-entities/moj.png',
        'لجنة الإفلاس'                       => null,   // no official fixture yet
        'محكمة التنفيذ بمكة المكرمة'         => 'issuing-entities/courts.png',
        'الهيئة العامة للأوقاف'              => null,   // no official fixture yet
    ];

    /**
     * Run the database seeds for test tenant issuing entities.
     */
    public function run(): void
    {
        $firstUserId = User::first()?->id;
        $isTest      = (bool) config('seeding.test_seeders_enabled', false);

        $entities = [
            ['name' => 'مركز الإسناد والتصفية (إنفاذ)',     'is_active' => true],
            ['name' => 'محكمة التنفيذ بالرياض',              'is_active' => true],
            ['name' => 'محكمة التنفيذ بجدة',                 'is_active' => true],
            ['name' => 'محكمة التنفيذ بالدمام',              'is_active' => true],
            ['name' => 'محكمة الأحوال الشخصية بالرياض',     'is_active' => true],
            ['name' => 'الهيئة العامة لعقارات الدولة',       'is_active' => true],
            ['name' => 'وزارة العدل',                        'is_active' => true],
            ['name' => 'لجنة الإفلاس',                       'is_active' => true],
            ['name' => 'محكمة التنفيذ بمكة المكرمة',         'is_active' => true],
            ['name' => 'الهيئة العامة للأوقاف',              'is_active' => true],
        ];

        foreach ($entities as $entity) {
            $imageAttributes = [];

            if ($isTest) {
                $fixture   = self::ENTITY_FIXTURES[$entity['name']] ?? null;
                $targetPath = 'issuing-entities/'.md5($entity['name']).'.png';

                $imageAttributes['image'] = $fixture !== null
                    ? DemoAssets::namedImage($fixture, $targetPath)
                    : DemoAssets::image('issuing-entities/demo-'.md5($entity['name']).'.jpg');
            }

            IssuingEntity::withTrashed()->updateOrCreate(
                ['name' => $entity['name']],
                array_merge([
                    'is_active'  => $entity['is_active'],
                    'created_by' => $firstUserId,
                    'deleted_at' => null,
                ], $imageAttributes)
            );
        }
    }
}
