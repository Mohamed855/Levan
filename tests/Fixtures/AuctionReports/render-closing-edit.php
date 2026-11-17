<?php

declare(strict_types=1);

use App\Models\Tenants\Auctions\Auction;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
DB::listen(static fn () => throw new RuntimeException('The closing UI fixture must not query a database.'));
Route::get('/fixture/closing/{auction}/properties/{property}', static fn () => null)->name('auction-closings.property-data');
Route::getRoutes()->refreshNameLookups();
$errors = new ViewErrorBag;
$errors->put('default', new MessageBag(json_decode($argv[1] ?? '{}', true, flags: JSON_THROW_ON_ERROR)));
view()->share('errors', $errors);
$auction = new Auction(['name' => 'مزاد الاختبار', 'start_date' => '2026-11-10', 'end_date' => '2026-11-20']);
$auction->id = 1;
$auction->setRelation('closing', null);
$auction->setRelation('establishmentRequest', null);
$auction->setRelation('attachments', collect());
$properties = [];
foreach ([42, 43] as $id) {
    $property = new class extends Model {};
    $property->setRawAttributes(['id' => $id, 'name' => 'العقار '.$id, 'property_number' => 'PROP-'.$id, 'type_label' => 'أرض', 'city' => 'الرياض', 'district' => 'الحي', 'area' => 100, 'estimated_value' => 1000]);
    $property->setRelation('pivot', (object) ['result_status' => 'sold', 'opening_price' => 1000, 'participants_count' => 3, 'bids_count' => 4, 'buyer_name' => 'المشتري '.$id, 'sale_price' => 1500, 'unsold_reason' => '', 'result_notes' => 'ملاحظات '.$id]);
    $properties[] = $property;
}
$auction->setRelation('properties', collect($properties));
$source = file_get_contents(resource_path('views/tenant/auction-closings/edit.blade.php'));
$source = str_replace("@extends('tenant.layouts.layoutMaster')", '', $source);
echo '<!doctype html><meta charset="utf-8"><style>.d-none{display:none!important}.modal:not(.show){display:none}.modal.show{display:block}.modal-backdrop{display:none}.modal{position:relative}</style>';
echo Blade::render($source."\n@yield('content')", compact('auction', 'errors'));
