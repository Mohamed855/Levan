<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\Auctions;

use App\DataTables\Tenants\Auctions\AuctionReportsDataTable;
use App\Http\Controllers\Controller;
use App\Services\Tenants\Auctions\Reports\AuctionReportManagementService;

final class AuctionReportsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:tenant', 'can:auction_reports.view']);
    }

    public function index(AuctionReportsDataTable $table, AuctionReportManagementService $options)
    {
        return $table->render('tenant.auction-reports.index', ['statistics' => $options->reportStatistics()]);
    }
}
