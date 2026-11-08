<?php

declare(strict_types=1);
use App\Contracts\Auctions\BrowserDocumentRenderer;
use App\Services\Tenants\Auctions\Reports\AuctionReportRenderer;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
app()->setLocale('ar');
DB::listen(static function (): void {
    throw new RuntimeException('Cycle proof must not access a database.');
});
$directory = base_path('output/pdf/cycle-proof');
if (! is_dir($directory)) {
    mkdir($directory, 0775, true);
}
$logo = static function (string $label, string $color): string {
    $image = imagecreatetruecolor(400, 120);
    imagefill($image, 0, 0, imagecolorallocate($image, hexdec(substr($color, 1, 2)), hexdec(substr($color, 3, 2)), hexdec(substr($color, 5, 2))));
    imagestring($image, 5, 160, 50, $label, imagecolorallocate($image, 255, 255, 255));
    ob_start();
    imagepng($image);
    $bytes = ob_get_clean();

    return 'data:image/png;base64,'.base64_encode($bytes);
};
$receipt = [];
foreach (['empty', 'saved'] as $scenario) {
    $snapshot = require base_path('tests/Fixtures/AuctionReports/haram.php');
    $snapshot['cycle_version'] = 1;
    $snapshot['closing_saved'] = $scenario === 'saved';
    $snapshot['branding'] = ['company_logo' => $logo('COMPANY', '#246c53'), 'issuing_entity_logo' => $logo('ENTITY', '#244f9a')];
    $snapshot['marketing']['contacts'] = ['phone' => '0501234567', 'unified_phone' => '920000000', 'social' => '@company'];
    $snapshot['properties'] = array_slice($snapshot['properties'], 0, 2);
    $snapshot['properties'][0]['buyer_name'] = 'اسم المشتري للاختبار';
    $snapshot['properties'][0]['result_notes'] = 'ملاحظات نتيجة البيع';
    $snapshot['properties'][1]['result_status'] = 'unsold';
    $snapshot['properties'][1]['sale_price'] = null;
    $snapshot['properties'][1]['unsold_reason'] = 'لم يصل السعر إلى القيمة المطلوبة';
    $snapshot['totals'] = array_replace($snapshot['totals'], ['properties_count' => 2, 'sold_count' => 1, 'unsold_count' => 1, 'recorded_sales_total' => 600000]);
    $snapshot['closing'] = ['date' => '2026-11-05', 'approval_number' => 'APPROVAL-123'];
    if ($scenario === 'saved') {
        $snapshot['marketing']['campaigns'] = [
            ['id' => 1, 'platform' => 'x', 'views' => 0, 'impressions' => 0, 'url' => 'https://example.com/first', 'campaign_date' => '2026-11-01'],
            ['id' => 2, 'platform' => 'x', 'views' => 125, 'impressions' => 250, 'url' => 'https://example.com/second'],
            ['id' => 3, 'platform' => 'youtube', 'views' => 0, 'subscribers' => 12, 'url' => 'https://example.com/video'],
            ['id' => 4, 'platform' => 'whatsapp', 'messages' => 0, 'delivered' => 0, 'total' => 0, 'campaign_date' => '2026-11-01'],
            ['id' => 5, 'platform' => 'tiktok', 'impressions' => 1200, 'reach' => 0, 'views' => 1300],
            ['id' => 6, 'platform' => 'snapchat', 'impressions' => 700],
            ['id' => 7, 'platform' => 'brochure', 'brochure_context' => 'تم إعداد الكتيب التسويقي للعقارات.', 'link_role' => 'brochure', 'url' => 'https://example.com/brochure'],
            ['id' => 8, 'platform' => 'planning', 'planning_methodology' => 'دراسة السوق العقاري وتحديد الوسائل المناسبة للوصول إلى الفئات المستهدفة.'],
            ['id' => 9, 'platform' => 'design', 'name_context' => 'اختيار اسم المزاد وهويته البصرية.', 'brochure_methodology' => 'إعداد الكتيب بصور العقارات وبياناتها ومواقعها.'],
            ['id' => 10, 'platform' => 'promotion', 'audience_methodology' => 'التواصل مع المستثمرين المهتمين بالعقارات المعروضة.'],
            ['id' => 11, 'platform' => 'brokers', 'display_name' => 'الوسيط المعتمد', 'broker_second_name' => 'الوسيط الثاني', 'contract_text' => 'تم التعاقد مع الوسيط لتسويق العقارات المعروضة في المزاد.'],
            ['id' => 12, 'platform' => 'platform', 'platform_context' => 'عرض العقارات على منصة المزاد.'],
            ['id' => 13, 'platform' => 'summary', 'city_context' => 'المملكة ومنطقة الرياض.'],
            ['id' => 14, 'platform' => 'google', 'views' => 0],
        ];
        $snapshot['evidence']['social'] = [
            ['id' => 61, 'campaign_id' => 6, 'channel' => 'snapchat', 'uri' => $logo('SNAP A', '#245a76')],
            ['id' => 62, 'campaign_id' => 6, 'channel' => 'snapchat', 'uri' => $logo('SNAP B', '#245a76')],
        ];
        $snapshot['evidence']['brochure'] = [['id' => 71, 'campaign_id' => 7, 'channel' => 'brochure', 'uri' => $logo('BROCHURE', '#245a76')]];
    }
    foreach (['outputs', 'report'] as $type) {
        $html = app(AuctionReportRenderer::class)->html($snapshot, $type);
        $path = $directory.'/'.$scenario.'-'.$type;
        file_put_contents($path.'.html', $html);
        app(BrowserDocumentRenderer::class)->pdf($html, $path.'.pdf');
        $receipt[$scenario.'-'.$type] = ['pages' => substr_count($html, 'data-page-type='), 'sha256' => hash_file('sha256', $path.'.pdf')];
    }
}
file_put_contents($directory.'/receipt.json', json_encode($receipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
echo json_encode($receipt, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
