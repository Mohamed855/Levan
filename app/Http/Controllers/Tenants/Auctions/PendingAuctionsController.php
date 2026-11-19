<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\Auctions;

use App\DataTables\Tenants\Auctions\PendingAuctionsDataTable;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

final class PendingAuctionsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:tenant', 'can:pending_auctions.view']);
    }

    public function index(PendingAuctionsDataTable $dataTable): View|JsonResponse
    {
        return $dataTable->render('tenant.pending-auctions.index');
    }
}
