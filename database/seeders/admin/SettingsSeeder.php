<?php

namespace Database\Seeders\admin;

use App\Models\Admin\Settings\Settings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Settings::updateOrCreate(
            ['id' => Settings::query()->value('id') ?? 1],
            [
                'image'       => 'default-office.png',
                'office_name' => 'لوت LOT',
                'logo_text'   => 'لوت LOT',
                'office_address' => 'الرياض، طريق الأمير محمد بن عبدالعزيز، حي السليمانية، 12242', 'phone_number' => '0500181100', 'email' => 'info@e-tec.sa',
                'facebook_url' => 'https://www.facebook.com/', 'x_url' => 'https://x.com/', 'linkedin_url' => 'https://www.linkedin.com/',
                'google_maps_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d29046.883712398553!2d46.67226!3d24.70876!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3e2f0302c3848b11%3A0xc31fa4a20bde1573!2sAl%20Sulaimaniyah%2C%20Riyadh%20Saudi%20Arabia!5e0!3m2!1sar!2ssa!4v1710000000000!5m2!1sar!2ssa',
            ]
        );
    }
}
