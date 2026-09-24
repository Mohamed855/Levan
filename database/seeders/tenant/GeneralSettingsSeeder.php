<?php

namespace Database\Seeders\tenant;

use App\Models\Tenants\GeneralSetting\ContactMethod\SettingsContactMethod;
use App\Models\Tenants\GeneralSetting\Country\SettingsCountry;
use App\Models\Tenants\GeneralSetting\PaymentMethod\SettingsPaymentMethod;
use Illuminate\Database\Seeder;

class GeneralSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $paymentMethods = [
            'تحويل بنكي',
            'نقدي (كاش)',
            'مدى / بطاقة ائتمانية',
            'سداد',
            'شيك مصرفي',
        ];

        foreach ($paymentMethods as $method) {
            SettingsPaymentMethod::updateOrCreate(
                ['name' => $method],
                ['status' => 'active']
            );
        }

        $contactMethods = [
            'اتصال هاتفي',
            'واتساب WhatsApp',
            'رسالة نصية SMS',
            'بريد إلكتروني',
            'زيارة ميدانية',
        ];

        foreach ($contactMethods as $method) {
            SettingsContactMethod::updateOrCreate(
                ['name' => $method],
                ['status' => 'active']
            );
        }

        $countries = [
            ['name' => 'المملكة العربية السعودية', 'code' => 'SA'],
            ['name' => 'جمهورية مصر العربية', 'code' => 'EG'],
            ['name' => 'الإمارات العربية المتحدة', 'code' => 'AE'],
            ['name' => 'الكويت', 'code' => 'KW'],
            ['name' => 'الأردن', 'code' => 'JO'],
        ];

        foreach ($countries as $country) {
            SettingsCountry::updateOrCreate(
                ['name' => $country['name']],
                ['status' => 'active']
            );
        }
    }
}
