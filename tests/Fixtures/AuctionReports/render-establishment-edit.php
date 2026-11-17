<?php

declare(strict_types=1);

use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\AuctionEstablishments\AuctionEstablishmentRequestData;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ViewErrorBag;

require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
view()->share('errors', new ViewErrorBag);
DB::listen(static fn () => throw new RuntimeException('The establishment UI fixture must not query a database.'));
Route::get('/fixture/attachments/{auction}/{type}/{property?}', static fn () => null)->name('auction-establishment-requests.attachment');
Route::getRoutes()->refreshNameLookups();
$auction = new Auction(['name' => 'مزاد الاختبار']);
$auction->id = 1;
$auction->setRelation('assignment', (object) ['assignment_number' => 'ASSIGN-1']);
$properties = [];
$rows = [];
foreach ([42, 43] as $id) {
    $properties[] = (object) ['id' => $id, 'name' => 'العقار '.$id, 'property_number' => 'PROP-'.$id, 'type_label' => 'أرض', 'city' => 'الرياض', 'district' => 'الحي', 'area' => 100, 'estimated_value' => 1000, 'agent_name' => null, 'assignment' => null, 'main_image_url' => null, 'image_url' => null];
    $rows[] = ['property_id' => $id, 'owner_name' => 'مالك العقار', 'deed_number' => '123456789', 'contract_number' => 'ASSIGN-1', 'area' => 100, 'type' => 'أرض', 'usage' => 'residential', 'usage_other' => '', 'age' => 0, 'city_district' => 'الرياض - الحي', 'status' => 'clear', 'status_other' => '', 'ownership_share' => 100, 'disclosure' => 'لا يوجد', 'agent_name' => '', 'agency_number' => '', 'opening_value' => 1000, 'entry_amount' => 100, 'estimated_value' => 1000];
}
$auction->setRelation('properties', collect($properties));
$data = ['properties' => $rows, 'attachments' => []];
if (($argv[1] ?? '') === 'existing') {
    foreach (['manager_certificate', 'auctioneer_certificate'] as $type) {
        $data['attachments'][$type][0] = ['name' => 'شهادة المسؤول', 'path' => 'proof/'.$type.'.png', 'mime' => 'image/png'];
    }
    foreach ([42, 43] as $id) {
        foreach (['deed', 'valuation'] as $type) {
            $data['attachments'][$type][$id] = ['name' => 'مستند العقار', 'path' => 'proof/'.$type.'-'.$id.'.png', 'mime' => 'image/png'];
        }
    }
}
$schema = new AuctionEstablishmentRequestData;
ob_start();
echo '<!doctype html><meta charset="utf-8"><style>.d-none{display:none!important}.modal:not(.show){display:none}.modal.show{display:block}.modal-backdrop{display:none}.modal{position:relative}</style><div data-form-stepper><form id="establishment-request-form"><section id="step-establishment-properties">';
echo view('tenant.auction-establishment-requests.steps.step3-properties', compact('auction', 'data', 'schema'))->render();
echo '<button class="btn-next" data-wizard-next type="button">التالي</button></section>';
echo view('tenant.auction-establishment-requests.steps.step5-attachments', compact('auction', 'data', 'schema'))->render();
echo '</form>';
echo view('tenant.auction-establishment-requests.steps._property-modal', compact('schema'))->render();
echo '</div>';
echo ob_get_clean();
