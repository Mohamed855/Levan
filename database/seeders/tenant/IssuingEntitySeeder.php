<?php

declare(strict_types=1);

namespace Database\Seeders\tenant;

use App\Models\Tenants\Assignments\IssuingEntity;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Seeder;

class IssuingEntitySeeder extends Seeder
{
    /**
     * Run the database seeds for test tenant issuing entities.
     */
    public function run(): void
    {
        $firstUserId = User::first()?->id;

        $entities = [
            [
                'name' => 'مركز الإسناد والتصفية (إنفاذ)',
                'is_active' => true,
            ],
            [
                'name' => 'محكمة التنفيذ بالرياض',
                'is_active' => true,
            ],
            [
                'name' => 'محكمة التنفيذ بجدة',
                'is_active' => true,
            ],
            [
                'name' => 'محكمة التنفيذ بالدمام',
                'is_active' => true,
            ],
            [
                'name' => 'محكمة الأحوال الشخصية بالرياض',
                'is_active' => true,
            ],
            [
                'name' => 'الهيئة العامة لعقارات الدولة',
                'is_active' => true,
            ],
            [
                'name' => 'وزارة العدل',
                'is_active' => true,
            ],
            [
                'name' => 'لجنة الإفلاس',
                'is_active' => true,
            ],
            [
                'name' => 'محكمة التنفيذ بمكة المكرمة',
                'is_active' => true,
            ],
            [
                'name' => 'الهيئة العامة للأوقاف',
                'is_active' => true,
            ],
        ];

        foreach ($entities as $entity) {
            IssuingEntity::withTrashed()->updateOrCreate(
                ['name' => $entity['name']],
                [
                    'is_active' => $entity['is_active'],
                    'created_by' => $firstUserId,
                    'deleted_at' => null,
                ]
            );
        }
    }
}
