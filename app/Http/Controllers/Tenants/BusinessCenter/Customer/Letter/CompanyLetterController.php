<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Customer\Letter;

use App\DataTables\Tenants\BusinessCenter\Customer\Letters\CompanyLettersDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\BusinessCenter\Customer\Letter\PreviewCompanyLetterRequest;
use App\Http\Requests\Tenants\BusinessCenter\Customer\Letter\SendCompanyLetterRequest;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Customers\Letter\CompanyLetter;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Services\Tenants\BusinessCenter\Customer\Letter\CompanyLetterService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CompanyLetterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Constructor & Dependencies
    |--------------------------------------------------------------------------
    */
    protected CompanyLetterService $letterService;

    public function __construct(CompanyLetterService $letterService)
    {
        $this->letterService = $letterService;
        $this->middleware('auth');
        $this->middleware('can:company_letters.view')->only(['index', 'show', 'getStatistics']);
        $this->middleware('can:company_letters.send')->only(['previewTemplate', 'send']);
    }

    /*
    |--------------------------------------------------------------------------
    | عرض قائمة الخطابات
    |--------------------------------------------------------------------------
    */
    public function index(CompanyLettersDataTable $dataTable): mixed
    {
        $this->authorize('viewAny', CompanyLetter::class);

        $statistics = $this->letterService->getStatistics();

        $companies = Company::active()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $portfolios = Portfolio::active()
            ->select('id', 'name', 'company_id')
            ->orderBy('name')
            ->get();

        return $dataTable->render('tenants.business_center.customers.letters.index', [
            'statistics' => $statistics,
            'companies' => $companies,
            'portfolios' => $portfolios,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | عرض تفاصيل الخطاب
    |--------------------------------------------------------------------------
    */
    public function show(int $id): View
    {
        $letter = $this->letterService->getLetterDetails($id);
        $this->authorize('view', $letter);

        $templates = $this->letterService->getTemplatesByType($letter->action_type);

        return view('tenants.business_center.customers.letters.show', [
            'letter' => $letter,
            'templates' => $templates,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | معاينة القالب
    |--------------------------------------------------------------------------
    */
    public function previewTemplate(PreviewCompanyLetterRequest $request, int $id): JsonResponse
    {
        try {
            $letter = CompanyLetter::findOrFail($id);
            $this->authorize('send', $letter);
            $preview = $this->letterService->previewTemplate($letter, $request->validated('template_id'));

            return response()->json($preview);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء معاينة القالب: '.$e->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | إرسال البريد
    |--------------------------------------------------------------------------
    */
    public function send(SendCompanyLetterRequest $request, int $id): JsonResponse
    {
        try {
            $letter = CompanyLetter::findOrFail($id);
            $this->authorize('send', $letter);

            if ($letter->status === 'sent') {
                return response()->json([
                    'success' => false,
                    'message' => 'تم إرسال هذا الخطاب مسبقاً',
                ], 400);
            }

            $result = $this->letterService->sendEmail($letter, $request->validated());

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال البريد: '.$e->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على الإحصائيات
    |--------------------------------------------------------------------------
    */
    public function getStatistics(): JsonResponse
    {
        $this->authorize('viewAny', CompanyLetter::class);

        try {
            $statistics = $this->letterService->getStatistics();

            return response()->json([
                'success' => true,
                'data' => $statistics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الإحصائيات',
            ], 500);
        }
    }
}
