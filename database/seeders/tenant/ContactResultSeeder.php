<?php

namespace Database\Seeders\tenant;

use App\Models\Tenants\GeneralSetting\ContactResult\ContactResult;
use Illuminate\Database\Seeder;

class ContactResultSeeder extends Seeder
{
    public function run(): void
    {
        $contactResults = [
            ['name' => 'تم السداد بالكامل', 'category' => 'positive', 'status' => true],
            ['name' => 'سداد جزئي', 'category' => 'positive', 'status' => true],
            ['name' => 'وعد بالسداد', 'category' => 'positive', 'status' => true],
            ['name' => 'معاودة الاتصال', 'category' => 'positive', 'status' => true],
            ['name' => 'طلب مستندات', 'category' => 'positive', 'status' => true],
            ['name' => 'لا يوجد رد', 'category' => 'negative', 'status' => true],
            ['name' => 'الرقم مغلق', 'category' => 'negative', 'status' => true],
            ['name' => 'الرقم خاطئ', 'category' => 'negative', 'status' => true],
            ['name' => 'رفض السداد', 'category' => 'negative', 'status' => true],
            ['name' => 'إنكار المديونية', 'category' => 'negative', 'status' => true],
        ];

        foreach ($contactResults as $result) {
            ContactResult::updateOrCreate(
                ['name' => $result['name']],
                [
                    'category' => $result['category'],
                    'status' => $result['status'],
                ]
            );
        }
    }
}
