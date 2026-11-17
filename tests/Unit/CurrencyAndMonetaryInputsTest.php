<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DataTables\Tenants\Properties\PropertiesDataTable;
use App\Helpers\Helpers;
use App\Http\Middleware\NormalizeMonetaryInputs;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class CurrencyAndMonetaryInputsTest extends TestCase
{
    public function test_format_price_helper_formats_thousands_and_decimals(): void
    {
        $this->assertStringContainsString('1,000.00', Helpers::formatPrice(1000));
        $this->assertStringContainsString('icon-saudi_riyal--dark', Helpers::formatPrice(1000));
        $this->assertStringContainsString('1,234,567.89', Helpers::formatPrice(1234567.89));
        $this->assertStringContainsString('500.50', Helpers::formatPrice('500.50'));
        $this->assertSame('-', Helpers::formatPrice(null));
        $this->assertSame('-', Helpers::formatPrice(''));

        // Test global wrapper
        $this->assertStringContainsString('2,500.00', \format_price(2500));
    }

    public function test_riyal_icon_helper_generates_dark_and_white_variants(): void
    {
        $darkIcon = Helpers::riyalIcon(true);
        $this->assertStringContainsString('icon-saudi_riyal', $darkIcon);
        $this->assertStringContainsString('icon-saudi_riyal--dark', $darkIcon);

        $whiteIcon = Helpers::riyalIcon(false);
        $this->assertStringContainsString('icon-saudi_riyal', $whiteIcon);
        $this->assertStringContainsString('icon-saudi_riyal--white', $whiteIcon);

        // Test global wrapper
        $this->assertStringContainsString('icon-saudi_riyal--dark', \riyal_icon(true));
        $this->assertStringContainsString('icon-saudi_riyal--white', \riyal_icon(false));
    }

    public function test_normalize_monetary_inputs_middleware_strips_thousands_commas(): void
    {
        $middleware = new NormalizeMonetaryInputs;

        $request = Request::create('/test', 'POST', [
            'price' => '1,500,000.50',
            'deposit' => '50,000',
            'regular_text' => 'Hello, world!',
            'numbers_list' => '1,2,3',
            'nested' => [
                'amount' => '25,000.00',
                'description' => 'A, B, C',
            ],
        ]);

        $response = $middleware->handle($request, function (Request $req): Response {
            $this->assertSame('1500000.50', $req->input('price'));
            $this->assertSame('50000', $req->input('deposit'));
            $this->assertSame('Hello, world!', $req->input('regular_text'));
            $this->assertSame('1,2,3', $req->input('numbers_list'));
            $this->assertSame('25000.00', $req->input('nested.amount'));
            $this->assertSame('A, B, C', $req->input('nested.description'));

            return new Response('ok');
        });

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_input_component_renders_price_mode_attributes_and_icon(): void
    {
        $rendered = Blade::render('<x-form.controls.input name="test_price" :price="true" value="1000" />');

        $this->assertStringContainsString('data-price-input="true"', $rendered);
        $this->assertStringContainsString('inputmode="decimal"', $rendered);
        $this->assertStringContainsString('icon-saudi_riyal', $rendered);
        $this->assertStringContainsString('icon-saudi_riyal--dark', $rendered);
        $this->assertStringContainsString('value="1,000"', $rendered);
    }

    public function test_stat_card_component_renders_dark_currency_icon_when_requested(): void
    {
        $rendered = Blade::render('<x-ui.stat-card title="المدفوعات هذا الشهر" :value="number_format(15000, 0)" :currency="true" />');

        $this->assertStringContainsString('15,000', $rendered);
        $this->assertStringContainsString('icon-saudi_riyal', $rendered);
        $this->assertStringContainsString('icon-saudi_riyal--dark', $rendered);

        $defaultRendered = Blade::render('<x-ui.stat-card title="العملاء" :value="50" />');
        $this->assertStringNotContainsString('icon-saudi_riyal', $defaultRendered);
    }

    public function test_properties_datatable_includes_estimated_value_column(): void
    {
        $dataTable = app(PropertiesDataTable::class);
        $method = new \ReflectionMethod($dataTable, 'getColumns');
        $columns = $method->invoke($dataTable);

        $column = collect($columns)->firstWhere('data', 'estimated_value');

        $this->assertNotNull($column);
        $this->assertSame('القيمة التقديرية', $column['title']);

        $rawColumnsMethod = new \ReflectionMethod($dataTable, 'rawColumns');
        $rawColumns = $rawColumnsMethod->invoke($dataTable);
        $this->assertContains('estimated_value', $rawColumns);
    }

    public function test_assignment_show_properties_table_matches_auction_show_columns(): void
    {
        $assignmentShow = file_get_contents(resource_path('views/tenant/assignments/show.blade.php'));
        $auctionShow = file_get_contents(resource_path('views/tenant/auctions/show.blade.php'));

        $this->assertStringContainsString('<th>القيمة التقديرية</th>', $assignmentShow);
        $this->assertStringContainsString('<th style="width: 60px;" class="text-center">الصورة</th>', $assignmentShow);
        $this->assertStringContainsString('<th style="width: 80px;" class="text-center">الإجراء</th>', $assignmentShow);

        $this->assertStringContainsString('table table-hover align-middle mb-0', $assignmentShow);
        $this->assertStringContainsString('table table-hover align-middle mb-0', $auctionShow);

        $this->assertStringContainsString('x-actions.show', $assignmentShow);
    }

    public function test_property_show_includes_estimated_value_after_usage_classification(): void
    {
        $propertyShow = file_get_contents(resource_path('views/tenant/properties/show.blade.php'));

        $this->assertStringContainsString('تصنيف الاستخدام:', $propertyShow);
        $this->assertStringContainsString('القيمة التقديرية:', $propertyShow);
        $this->assertStringContainsString('icon-saudi_riyal--dark', $propertyShow);

        $usagePos = strpos($propertyShow, 'تصنيف الاستخدام:');
        $estimatedPos = strpos($propertyShow, 'القيمة التقديرية:');

        $this->assertNotFalse($usagePos);
        $this->assertNotFalse($estimatedPos);
        $this->assertTrue($estimatedPos > $usagePos, 'Estimated value must be placed after usage classification');
    }
}
