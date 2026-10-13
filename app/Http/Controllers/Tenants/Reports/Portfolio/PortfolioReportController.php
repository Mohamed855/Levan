<?php

namespace App\Http\Controllers\Tenants\Reports\Portfolio;

use App\DataTables\Tenants\Reports\Portfolio\PortfolioReportDataTable;
use App\Http\Controllers\Controller;

class PortfolioReportController extends Controller
{
    public function index(PortfolioReportDataTable $dataTable)
    {
        return $dataTable->render('tenants.reports.portfolios.index');
    }
}
