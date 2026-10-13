<?php

namespace App\Http\Controllers\Tenants\Reports\User;

use App\Http\Controllers\Controller;
use App\Services\Tenants\Reports\User\UserReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserReportController extends Controller
{
    protected UserReportService $userReportService;

    public function __construct(UserReportService $userReportService)
    {
        $this->userReportService = $userReportService;

        $this->middleware('can:reports.users.view');
    }

    /*
    |--------------------------------------------------------------------------
    | عرض تقارير المستخدمين
    |--------------------------------------------------------------------------
    */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            return $this->userReportService->getUsersReportData($request);
        }

        $reportData = $this->userReportService->getReportStatistics();
        $formData = $this->userReportService->getFormData();

        return view('tenants.reports.users.index', array_merge($reportData, $formData));
    }

    /*
    |--------------------------------------------------------------------------
    | تصدير تقرير المستخدمين إلى PDF
    |--------------------------------------------------------------------------
    */
    public function exportPDF(Request $request): BinaryFileResponse
    {
        $filters = $request->only([
            'status',
            'date_from',
            'date_to',
        ]);

        return $this->userReportService->exportUsersReportToPDF($filters);
    }
}
