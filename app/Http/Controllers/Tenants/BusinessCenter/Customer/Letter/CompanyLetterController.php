<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Customer\Letter;

use App\DataTables\Tenants\BusinessCenter\Customer\Letters\CompanyLettersDataTable;
use App\Http\Controllers\Controller;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Customers\Letter\CompanyLetter;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Services\Tenants\BusinessCenter\Customer\Letter\CompanyLetterService;
use Illuminate\Http\Request;
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
        $this->middleware('can:خطابات الشركات')->only(['index', 'show', 'getStatistics']);
        $this->middleware('can:إرسال خطاب')->only(['previewTemplate', 'send']);
    }


    /*
    |--------------------------------------------------------------------------
    | عرض قائمة الخطابات
    |--------------------------------------------------------------------------
    */
    public function index(CompanyLettersDataTable $dataTable): mixed
    {
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
            'portfolios' => $portfolios
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

        $templates = $this->letterService->getTemplatesByType($letter->action_type);

        return view('tenants.business_center.customers.letters.show', [
            'letter' => $letter,
            'templates' => $templates
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | معاينة القالب
    |--------------------------------------------------------------------------
    */
    public function previewTemplate(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'template_id' => 'required|exists:message_templates,id'
        ]);

        try {
            $letter = CompanyLetter::findOrFail($id);
            $preview = $this->letterService->previewTemplate($letter, $request->template_id);

            return response()->json($preview);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء معاينة القالب: ' . $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | إرسال البريد
    |--------------------------------------------------------------------------
    */
    public function send(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'template_id' => 'required|exists:message_templates,id',
            'recipient_email' => 'required|email'
        ], [
            'template_id.required' => 'يرجى اختيار قالب البريد',
            'recipient_email.required' => 'يرجى إدخال البريد الإلكتروني',
            'recipient_email.email' => 'يرجى إدخال بريد إلكتروني صحيح'
        ]);

        try {
            $letter = CompanyLetter::findOrFail($id);

            if ($letter->status === 'sent') {
                return response()->json([
                    'success' => false,
                    'message' => 'تم إرسال هذا الخطاب مسبقاً'
                ], 400);
            }

            $result = $this->letterService->sendEmail($letter, $request->only(['template_id', 'recipient_email']));

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال البريد: ' . $e->getMessage()
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
        try {
            $statistics = $this->letterService->getStatistics();

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الإحصائيات'
            ], 500);
        }
    }
}
