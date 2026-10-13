<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Contract;

use App\DataTables\Tenants\BusinessCenter\Contract\ContractsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\BusinessCenter\Contract\CheckContractNumberRequest;
use App\Http\Requests\Tenants\BusinessCenter\Contract\StoreContractRequest;
use App\Http\Requests\Tenants\BusinessCenter\Contract\UpdateContractRequest;
use App\Http\Requests\Tenants\UpdateResourceStatusRequest;
use App\Models\Tenants\BusinessCenter\Contract\Contract;
use App\Models\Tenants\BusinessCenter\Contract\ContractCommission;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use App\Services\Tenants\BusinessCenter\Contract\ContractService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ContractController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Service Dependency
    |--------------------------------------------------------------------------
    */
    protected ContractService $contractService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct(ContractService $contractService)
    {
        $this->contractService = $contractService;
        $this->authorizeResource(Contract::class, 'contract');
        $this->middleware('auth');
        $this->middleware('can:contracts.view')->only(['index', 'show']);
        $this->middleware('can:contracts.create')->only(['create', 'store']);
        $this->middleware('can:contracts.edit')->only(['edit', 'update', 'toggleStatus']);
        $this->middleware('can:contracts.delete')->only(['destroy']);
    }

    /*
    |--------------------------------------------------------------------------
    | Display Contracts List
    |--------------------------------------------------------------------------
    */
    public function index(ContractsDataTable $dataTable)
    {
        try {
            // Log::info('=== بداية تحميل صفحة العقود ===');
            // Log::info('المستخدم الحالي', ['user_id' => auth()->id(), 'user_name' => auth()->user()->name ?? 'غير معروف']);

            // جلب الإحصائيات
            // Log::info('محاولة جلب الإحصائيات...');
            $statistics = $this->contractService->getContractStatistics();
            // Log::info('✅ تم جلب الإحصائيات بنجاح', ['statistics' => $statistics]);

            // جلب الشركات
            // Log::info('محاولة جلب الشركات...');
            $companies = $this->contractService->getAvailableCompanies();
            // Log::info('✅ تم جلب الشركات بنجاح', ['companies_count' => $companies->count()]);

            // عرض الصفحة
            // Log::info('محاولة عرض الصفحة مع DataTable...');
            $result = $dataTable->render('tenants.business_center.contracts.index', [
                'totalContracts' => $statistics['total'],
                'activeContracts' => $statistics['active'],
                'expiredContracts' => $statistics['expired'],
                'contractsExpiringSoon' => $statistics['expiring_soon'],
                'commissionTypeStats' => $statistics['by_commission_type'],
                'companies' => $companies,
            ]);

            // Log::info('✅ تم عرض الصفحة بنجاح');
            return $result;
        } catch (\Exception $e) {
            Log::error('❌ خطأ في صفحة العقود', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_code' => $e->getCode(),
                'stack_trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات: '.$e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show Create Form
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        try {
            $companies = $this->contractService->getAvailableCompanies();
            $contractNumber = $this->contractService->generateContractNumber();

            return view('tenants.business_center.contracts.create', compact(
                'companies',
                'contractNumber'
            ));
        } catch (\Exception $e) {
            return redirect()->to(tenant_route_name('contracts.index'))->with('error', 'حدث خطأ أثناء تحميل نموذج إنشاء العقد');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Store New Contract
    |--------------------------------------------------------------------------
    */
    public function store(StoreContractRequest $request): RedirectResponse
    {
        try {
            $contract = $this->contractService->createContract($request->toCreateDTO());

            return redirect()->to(tenant_route_name('contracts.index'))
                ->with('success', 'تم إضافة العقد بنجاح');
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->validator);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()
                ->with('error', 'حدث خطأ أثناء إضافة العقد. يرجى المحاولة مرة أخرى.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show Contract Details
    |--------------------------------------------------------------------------
    */
    public function show(Contract $contract)
    {
        try {
            $contract->load([
                'company:id,name',
                'commissions',
                'creator:id,name',
                'updater:id,name',
            ]);

            // جلب التحصيلات المعتمدة إن وجد جدول التحصيلات
            $approvedCollections = Schema::hasTable('collections')
                ? Collection::whereHas('customer', function ($query) use ($contract) {
                    $query->where('company_id', $contract->company_id);
                })
                    ->where('status', 'approved')
                    ->with(['customer', 'paymentMethod'])
                    ->orderBy('collection_date', 'desc')
                    ->get()
                : collect();

            $totalCollectedAmount = 0;
            $companyCommissionAmount = 0;

            $contract_commission = ContractCommission::where('contract_id', $contract->id)->get();

            // حساب العمولة لكل تحصيل بناءً على شرائح العمولة في العقد
            foreach ($approvedCollections as $collection) {
                $totalCollectedAmount += $collection->collected_amount;

                // تحديد نسبة العمولة المناسبة لهذا التحصيل
                $commissionRate = $this->calculateCommissionRate($contract, $collection);

                // حساب مبلغ العمولة
                $commissionAmount = ($collection->collected_amount * $commissionRate) / 100;

                // إضافة البيانات المحسوبة للتحصيل
                $collection->commission_rate = $commissionRate;
                $collection->commission_amount = $commissionAmount;

                $companyCommissionAmount += $commissionAmount;
            }

            $netAmount = $totalCollectedAmount - $companyCommissionAmount;

            // تجهيز البيانات للعرض
            $financialReport = [
                'total_collected_amount' => $totalCollectedAmount,
                'company_commission_amount' => $companyCommissionAmount,
                'net_amount' => $netAmount,
                'approved_collections_count' => $approvedCollections->count(),
                'collections' => $approvedCollections,
            ];

            return view('tenants.business_center.contracts.show', compact('contract', 'financialReport'));
        } catch (\Exception $e) {
            Log::error('Error in Contract Show: '.$e->getMessage());

            return redirect()->to(tenant_route_name('contracts.index'))
                ->with('error', 'حدث خطأ أثناء تحميل تفاصيل العقد: '.$e->getMessage());
        }
    }

    /**
     * حساب نسبة العمولة المناسبة للتحصيل بناءً على نوع العقد
     */
    private function calculateCommissionRate($contract, $collection)
    {
        $commissions = $contract->commissions->where('is_active', 1);

        if ($commissions->isEmpty()) {
            return 0;
        }

        // إذا كان العقد حسب الإجراء
        if ($contract->commission_type === 'procedure') {
            // البحث عن عمولة من نوع friendly أولاً، ثم legal
            $friendlyCommission = $commissions->where('procedure_type', 'friendly')->first();
            $legalCommission = $commissions->where('procedure_type', 'legal')->first();

            // استخدام أول عمولة متاحة
            return $friendlyCommission ? $friendlyCommission->commission_rate : ($legalCommission ? $legalCommission->commission_rate : 0);
        }

        // إذا كان العقد حسب المدة أو مدة التعثر
        if (in_array($contract->commission_type, ['duration', 'delinquency'])) {
            // التحقق من وجود العميل وتاريخ الإنشاء
            if (! $collection->customer || ! $collection->customer->created_at) {
                // إذا لم يكن هناك تاريخ، نستخدم أول شريحة
                return $commissions->sortBy('duration_from')->first()->commission_rate ?? 0;
            }

            // تحديد تاريخ البداية - استخدام تاريخ التحصيل أو تاريخ إنشاء العميل
            $startDate = $collection->customer->created_at;

            // تحديد تاريخ النهاية - استخدام تاريخ التحصيل أو التاريخ الحالي
            $endDate = $collection->collection_date ?? now();

            // التحقق من أن التواريخ صحيحة
            if (! $startDate || ! $endDate) {
                return $commissions->sortBy('duration_from')->first()->commission_rate ?? 0;
            }

            // حساب عدد الأيام
            try {
                $daysDifference = $startDate->diffInDays($endDate);
            } catch (\Exception $e) {
                // في حالة حدوث خطأ في حساب الأيام، استخدم أول شريحة
                return $commissions->sortBy('duration_from')->first()->commission_rate ?? 0;
            }

            // البحث عن الشريحة المناسبة
            $commission = $commissions
                ->where('duration_from', '<=', $daysDifference)
                ->where('duration_to', '>=', $daysDifference)
                ->first();

            // إذا لم توجد شريحة مناسبة، استخدم آخر شريحة (الأعلى)
            if (! $commission) {
                $commission = $commissions->sortByDesc('duration_to')->first();
            }

            return $commission ? $commission->commission_rate : 0;
        }

        // في حالة عدم وجود نوع معروف، نستخدم أول عمولة نشطة
        return $commissions->first()->commission_rate ?? 0;
    }

    /**
     * تحديد نوع الإجراء بناءً على حالة الدين
     */
    private function getProcedureType($collection)
    {
        // يمكنك تعديل هذا المنطق بناءً على كيفية تحديد نوع الإجراء في نظامك
        // مثلاً من خلال حقل في جدول الديون أو من خلال حالة معينة

        if (! $collection->customer->debt) {
            return 'friendly'; // افتراضي
        }

        // مثال: إذا كان هناك حقل procedure_type في جدول الديون
        if (isset($collection->customer->debt->procedure_type)) {
            return $collection->customer->debt->procedure_type;
        }

        // أو يمكن التحديد بناءً على حالة الدين
        $debtStatus = $collection->customer->debt->status ?? null;

        if (in_array($debtStatus, ['in_court', 'legal_action'])) {
            return 'legal';
        }

        return 'friendly';
    }

    /*
    |--------------------------------------------------------------------------
    | Show Edit Form
    |--------------------------------------------------------------------------
    */
    public function edit(Contract $contract)
    {
        try {
            $companies = $this->contractService->getAvailableCompanies();
            $contractCommissions = $contract->commissions->toArray();

            return view('tenants.business_center.contracts.edit', compact(
                'contract',
                'companies',
                'contractCommissions'
            ));
        } catch (\Exception $e) {
            return redirect()->to(tenant_route_name('contracts.index'))
                ->with('error', 'حدث خطأ أثناء تحميل نموذج تعديل العقد');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update Contract
    |--------------------------------------------------------------------------
    */
    public function update(UpdateContractRequest $request, Contract $contract): RedirectResponse
    {
        try {
            $old = $contract->toArray();
            $updated = $this->contractService->updateContract($contract, $request->toUpdateDTO());

            activity('contracts')
                ->performedOn($updated)
                ->causedBy(auth()->user())
                ->withProperties(['old' => $old, 'new' => $updated->toArray()])
                ->log('تم تحديث العقد رقم '.$updated->contract_number);

            return redirect()->to(tenant_route_name('contracts.index'))
                ->with('success', 'تم تحديث العقد بنجاح');
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->validator);
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'حدث خطأ أثناء تحديث العقد. يرجى المحاولة مرة أخرى.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Contract
    |--------------------------------------------------------------------------
    */
    public function destroy(Contract $contract): RedirectResponse
    {
        try {
            $num = $contract->contract_number;
            $company = $contract->company->name ?? 'غير محدد';

            $this->contractService->deleteContract($contract);

            activity('contracts')
                ->causedBy(auth()->user())
                ->withProperties(['contract_number' => $num, 'company_name' => $company])
                ->log('تم حذف العقد رقم '.$num.' للشركة '.$company);

            return redirect()->back()->with('success', 'تم حذف العقد بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle Contract Status
    |--------------------------------------------------------------------------
    */
    public function toggleStatus(UpdateResourceStatusRequest $request, Contract $contract): JsonResponse
    {
        try {
            if (! auth()->user()->can('contracts.edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية لتعديل حالة العقد',
                ], 403);
            }

            $updated = $this->contractService->toggleStatus($contract, $request->validated('status'));

            return response()->json([
                'success' => true,
                'message' => 'تم تبديل الحالة بنجاح',
                'new_status' => $updated->status,
                'status_text' => $updated->status_label,
                'status_class' => $updated->status_class,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء تبديل الحالة'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Contract Number
    |--------------------------------------------------------------------------
    */
    public function generateContractNumber(): JsonResponse
    {
        try {
            $num = $this->contractService->generateContractNumber();

            return response()->json(['success' => true, 'contract_number' => $num]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء توليد رقم العقد'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Check Contract Number Availability
    |--------------------------------------------------------------------------
    */
    public function checkContractNumber(CheckContractNumberRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $available = $this->contractService->isContractNumberAvailable(
                $validated['contract_number'],
                $validated['contract_id'] ?? null,
            );

            return response()->json([
                'success' => true,
                'available' => $available,
                'message' => $available ? 'رقم العقد متاح' : 'رقم العقد مستخدم مسبقاً',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء التحقق من رقم العقد'], 500);
        }
    }
}
