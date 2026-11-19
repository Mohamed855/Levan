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
     */
    private const ENTITY_FIXTURES = [
        'مركز الإسناد والتصفية (إنفاذ)' => 'issuing-entities/infath.png',
        'محكمة التنفيذ بالرياض' => 'issuing-entities/courts.png',
        'محكمة التنفيذ بجدة' => 'issuing-entities/courts.png',
        'محكمة التنفيذ بالدمام' => 'issuing-entities/courts.png',
        'محكمة الأحوال الشخصية بالرياض' => 'issuing-entities/courts.png',
        'الهيئة العامة لعقارات الدولة' => 'issuing-entities/spga.png',
        'وزارة العدل' => 'issuing-entities/moj.png',
        'لجنة الإفلاس' => 'issuing-entities/bankruptcy.png',
        'محكمة التنفيذ بمكة المكرمة' => 'issuing-entities/courts.png',
        'الهيئة العامة للأوقاف' => 'issuing-entities/awqaf.png',
        'كتابة العدل الأولى' => 'issuing-entities/moj.png',
    ];

    /**
     * Run the database seeds for tenant issuing entities.
     */
    public function run(): void
    {
        $firstUserId = User::first()?->id;

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
            $fixture = self::ENTITY_FIXTURES[$entity['name']] ?? null;

            if ($fixture !== null) {
                $targetPath = 'issuing-entities/'.md5($entity['name']).'.png';
                $storedPath = DemoAssets::namedImage($fixture, $targetPath);
                if ($storedPath !== null) {
                    $imageAttributes['image'] = $storedPath;
                }
            }

            IssuingEntity::withTrashed()->updateOrCreate(
                ['name' => $entity['name']],
                array_merge([
                    'is_active' => $entity['is_active'],
                    'created_by' => $firstUserId,
                    'deleted_at' => null,
                ], $imageAttributes)
            );
        }

        $notary = IssuingEntity::withTrashed()->where('name', 'كتابة العدل الأولى')->first();
        if ($notary && empty($notary->image)) {
            $notaryPath = 'issuing-entities/'.md5('كتابة العدل الأولى').'.png';
            $notaryStored = DemoAssets::namedImage(self::ENTITY_FIXTURES['كتابة العدل الأولى'], $notaryPath);
            if ($notaryStored !== null) {
                $notary->update(['image' => $notaryStored]);
            }
        }
    }
}
