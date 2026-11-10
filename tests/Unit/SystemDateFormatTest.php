<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DataTables\Admin\Plans\PlanDataTable;
use App\DataTables\Admin\PublicRequests\TrialRequestsDataTable;
use App\DataTables\Tenants\Auctions\AuctionsDataTable;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\PublicRequests\TrialRequest;
use App\Models\Tenants\AuctionEstablishments\AuctionEstablishmentRequest;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\MarketingCampaigns\MarketingCampaign;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class SystemDateFormatTest extends TestCase
{
    public function test_system_date_formats_all_supported_inputs_as_day_month_year(): void
    {
        self::assertSame('d-m-Y', system_date_format());
        self::assertSame('10-11-2026', system_date(Carbon::parse('2026-11-10')));
        self::assertSame('10-11-2026', system_date(new \DateTimeImmutable('2026-11-10 14:30:00')));
        self::assertSame('10-11-2026', system_date('2026-11-10'));
        self::assertSame('10-11-2026', system_date('2026/11/10'));
        self::assertSame('10-11-2026', system_date('2026-11-10T00:21:00.000000Z'));
        self::assertSame('10-11-2026', system_date('10-11-2026'));
        self::assertSame('-', system_date(null));
        self::assertSame('-', system_date(''));
        self::assertSame('غير محدد', system_date(null, 'غير محدد'));
    }

    public function test_system_datetime_formats_date_and_arabic_meridiem_time(): void
    {
        self::assertSame('10-11-2026 02:30 م', system_datetime('2026-11-10 14:30:00'));
        self::assertSame('10-11-2026 09:05 ص', system_datetime('2026-11-10 09:05:00'));
        self::assertSame('-', system_datetime(null));
    }

    public function test_carbon_default_string_format_outputs_day_month_year(): void
    {
        self::assertSame('10-11-2026', (string) Carbon::parse('2026-11-10 14:30:00'));
        self::assertSame('10-11-2026', (string) CarbonImmutable::parse('2026-11-10 14:30:00'));
    }

    public function test_blade_date_and_datetime_directives_render_formatted_values(): void
    {
        $dateHtml = Blade::render('@date($d)', ['d' => Carbon::parse('2026-11-10')]);
        $datetimeHtml = Blade::render('@datetime($d)', ['d' => Carbon::parse('2026-11-10 14:30:00')]);
        $fallbackHtml = Blade::render('@date($d, "غير متاح")', ['d' => null]);

        self::assertSame('10-11-2026', trim($dateHtml));
        self::assertSame('10-11-2026 02:30 م', trim($datetimeHtml));
        self::assertSame('غير متاح', trim($fallbackHtml));
    }

    public function test_abstract_datatable_exposes_client_side_auto_date_renderer_and_helpers(): void
    {
        $dataTable = new AuctionsDataTable;
        $reflection = new \ReflectionClass($dataTable);

        $parametersMethod = $reflection->getMethod('parameters');
        $parametersMethod->setAccessible(true);
        $params = $parametersMethod->invoke($dataTable);

        $render = $params['columnDefs'][0]['render'] ?? '';
        self::assertStringContainsString('match[3] + "-" + match[2] + "-" + match[1]', $render);

        $fake = new class
        {
            public array $added = [];
            public array $edited = [];

            public function addColumn(string $name, $cb): self
            {
                $this->added[$name] = $cb;

                return $this;
            }

            public function editColumn(string $name, $cb): self
            {
                $this->edited[$name] = $cb;

                return $this;
            }
        };

        $addCustomColumns = $reflection->getMethod('addCustomColumns');
        $addCustomColumns->setAccessible(true);
        $addCustomColumns->invoke($dataTable, $fake);

        $auction = new Auction;
        $auction->id = 1;
        $auction->start_date = Carbon::parse('2026-11-10');
        $auction->start_time = '14:30';
        $auction->end_date = Carbon::parse('2026-11-12');
        $auction->end_time = '17:00';
        $auction->created_at = Carbon::parse('2026-11-10 09:00:00');

        $startHtml = ($fake->edited['start_date'])($auction);
        $createdAt = ($fake->edited['created_at'])($auction);

        self::assertStringContainsString('10-11-2026', $startHtml);
        self::assertStringNotContainsString('2026/11/10', $startHtml);
        self::assertSame('10-11-2026', $createdAt);
    }

    public function test_admin_datatables_format_dates_as_day_month_year(): void
    {
        $trialDt = new TrialRequestsDataTable;
        $trialRef = new \ReflectionClass($trialDt);
        $trialFake = new class
        {
            public array $edited = [];

            public function addColumn(string $name, $cb): self
            {
                return $this;
            }

            public function editColumn(string $name, $cb): self
            {
                $this->edited[$name] = $cb;

                return $this;
            }
        };
        $trialAdd = $trialRef->getMethod('addCustomColumns');
        $trialAdd->setAccessible(true);
        $trialAdd->invoke($trialDt, $trialFake);

        $trial = new TrialRequest;
        $trial->created_at = Carbon::parse('2026-11-10 15:45:00');
        self::assertSame('10-11-2026', ($trialFake->edited['created_at'])($trial));

        $planDt = new PlanDataTable;
        $planRef = new \ReflectionClass($planDt);
        $planFake = new class
        {
            public array $edited = [];

            public function addColumn(string $name, $cb): self
            {
                return $this;
            }

            public function editColumn(string $name, $cb): self
            {
                $this->edited[$name] = $cb;

                return $this;
            }
        };
        $planAdd = $planRef->getMethod('addCustomColumns');
        $planAdd->setAccessible(true);
        $planAdd->invoke($planDt, $planFake);

        $plan = new Plan;
        $plan->available_from = Carbon::parse('2026-11-10');
        $plan->created_at = Carbon::parse('2026-11-10');
        self::assertStringContainsString('10-11-2026', ($planFake->edited['available_from'])($plan));
        self::assertSame('10-11-2026', ($planFake->edited['created_at'])($plan));
    }

    public function test_key_blade_partials_render_formatted_dates(): void
    {
        $user = new \App\Models\Tenants\UserMangment\User\User;
        $user->id = 1;
        $this->actingAs($user);
        $this->actingAs($user, 'tenant');
        \Illuminate\Support\Facades\Gate::before(static fn (): bool => true);

        $auction = new Auction;
        $auction->id = 1;
        $auction->exists = true;
        $auction->status = 'draft';
        $auction->start_date = Carbon::parse('2026-11-10');
        $auction->start_time = '14:30';
        $auction->end_date = Carbon::parse('2026-11-12');
        $auction->end_time = '17:00';

        $establishment = new AuctionEstablishmentRequest;
        $establishment->submitted_at = Carbon::parse('2026-11-10 14:30:00');
        $establishment->approved_at = Carbon::parse('2026-11-10 16:45:00');
        $establishment->approval_number = '12345';
        $auction->setRelation('establishmentRequest', $establishment);

        $estHtml = view('tenant.auctions.partials.establishment-request', [
            'auction' => $auction,
            'establishment' => $establishment,
        ])->render();

        self::assertStringContainsString('10-11-2026 02:30 م', $estHtml);
        self::assertStringContainsString('10-11-2026 04:45 م', $estHtml);
        self::assertStringNotContainsString('2026/11/10', $estHtml);

        $campaign = new MarketingCampaign;
        $campaign->channel = 'whatsapp';
        $campaign->campaign_date = Carbon::parse('2026-11-10');
        $campaign->setRelation('evidence', collect());
        $auction->setRelation('marketingCampaigns', collect([$campaign]));
        $auction->setRelation('properties', collect());
        $auction->setRelation('closing', null);
        $auction->setRelation('assignment', null);
        $auction->setRelation('attachments', collect());

        $cycleHtml = view('tenant.auctions.partials.cycle-details', [
            'auction' => $auction,
        ])->render();

        self::assertStringContainsString('10-11-2026', $cycleHtml);
        self::assertStringNotContainsString('2026-11-10', $cycleHtml);

        $reviewHtml = view('tenant.auctions.partials.steps.step5-review', [
            'isEdit' => true,
            'auction' => $auction,
        ])->render();

        self::assertStringContainsString('10-11-2026 إلى 12-11-2026', $reviewHtml);
    }

    public function test_date_inputs_and_filters_render_expected_format_placeholders_and_readonly_picker_config(): void
    {
        $dateInputHtml = Blade::render('<x-form.controls.input name="start_date" type="date" label="تاريخ البداية" />');
        $dateTimeInputHtml = Blade::render('<x-form.controls.input name="starts_at" type="datetime-local" label="وقت البداية" />');
        $filterDateHtml = Blade::render('<x-filter.date id="filter-created-from" filterKey="created_from" label="تاريخ الإنشاء من" />');

        self::assertStringContainsString('placeholder="اختر التاريخ"', $dateInputHtml);
        self::assertStringContainsString('placeholder="اختر التاريخ والوقت"', $dateTimeInputHtml);
        self::assertStringContainsString('placeholder="اختر التاريخ"', $filterDateHtml);

        $assignmentsForm = file_get_contents(resource_path('views/tenant/assignments/partials/_form.blade.php')) ?: '';
        $propertiesForm = file_get_contents(resource_path('views/tenant/properties/partials/_form.blade.php')) ?: '';
        self::assertStringNotContainsString('يوم-شهر-سنة', $assignmentsForm);
        self::assertStringNotContainsString('يوم-شهر-سنة', $propertiesForm);

        $dateFormatJs = file_get_contents(resource_path('assets/js/shared/date-format.js')) ?: '';
        self::assertStringContainsString('allowInput: false', $dateFormatJs);
        self::assertStringContainsString('fp.config.allowInput = false;', $dateFormatJs);
        self::assertStringContainsString('اختر التاريخ', $dateFormatJs);
        self::assertStringContainsString('اختر التاريخ والوقت', $dateFormatJs);
        self::assertStringContainsString('flatpickr-input', $dateFormatJs);
    }
}
