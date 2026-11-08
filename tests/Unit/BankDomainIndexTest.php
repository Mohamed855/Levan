<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Bank\Bank;
use App\Models\Admin\ControlDomain\ControlDomain;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class BankDomainIndexTest extends TestCase
{
    public static function settingsIndexes(): array
    {
        return [
            'banks' => ['banks', Bank::class],
            'domains' => ['domains', ControlDomain::class],
        ];
    }

    #[DataProvider('settingsIndexes')]
    public function test_row_deletion_uses_the_shared_delegated_button(string $module, string $model): void
    {
        $user = (new AdminUser)->forceFill(['id' => 10]);
        $this->actingAs($user, 'admin');
        Gate::before(fn () => true);
        $row = (new $model)->forceFill(['id' => 7]);
        $route = 'admin.settings.'.$module;

        $html = view('admin.settings.'.$module.'.action', compact('row', 'route'))->render();

        self::assertStringContainsString('js-delete-btn', $html);
        self::assertStringContainsString('data-delete-url="'.route($route.'.destroy', 7).'"', $html);
        self::assertStringNotContainsString('onclick=', $html);
    }

    #[DataProvider('settingsIndexes')]
    public function test_row_mutations_are_hidden_when_not_authorized(string $module, string $model): void
    {
        $this->actingAs((new AdminUser)->forceFill(['id' => 10]), 'admin');
        Gate::before(fn () => false);
        $row = (new $model)->forceFill(['id' => 7]);
        $route = 'admin.settings.'.$module;

        $html = view('admin.settings.'.$module.'.action', compact('row', 'route'))->render();

        self::assertStringNotContainsString(route($route.'.destroy', 7), $html);
        self::assertStringNotContainsString(route($route.'.edit', 7), $html);
    }
}
