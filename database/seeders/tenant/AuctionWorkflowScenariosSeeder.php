<?php

declare(strict_types=1);

namespace Database\Seeders\tenant;

use App\Actions\Tenants\AuctionClosings\SaveAuctionClosingAction;
use App\Actions\Tenants\AuctionEstablishments\ApproveAuctionEstablishmentRequestAction;
use App\Actions\Tenants\AuctionEstablishments\SaveAuctionEstablishmentRequestAction;
use App\Actions\Tenants\MarketingCampaigns\SaveMarketingCampaignAction;
use App\Http\Requests\Tenants\MarketingCampaigns\SaveMarketingCampaignRequest;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\AuctionEstablishments\AuctionEstablishmentRequestData;
use App\Services\Tenants\Auctions\Reports\AuctionReportService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Throwable;

final class AuctionWorkflowScenariosSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('seeding.test_seeders_enabled', false)) {
            return;
        }
        if (! tenancy()->initialized) {
            throw new RuntimeException('Auction workflow scenarios require an initialized test tenant.');
        }
        $actorId = User::where('email', 'demo@lot.com')->value('id') ?? User::value('id');
        if (! $actorId) {
            throw new RuntimeException('Seed tenant users before auction workflow scenarios.');
        }
        $assignment = Assignment::firstOrCreate(['assignment_number' => 'WF-DEMO'], [
            'name' => 'إسناد اختبار دورة المزاد', 'issuing_entity' => 'جهة تجريبية', 'category' => 'residential',
            'city' => 'الرياض', 'start_date' => now()->subMonths(2), 'end_date' => now()->addYear(), 'is_active' => true,
        ]);
        foreach ($this->scenarios() as $number => $scenario) {
            $name = '[WF-'.$number.'] '.$scenario['label'];
            // Re-running seeders must preserve the tester's edits and approved snapshots.
            if (Auction::withTrashed()->where('marketing_data->demo_scenario', 'WF-'.$number)->exists()) {
                continue;
            }
            $paths = [];
            try {
                DB::transaction(function () use ($assignment, $actorId, $number, $scenario, $name, &$paths): void {
                    $auction = Auction::create([
                        'assignment_id' => $assignment->id, 'name' => $name, 'description' => $scenario['label'].' — بيانات تجريبية للاختبار فقط.',
                        'type' => match ((int) $number % 3) {
                            0 => 'hybrid', 1 => 'electronic', default => 'in_person'
                        },
                        'category' => 'residential', 'city' => 'الرياض', 'start_date' => now()->addDays(5), 'end_date' => now()->addDays(7),
                        'start_time' => '16:00', 'end_time' => '21:00', 'platform_name' => 'منصة المزاد التجريبية',
                        'platform_url' => 'https://example.com/auctions/wf-'.$number, 'project_manager_name' => 'مدير المزاد التجريبي',
                        'marketing_data' => ['demo_scenario' => 'WF-'.$number],
                        'is_active' => true, 'created_by' => $actorId,
                    ]);
                    for ($index = 1; $index <= $scenario['properties']; $index++) {
                        $property = Property::create([
                            'assignment_id' => $assignment->id, 'property_number' => 'WF-'.$number.'-P'.$index,
                            'name' => 'عقار تجربة '.$number.' / '.$index, 'type' => 'land', 'usage_classification' => 'residential',
                            'city' => 'الرياض', 'district' => 'النرجس', 'area' => '1200.00', 'estimated_value' => '1500000.00',
                            'deed_number' => '000'.$number.'000'.$index, 'status' => 'assigned_to_auction', 'is_active' => true,
                            'agent_name' => $number === '03' && $index === 1 ? 'وكيل المالك التجريبي' : null,
                            'agency_number' => $number === '03' && $index === 1 ? '000123456' : null, 'created_by' => $actorId,
                        ]);
                        $property->owners()->create(['owner_name' => 'مالك عقار التجربة '.$index, 'national_id_or_cr' => '100000000'.$index, 'sort_order' => 0]);
                        $auction->properties()->attach($property->id, ['sort_order' => $index, 'result_status' => 'pending', 'opening_price' => '1000000.00']);
                    }
                    if ($scenario['request']) {
                        $this->saveRequest($auction->fresh(), $actorId, $paths);
                    }
                    if ($scenario['approved']) {
                        app(ApproveAuctionEstablishmentRequestAction::class)->execute($auction, '000900'.$number, $actorId);
                    }
                    $this->saveMarketing($auction->fresh(), $scenario['marketing'], $actorId);
                    if ($scenario['results'] !== []) {
                        $results = [];
                        foreach ($auction->properties()->orderByPivot('sort_order')->get() as $index => $property) {
                            $status = $scenario['results'][$index];
                            $results[$property->id] = ['status' => $status, 'buyer_name' => $status === 'sold' ? 'مشتري تجربة '.$number : null,
                                'sale_price' => $status === 'sold' ? '1350000.25' : null,
                                'unsold_reason' => $status === 'unsold' ? 'لم يصل إلى الحد الأدنى المعتمد' : null,
                                'participants_count' => 12, 'bids_count' => 30];
                        }
                        app(SaveAuctionClosingAction::class)->execute($auction->fresh(), [
                            'closing_date' => now()->toDateString(), 'results' => $results,
                            'results_narrative' => 'نتائج تجريبية لاختبار حفظ الإقفال وإعادة توليد الملفات.',
                        ], $actorId);
                    }
                    if (! $auction->reportGenerations()->exists()) {
                        $this->requireReportGeneration($auction);
                    }
                });
            } catch (Throwable $exception) {
                Storage::disk('tenant_private')->delete($paths);
                throw $exception;
            }
        }
        $this->seedExistingMixedClosing((int) $actorId);
    }

    private function scenarios(): array
    {
        return [
            '01' => ['label' => 'قيد الانتظار بدون طلب إقامة', 'request' => false, 'approved' => false, 'marketing' => 'none', 'properties' => 2, 'results' => []],
            '02' => ['label' => 'طلب قيد المراجعة وتسويق جزئي', 'request' => true, 'approved' => false, 'marketing' => 'partial', 'properties' => 2, 'results' => []],
            '03' => ['label' => 'طلب قيد المراجعة وتسويق مكتمل', 'request' => true, 'approved' => false, 'marketing' => 'complete', 'properties' => 2, 'results' => []],
            '04' => ['label' => 'معتمد وتسويق غير مكتمل', 'request' => true, 'approved' => true, 'marketing' => 'partial', 'properties' => 2, 'results' => []],
            '05' => ['label' => 'معتمد ومكتمل وجاهز للإقفال', 'request' => true, 'approved' => true, 'marketing' => 'complete', 'properties' => 2, 'results' => []],
            '06' => ['label' => 'إقفال جزئي بمباع وغير مباع', 'request' => true, 'approved' => true, 'marketing' => 'complete', 'properties' => 2, 'results' => ['sold', 'unsold']],
            '07' => ['label' => 'تم الإقفال والتسويق مكتمل', 'request' => true, 'approved' => true, 'marketing' => 'complete', 'properties' => 2, 'results' => ['sold', 'sold']],
            '08' => ['label' => 'تم الإقفال والتسويق غير مكتمل', 'request' => true, 'approved' => true, 'marketing' => 'partial', 'properties' => 2, 'results' => ['sold', 'sold']],
            '09' => ['label' => 'إقفال بلا مبيعات وقابل للتحديث', 'request' => true, 'approved' => true, 'marketing' => 'none', 'properties' => 2, 'results' => ['unsold', 'unsold']],
            '10' => ['label' => 'مزاد بدون عقارات لا يقبل طلب إقامة', 'request' => false, 'approved' => false, 'marketing' => 'none', 'properties' => 0, 'results' => []],
        ];
    }

    private function saveRequest(Auction $auction, int $actorId, array &$paths): void
    {
        $data = json_decode(file_get_contents(resource_path('auction-regulatory-forms/example.json')), true, flags: JSON_THROW_ON_ERROR);
        $propertyExample = array_replace($data['property'], $data['properties'][0]);
        unset($data['property'], $data['agents'], $data['closing'], $data['submission_date']);
        $data['auction'] = array_replace($data['auction'], app(AuctionEstablishmentRequestData::class)->auctionSource($auction), ['venue' => 'قاعة المزاد التجريبية']);
        $data['properties'] = [];
        $uploads = ['manager_certificate' => $this->document(), 'auctioneer_certificate' => $this->document()];
        foreach ($auction->properties as $index => $property) {
            $data['properties'][] = array_replace($propertyExample, [
                'property_id' => $property->id, 'owner_name' => $property->owners->pluck('owner_name')->implode('، '),
                'deed_number' => $property->deed_number, 'contract_number' => '000150'.$property->id,
                'area' => (string) $property->area, 'age' => 0, 'type' => $property->type_label, 'usage' => $property->usage_classification_label,
                'agent_name' => $property->agent_name ?? '', 'agency_number' => $property->agency_number ?? '', 'city_district' => $property->city.' / '.$property->district,
                'opening_value' => '1000000.00', 'entry_amount' => $index === 0 ? '50000.00' : '0.00',
                'estimated_value' => (string) $property->estimated_value, 'disclosure' => 'لا توجد قيود إضافية — تجربة',
            ]);
            $uploads['deed'][$property->id] = $this->document();
            if ($index === 0) {
                $uploads['valuation'][$property->id] = $this->document();
            }
            if (filled($property->agent_name)) {
                $uploads['representative_authority'][$property->id] = $this->document();
            }
        }
        $request = app(SaveAuctionEstablishmentRequestAction::class)->execute($auction, ['data' => $data, 'declaration_accepted' => '1', 'attachments' => $uploads], $actorId);
        foreach ($request->data['attachments'] as $files) {
            foreach ($files as $file) {
                $paths[] = $file['path'];
            }
        }
    }

    private function saveMarketing(Auction $auction, string $level, int $actorId): void
    {
        if ($level === 'none') {
            return;
        }
        $channels = $level === 'complete' ? array_keys(config('auction-marketing-campaigns')) : ['google'];
        $payload = ['auction_id' => $auction->id, 'channels' => $channels, 'campaigns' => []];
        foreach ($channels as $channel) {
            $metrics = [];
            foreach (config('auction-marketing-campaigns.'.$channel) as $metric) {
                $metrics[$metric] = match (true) {
                    $metric === 'property_id' => $auction->properties->first()->id,
                    $metric === 'board_dimensions' => '3 × 2 متر',
                    array_key_exists($metric, config('auction-marketing-fields.text')) => 'تفاصيل الحملة التجريبية',
                    default => 100,
                };
            }
            if ($channel === 'whatsapp') {
                $metrics = ['messages' => 1000, 'delivered' => 900, 'failed' => 50, 'pending' => 50, 'total' => 1000, 'views' => 750];
            }
            $payload['campaigns'][$channel] = ['metrics' => $metrics, 'url' => 'https://example.com/demo/'.$channel, 'campaign_date' => now()->toDateString()];
        }
        $request = new SaveMarketingCampaignRequest;
        $request->merge($payload);
        $validated = Validator::make($payload, $request->rules(), $request->messages(), $request->attributes())->validate();
        app(SaveMarketingCampaignAction::class)->executeBatch($auction, $validated, $actorId);
    }

    private function document(): UploadedFile
    {
        return new UploadedFile(database_path('seeders/fixtures/auction-demo-document.pdf'), 'مستند_تجريبي_للاختبار.pdf', 'application/pdf', test: true);
    }

    private function requireReportGeneration(Auction $auction): void
    {
        if (! app(AuctionReportService::class)->requestGeneration($auction, (string) tenant('id'))) {
            throw new RuntimeException('Could not prepare the demo auction report snapshot.');
        }
    }

    private function seedExistingMixedClosing(int $actorId): void
    {
        $auction = Auction::where('name', 'مزاد مساهمات الشرقية الصناعية المنتهي')->first();
        if (! $auction || $auction->establishmentRequest) {
            return;
        }
        $paths = [];
        try {
            DB::transaction(function () use ($auction, $actorId, &$paths): void {
                $results = $auction->properties->mapWithKeys(fn ($property) => [$property->id => [
                    'status' => $property->pivot->result_status,
                    'buyer_name' => $property->pivot->buyer_name,
                    'sale_price' => $property->pivot->sale_price,
                    'unsold_reason' => $property->pivot->unsold_reason,
                ]])->all();
                $this->saveRequest($auction, $actorId, $paths);
                app(ApproveAuctionEstablishmentRequestAction::class)->execute($auction, '000900LEGACY', $actorId);
                app(SaveAuctionClosingAction::class)->execute($auction->fresh(), [
                    'closing_date' => $auction->end_date->toDateString(), 'results' => $results,
                ], $actorId);
            });
        } catch (Throwable $exception) {
            Storage::disk('tenant_private')->delete($paths);
            throw $exception;
        }
    }
}
