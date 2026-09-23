<?php
namespace App\Http\Controllers\Tenants\Reports\Contract;

use App\DataTables\Tenants\Reports\Contract\ContractReportDataTable;
use App\Http\Controllers\Controller;
use App\Models\Tenants\BusinessCenter\Contract\Contract;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractReportController extends Controller
{

    public function index(ContractReportDataTable $dataTable)
    {
        return $dataTable->render('tenants.reports.contracts.index');
    }
    // public function index()
    // {
    //     // جلب جميع العقود مع البيانات المطلوبة
    //     $contracts = Contract::with(['company', 'commissions'])
    //         ->get();

    //     // حساب التفاصيل لكل عقد
    //     $contractsReport = $contracts->map(function ($contract) {
    //         // جلب المبالغ المحصلة المعتمدة للعملاء التابعين لنفس الشركة
    //         $approvedCollections = Collection::whereHas('customer', function ($query) use ($contract) {
    //                 $query->where('company_id', $contract->company_id);
    //             })
    //             ->where('status', 'approved')
    //             ->with('customer')
    //             ->get();

    //         $totalCollectedAmount = $approvedCollections->sum('collected_amount');
            
    //         // حساب نسبة الشركة من العمولات
    //         $commissions = $contract->commissions;
    //         $companyCommissionAmount = 0;
    //         $averageCommissionRate = 0;

    //         if ($commissions->count() > 0) {
    //             // حساب متوسط نسبة العمولة النشطة فقط
    //             $activeCommissions = $commissions->where('is_active', 1);
    //             if ($activeCommissions->count() > 0) {
    //                 $averageCommissionRate = $activeCommissions->avg('commission_rate') ?? 0;
                    
    //                 // حساب قيمة عمولة الشركة
    //                 $companyCommissionAmount = ($totalCollectedAmount * $averageCommissionRate) / 100;
    //             }
    //         }

    //         // صافي المبلغ بعد خصم عمولة الشركة
    //         $netAmount = $totalCollectedAmount - $companyCommissionAmount;
            

    //         return [
    //             'contract' => $contract,
    //             'total_collected_amount' => $totalCollectedAmount,
    //             'approved_collections_count' => $approvedCollections->count(),
    //             'average_commission_rate' => $averageCommissionRate,
    //             'company_commission_amount' => $companyCommissionAmount,
    //             'net_amount' => $netAmount,
    //             'collections' => $approvedCollections,
    //             'commissions' => $commissions,
    //         ];
    //     });

    //     // إحصائيات عامة
    //     $statistics = [
    //         'total_contracts' => $contracts->count(),
    //         'total_collected' => $contractsReport->sum('total_collected_amount'),
    //         'total_company_commission' => $contractsReport->sum('company_commission_amount'),
    //         'total_net_amount' => $contractsReport->sum('net_amount'),
    //         'average_commission_rate' => $contractsReport->avg('average_commission_rate'),
    //     ];

    //     return view("tenants.reports.contracts.index", compact('contractsReport', 'statistics'));
    // }

    // دالة لعرض تفاصيل عقد معين
    public function show($id)
    {
        $contract = Contract::with(['company', 'commissions'])
            ->findOrFail($id);

        // جلب المبالغ المحصلة المعتمدة للعملاء التابعين لنفس الشركة
        $approvedCollections = Collection::whereHas('customer', function ($query) use ($contract) {
                $query->where('company_id', $contract->company_id);
            })
            ->where('status', 'approved')
            ->with(['customer', 'paymentMethod'])
            ->orderBy('collection_date', 'desc')
            ->get();

        $totalCollectedAmount = $approvedCollections->sum('collected_amount');
        
        // حساب نسبة الشركة من العمولات
        $commissions = $contract->commissions;
        $companyCommissionAmount = 0;
        $averageCommissionRate = 0;

        if ($commissions->count() > 0) {
            $activeCommissions = $commissions->where('is_active', 1);
            if ($activeCommissions->count() > 0) {
                $averageCommissionRate = $activeCommissions->avg('commission_rate') ?? 0;
                $companyCommissionAmount = ($totalCollectedAmount * $averageCommissionRate) / 100;
            }
        }

        $netAmount = $totalCollectedAmount - $companyCommissionAmount;

        return view("tenants.reports.contracts.show", compact(
            'contract',
            'approvedCollections',
            'totalCollectedAmount',
            'averageCommissionRate',
            'companyCommissionAmount',
            'netAmount',
            'commissions'
        ));
    }
}