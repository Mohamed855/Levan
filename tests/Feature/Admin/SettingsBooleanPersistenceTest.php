<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Actions\Admin\Settings\UpdateSettingsAction;
use App\Models\Admin\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SettingsBooleanPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_false_boolean_values_replace_previously_enabled_settings(): void
    {
        Setting::set('general', ['maintenance_mode' => true, 'office_name' => 'LOT']);
        Setting::set('security', ['force_two_factor_auth' => true]);

        app(UpdateSettingsAction::class)->execute([
            'maintenance_mode' => false,
            'force_two_factor_auth' => false,
        ]);

        $this->assertFalse(Setting::get('general')['maintenance_mode']);
        $this->assertSame('LOT', Setting::get('general')['office_name']);
        $this->assertFalse(Setting::get('security')['force_two_factor_auth']);
    }
}
