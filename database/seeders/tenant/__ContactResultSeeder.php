<?php

namespace Database\Seeders\tenant;

use App\Models\Tenants\GeneralSetting\ContactResult\ContactResult;
use Illuminate\Database\Seeder;

class __ContactResultSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $contactResults = [
            [
                'id' => 1,
                'name' => 'تم السداد بالكامل',
                'category' => 'positive',
                'status' => true,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 2,
                'name' => 'سداد جزئي',
                'category' => 'positive',
                'status' => true,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'id' => 3,
                'name' => 'وعد بالسداد',
                'category' => 'positive',
                'status' => true,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 4,
                'name' => 'معاودة الاتصال',
                'category' => 'positive',
                'status' => true,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'id' => 5,
                'name' => 'لا يوجد رد',
                'category' => 'negative',
                'status' => true,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 6,
                'name' => 'الرقم خارج الخدمة',
                'category' => 'negative',
                'status' => true,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 7,
                'name' => 'الرقم غير صحيح',
                'category' => 'negative',
                'status' => true,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 8,
                'name' => 'الهاتف مغلق',
                'category' => 'negative',
                'status' => true,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],



            [
                'id' => 9,
                'name' => 'طلب مستندات',
                'category' => 'other',
                'status' => true,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'id' => 10,
                'name' => 'معترض',
                'category' => 'negative',
                'status' => true,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],

            [
                'id' => 11,
                'name' => 'حذف مسترد',
                'category' => 'negative',
                'status' => true,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],


            [
                'id' => 12,
                'name' => 'عدم التواصل مؤقتاً',
                'category' => 'other',
                'status' => true,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
        ];

        ContactResult::insert($contactResults);
    }
}
