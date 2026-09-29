<?php

use App\Http\Controllers\Tenants\FinancialCollection\CollectionProcess\CollectionProcessController;
use Illuminate\Support\Facades\Route;

Route::prefix('collection-process')->name('collection-process.')->group(function () {
    Route::name('index')->get('/', [CollectionProcessController::class, 'index']);
    Route::name('customers.next')->get('/next', [CollectionProcessController::class, 'nextCustomer']);
    Route::name('customers.prev')->get('/prev', [CollectionProcessController::class, 'prevCustomer']);
    Route::name('customers.skip')->get('/skip', [CollectionProcessController::class, 'skipCustomer']);
    Route::name('customers.follows.store')->post('/customers/{customer}/follows/store', [CollectionProcessController::class, 'storeFollow']);
    Route::name('customers.follows.contact-result-fields')->get('/customers/{customer}/follows/contact-result-fields', [CollectionProcessController::class, 'getContactResultFields']);
    Route::name('customers.follows.get-customer-debt')->get('/customers/{customer}/debt', [CollectionProcessController::class, 'getCustomerDebt']);
    Route::name('exit')->post('/exit', [CollectionProcessController::class, 'recordExit']);
    Route::name('customers.whatsapp-preview')->get('/customers/{customer}/whatsapp-preview', [CollectionProcessController::class, 'previewWhatsAppTemplate']);
    Route::name('customers.phone-number')->get('/customers/{customer}/phone-number', [CollectionProcessController::class, 'getCustomerPhoneNumberForWhatsApp']);
    Route::name('customers.add-phone')->post('/customers/{customer}/add-phone', [CollectionProcessController::class, 'addCustomerPhone']);
    Route::name('customers.set-primary-phone')->post('/customers/{customer}/set-primary-phone', [CollectionProcessController::class, 'setPrimaryPhone']);
    Route::name('customers.get-related-accounts')->get('customers/{customer}/related-accounts', [CollectionProcessController::class, 'getRelatedAccounts']);

    Route::post('search-customer', [CollectionProcessController::class, 'searchCustomer'])->name('search-customer');
});
