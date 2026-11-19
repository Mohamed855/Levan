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
        'الهيئة العامة لعقارات الدولة' => 'issuing-entities/spga.png',
        'وزارة العدل' => 'issuing-entities/moj.png',
        'لجنة الإفلاس' => 'issuing-entities/bankruptcy.png',
        'الهيئة العامة للأوقاف' => 'issuing-entities/awqaf.png',
    ];

    /**
     * Run the database seeds for tenant issuing entities.
     */
    public function run(): void
    {
        $firstUserId = User::first()?->id;

        foreach (self::ENTITY_FIXTURES as $name => $fixture) {
            $imageAttributes = [];
            $targetPath = 'issuing-entities/'.md5($name).'.png';
            $storedPath = DemoAssets::namedImage($fixture, $targetPath);
            if ($storedPath !== null) {
                $imageAttributes['image'] = $storedPath;
            }

            IssuingEntity::withTrashed()->updateOrCreate(
                ['name' => $name],
                array_merge([
                    'is_active' => true,
                    'created_by' => $firstUserId,
                    'deleted_at' => null,
                ], $imageAttributes)
            );
        }

    }
}
