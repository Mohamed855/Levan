<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Customer;

use App\DataTables\Tenants\BusinessCenter\Customer\CustomersDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\BusinessCenter\Customer\StoreCustomerRequest;
use App\Http\Requests\Tenants\BusinessCenter\Customer\UpdateCustomerRequest;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Phone\CustomerPhone;
use App\Models\Tenants\FinancialCollection\Collection\CustomerTransaction;
use App\Models\Tenants\GeneralSetting\ContactResult\ContactResult;
use App\Models\Tenants\GeneralSetting\MessageTemplate\MessageTemplate;
use App\Models\Tenants\GeneralSetting\CustomerStatus\SettingsCustomerStatus;
use App\Services\Tenants\BusinessCenter\Customer\CustomerService;
use App\Services\Tenants\SMS\Customer\CustomerSMSService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\Facades\DataTables;

class CustomerController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Constructor & Dependencies
    |--------------------------------------------------------------------------
    | Initialize controller with required dependencies and middleware.
    */
    protected CustomerService $customerService;
    protected CustomerSMSService $customerSMSService;

    public function __construct(CustomerService $customerService, CustomerSMSService $customerSMSService)
    {
        $this->customerService = $customerService;
        $this->customerSMSService = $customerSMSService;

        $this->middleware('auth');

        $this->middleware(function ($request, $next) {

            $currentRoute = optional($request->route())->getName() ?? '';
            $hasAllCustomers = auth()->user()->can('customers.view');
            $hasMyCustomers = auth()->user()->can('customers.my_view');

            if (str_ends_with($currentRoute, 'customers.index')) {
                if (!$hasAllCustomers) {
                    if ($hasMyCustomers) {
                        return redirect(tenant_route_name('customers.my-customers'));
                    }
                    abort(403, 'ليس لديك صلاحية للوصول لهذه الصفحة');
                }
            } elseif (str_ends_with($currentRoute, 'customers.my-customers')) {
                if (!$hasMyCustomers) {
                    abort(403, 'ليس لديك صلاحية لعرض عملائك');
                }
            }

            return $next($request);
        })->only(['index']);

        // $this->middleware('can:العملاء')->only(['index']);
        $this->middleware('can:customers.create')->only(['create', 'store']);
        $this->middleware('can:customers.edit')->only(['edit', 'update']);
        $this->middleware('can:customers.delete')->only(['destroy']);
        $this->middleware('can:customers.send_message')->only(['sendSMS', 'showSMSForm']);
    }

    /*
    |--------------------------------------------------------------------------
    | Display Customers List
    |--------------------------------------------------------------------------
    | Display a listing of customers with DataTables integration and statistics.
    */
    public function index(CustomersDataTable $dataTable)
    {
        $viewType = $this->getViewType(request());
        $statistics = $this->customerService->getCustomerStatistics($viewType);
        $formData = $this->customerService->getFormData();
        $contactResults = ContactResult::where('status', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $baseCustomerQuery = Customer::query();
        if ($viewType === 'my') {
            $baseCustomerQuery->where('relationship_manager_id', auth()->id());
        }
        $totalCustomersForView = $baseCustomerQuery->count();

        return $dataTable->render('tenants.business_center.customers.index', [
            'viewType' => $viewType,
            'totalCustomers' => $totalCustomersForView,
            'individualCustomers' => $statistics['individual'],
            'companyCustomers' => $statistics['company'],
            'statistics' => $statistics, // ✅ تمرير كل الإحصائيات
            'companies' => $formData['companies'],
            'portfolios' => $formData['portfolios'],
            'countries' => $formData['countries'],
            'users' => $formData['users'],
            'customerStatuses' => $formData['customer_statuses'],
            'contactResults' => $contactResults,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Create Form
    |--------------------------------------------------------------------------
    | Show the form for creating a new customer.
    */
    public function create(): View
    {
        $formData = $this->customerService->getFormData();
        return view('tenants.business_center.customers.create', $formData);
    }

    /*
    |--------------------------------------------------------------------------
    | Store New Customer
    |--------------------------------------------------------------------------
    | Store a newly created customer in storage.
    */
    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        try {
            $this->customerService->createCustomer($request->validated());

            return redirect()
                ->to(tenant_route_name('customers.index'))
                ->with('success', 'تم إضافة العميل بنجاح');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء إضافة العميل');
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Show Customer Details
    |--------------------------------------------------------------------------
    | Display the specified customer with all details and tabs.
    */
    public function show(Customer $customer): View
    {
        if (
            !auth()->user()->can('customers.view') &&
            $customer->relationship_manager_id !== auth()->id()
        ) {
            abort(403, 'ليس لديك صلاحية لعرض هذا العميل');
        }

        $allAccounts = $this->customerService->getCustomerAccountsByIdentity($customer->identity_number);
        $comprehensiveStats = $this->customerService->getComprehensiveFinancialStats($customer->identity_number);

        $customer->load([
            'company:id,name,logo_path',
            'portfolio:id,name',
            'nationalityCountry:id,name',
            'relationshipManager:id,name',
            'createdBy:id,name',
            'customerStatus:id,name',
            'phones',
            'dynamicValues.portfolioField'
        ]);

        $customerStats = [
            'total_messages' => 0,
            'payment_plans' => 0,
            'settlement_requests' => 0,
            'payment_details' => 0,
            'update_requests' => 0,
            'circular_requests' => 0,
            'notes' => 0,
            'attachments' => 0,
        ];

        $templates = \Illuminate\Support\Facades\Schema::hasTable('message_templates')
            ? MessageTemplate::active()
                ->where('category', 'customers')
                ->select('id', 'name', 'content')
                ->orderBy('name')
                ->get()
            : collect();

        return view('tenants.business_center.customers.show', [
            'customer' => $customer,
            'allAccounts' => $allAccounts,
            'comprehensiveStats' => $comprehensiveStats,
            'customerStats' => $customerStats,
            'templates' => $templates
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Edit Form
    |--------------------------------------------------------------------------
    | Show the form for editing the specified customer.
    */
    public function edit(Customer $customer): View
    {
        if (
            !auth()->user()->can('customers.edit_all') &&
            !(auth()->user()->can('تعديل عميل') && $customer->relationship_manager_id === auth()->id())
        ) {
            abort(403, 'ليس لديك صلاحية لتعديل هذا العميل');
        }

        $customer->load('dynamicValues');
        $formData = $this->customerService->getFormData();

        $financialStats = $this->customerService->getCustomerFinancialStats($customer->id);

        return view('tenants.business_center.customers.edit', array_merge($formData, [
            'customer' => $customer,
            'financialStats' => $financialStats
        ]));
    }

    /*
    |--------------------------------------------------------------------------
    | Update Customer
    |--------------------------------------------------------------------------
    | Update the specified customer in storage.
    */
    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        if (
            !auth()->user()->can('customers.edit_all') &&
            !(auth()->user()->can('تعديل عميل') && $customer->relationship_manager_id === auth()->id())
        ) {
            abort(403, 'ليس لديك صلاحية لتعديل هذا العميل');
        }

        try {
            if (auth()->user()->can('customers.update_directly')) {
                $this->customerService->updateCustomer($customer, $request->validated());
                return redirect()
                    ->to(tenant_route_name('customers.index'))
                    ->with('success', 'تم تحديث العميل بنجاح');
            } else {
                try {
                    $this->customerService->createUpdateRequest($customer, $request->validated());
                    return redirect()
                        ->to(tenant_route_name('customers.index'))
                        ->with('success', 'تم إرسال طلب التحديث للموافقة');
                } catch (\InvalidArgumentException $e) {
                    return redirect()
                        ->back()
                        ->withInput()
                        ->with('warning', $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث العميل');
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Has Pending Update Requests
    |--------------------------------------------------------------------------
    |  إضافة دالة جديدة للتعامل مع طلبات التحديث.
    */
    public function hasPendingUpdateRequests(Customer $customer): bool
    {
        return $customer->pendingUpdateRequests()->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Customer
    |--------------------------------------------------------------------------
    | Remove the specified customer from storage.
    */
    public function destroy(Customer $customer): RedirectResponse
    {

        if (
            !auth()->user()->can('customers.delete_all') &&
            !(auth()->user()->can('حذف عميل') && $customer->relationship_manager_id === auth()->id())
        ) {
            abort(403, 'ليس لديك صلاحية لحذف هذا العميل');
        }
        try {
            $this->customerService->deleteCustomer($customer);

            return redirect()
                ->back()
                ->with('success', 'تم حذف العميل بنجاح');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'حدث خطأ أثناء حذف العميل');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Available Customer Statuses
    |--------------------------------------------------------------------------
    */
    public function getAvailableStatuses(): JsonResponse
    {
        $statuses = SettingsCustomerStatus::where('status', 'active')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $statuses
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Portfolios by Company
    |--------------------------------------------------------------------------
    | Get portfolios filtered by company for AJAX requests.
    */
    public function getPortfoliosByCompany(Request $request): JsonResponse
    {
        Log::error(444);
        $companyId = $request->get('company_id');

        if (!$companyId) {
            return response()->json(['success' => false, 'message' => 'معرف الشركة مطلوب']);
        }

        $portfolios = $this->customerService->getPortfoliosByCompany($companyId);

        return response()->json([
            'success' => true,
            'data' => $portfolios
        ]);
    }



    /*
    |--------------------------------------------------------------------------
    | Extract Customer IDs
    |--------------------------------------------------------------------------
    | يُرسل الطرف الأمامي المعرفات كنص JSON في حقل واحد لتفادي حد
    | max_input_vars الذي كان يقصّ التحديد عند إرسالها كمصفوفة حقول.
    */
    private function extractCustomerIds(Request $request): array
    {
        $raw = $request->input('customer_ids', []);

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($raw) ? $raw : [];
    }






    /*
    |--------------------------------------------------------------------------
    | Get All Customer IDs with View Type Support
    |--------------------------------------------------------------------------
    | Get customer IDs based on filters and view type (all/my customers).
    */
    public function getAllCustomerIds(Request $request)
    {
        try {
            $viewType = $request->get('view_type', 'all');

            $query = $this->customerService->getCustomersQuery($viewType);

            if ($request->filled('customer_status_id')) {
                $query->where('customer_status_id', $request->customer_status_id);
            }

            if ($request->filled('last_contact_result_id')) {
                $val = $request->last_contact_result_id;
                if ($val === '__none') {
                    $query->whereNull('last_contact_result_id');
                } else {
                    $query->where('last_contact_result_id', $val);
                }
            }

            if ($request->filled('customer_type')) {
                $query->where('customer_type', $request->customer_type);
            }

            if ($request->filled('company_id')) {
                $query->where('company_id', $request->company_id);
            }

            if ($request->filled('portfolio_id')) {
                $query->where('portfolio_id', $request->portfolio_id);
            }

            if ($request->filled('nationality')) {
                $query->where('nationality', $request->nationality);
            }

            if ($request->filled('relationship_manager_id')) {
                $managerIds = $request->relationship_manager_id;

                if (is_array($managerIds) && count($managerIds) > 0) {
                    $query->whereIn('relationship_manager_id', $managerIds);
                } elseif (!is_array($managerIds) && !empty($managerIds)) {
                    $query->where('relationship_manager_id', $managerIds);
                }
            }

            if ($request->filled('debt_min')) {
                $min = (float) $request->debt_min;
                $query->havingRaw('remaining_debt >= ?', [$min]);
            }

            if ($request->filled('debt_max')) {
                $max = (float) $request->debt_max;
                $query->havingRaw('remaining_debt <= ?', [$max]);
            }

            if ($request->filled('sms_status')) {
                if ($request->sms_status === 'sent') {
                    $query->whereNotNull('last_sms_at');
                } elseif ($request->sms_status === 'not_sent') {
                    $query->whereNull('last_sms_at');
                }
            }

            if ($request->filled('sms_date_from')) {
                $query->where('last_sms_at', '>=', $request->sms_date_from . ' 00:00:00');
            }

            if ($request->filled('sms_date_to')) {
                $query->where('last_sms_at', '<=', $request->sms_date_to . ' 23:59:59');
            }

            // نفس منطق البحث العام المستخدم في CustomersDataTable حتى يتطابق
            // "تحديد الكل" مع نتائج البحث الظاهرة فعليًا في الجدول.
            if ($request->filled('search')) {
                $cleanSearch = trim(preg_replace('/\s+/', ' ', $request->input('search')));
                $searchWords = explode(' ', $cleanSearch);

                $query->where(function ($q) use ($cleanSearch, $searchWords) {
                    $q->where('name', 'like', "%{$cleanSearch}%")
                        ->orWhere('identity_number', 'like', "%{$cleanSearch}%")
                        ->orWhere('phone', 'like', "%{$cleanSearch}%")
                        ->orWhere('account_number', 'like', "%{$cleanSearch}%");

                    if (count($searchWords) > 1) {
                        $q->orWhere(function ($subQuery) use ($searchWords) {
                            foreach ($searchWords as $word) {
                                if (strlen($word) >= 2) {
                                    $subQuery->where('name', 'like', "%{$word}%");
                                }
                            }
                        });
                    }

                    $q->orWhereHas('phones', function ($phoneQuery) use ($cleanSearch) {
                        $phoneQuery->where('phone', 'like', "%{$cleanSearch}%");
                    })
                        ->orWhereHas('company', function ($companyQuery) use ($cleanSearch) {
                            $companyQuery->where('name', 'like', "%{$cleanSearch}%");
                        })
                        ->orWhereHas('portfolio', function ($portfolioQuery) use ($cleanSearch) {
                            $portfolioQuery->where('name', 'like', "%{$cleanSearch}%");
                        })
                        ->orWhereHas('relationshipManager', function ($managerQuery) use ($cleanSearch) {
                            $managerQuery->where('name', 'like', "%{$cleanSearch}%");
                        })
                        ->orWhereHas('customerStatus', function ($statusQuery) use ($cleanSearch) {
                            $statusQuery->where('name', 'like', "%{$cleanSearch}%");
                        })
                        ->orWhereHas('dynamicValues', function ($dynQuery) use ($cleanSearch) {
                            $dynQuery->where('value', 'like', "%{$cleanSearch}%");
                        });
                });
            }

            $customerIds = $query->pluck('customers.id')->toArray();

            return response()->json([
                'success' => true,
                'customer_ids' => $customerIds,
                'count' => count($customerIds)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب البيانات',
                'customer_ids' => []
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show SMS Form
    |--------------------------------------------------------------------------
    | عرض نموذج إرسال الرسائل للعملاء المحددين
    */
    public function showSMSForm(Request $request): JsonResponse
    {
        $request->validate([
            'customer_ids' => 'required',
            // 'customer_ids.*' => 'exists:customers,id',
        ]);

        $customerIds = $this->prepareCustomerIds($request->input('customer_ids'));

        if (empty($customerIds)) {
            return response()->json([
                'success' => false,
                'message' => 'يرجى تحديد عميل واحد على الأقل'
            ], 400);
        }

        $result = $this->customerSMSService->prepareCustomersForSMSForm($customerIds);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }


    /*
    |--------------------------------------------------------------------------
    | Send SMS to Customers - تم تبسيطه وإزالة التسجيل المكرر
    |--------------------------------------------------------------------------
    | إرسال الرسائل النصية للعملاء باستخدام SMSService فقط
    */
    public function sendSMS(Request $request): JsonResponse
    {
        // التحقق الأساسي
        $request->validate([
            'customer_ids' => 'required',
            'message_type' => 'required|in:template,custom',
        ]);

        // تحقق مشروط حسب نوع الرسالة
        if ($request->message_type === 'template') {
            $request->validate([
                'template_id' => 'required|exists:message_templates,id',
            ], [
                'template_id.required' => 'يرجى اختيار قالب الرسالة',
                'template_id.exists' => 'القالب المحدد غير موجود',
            ]);
        } else {
            $request->validate([
                'custom_message' => 'required|string|max:1000',
            ], [
                'custom_message.required' => 'يرجى كتابة نص الرسالة',
                'custom_message.max' => 'نص الرسالة يتجاوز الحد الأقصى المسموح (1000 حرف)',
            ]);
        }

        try {
            $customerIds = $this->prepareCustomerIds($request->customer_ids);

            if (empty($customerIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'يرجى تحديد عميل واحد على الأقل'
                ], 400);
            }

            // استخدام الطابور دائماً
            if ($request->message_type === 'template') {
                $result = $this->customerSMSService->sendTemplateMessageWithQueue(
                    $request->template_id,
                    $customerIds
                );
            } else {
                $result = $this->customerSMSService->sendCustomMessageWithQueue(
                    $request->custom_message,
                    $customerIds
                );
            }

            // إضافة الرسالة في الـ Controller
            if ($result['success']) {
                $result['message'] = 'تم إرسال الرسائل بنجاح';
            }

            return response()->json($result, $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال الرسائل: ' . $e->getMessage()
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Preview Template Message
    |--------------------------------------------------------------------------
    | معاينة الرسالة بالمتغيرات
    */
    public function previewTemplateMessage(Request $request): JsonResponse
    {
        $request->validate([
            'template_id' => 'required|exists:message_templates,id',
            'customer_id' => 'nullable|exists:customers,id'
        ]);

        try {
            $result = $this->customerSMSService->previewTemplateMessage(
                $request->template_id,
                $request->customer_id
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء معاينة الرسالة'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    private function prepareCustomerIds($customerIds): array
    {
        // إذا كان string، حوله إلى array
        if (is_string($customerIds)) {
            $customerIds = explode(',', $customerIds);
        }

        // إذا كان array بالفعل، استخدمه مباشرة
        if (is_array($customerIds)) {
            return array_filter(array_map('intval', $customerIds));
        }

        // إذا لم يكن أي منهما، أرجع array فارغ
        return [];
    }


    /*
    |--------------------------------------------------------------------------
    | الحصول على إحصائيات الرسائل النصية للعملاء
    |--------------------------------------------------------------------------
    */
    public function getSMSStatistics(): JsonResponse
    {
        try {
            $statistics = $this->customerSMSService->getCustomerSMSStatistics();
            $recentMessages = $this->customerSMSService->getRecentCustomerMessages(5);

            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => $statistics,
                    'recent_messages' => $recentMessages
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الإحصائيات'
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | جلب الحقول المرتبطة بالمحفظة
    |--------------------------------------------------------------------------
    */
    public function getPortfolioFields(Request $request): JsonResponse
    {
        $request->validate([
            'portfolio_id' => 'required|exists:portfolios,id',
        ]);

        $fields = $this->customerService
            ->getPortfolioFields((int)$request->portfolio_id);

        return response()->json(['success' => true, 'data' => $fields]);
    }

    /////////////////////////////////////////////////////////////////////////////////
    /*
    |--------------------------------------------------------------------------
    | الدالة الخاصة بالعملاء حسب الصلاحية جميع العملاء  - عملائي
    |--------------------------------------------------------------------------
    */
    private function getViewType(Request $request): string
    {
        $routeName = optional($request->route())->getName() ?? '';

        return str_ends_with($routeName, 'customers.my-customers') ? 'my' : 'all';
    }

    /*
    |--------------------------------------------------------------------------
    | الدالة الخاصة بالعملاء حسب الصلاحية جميع العملاء - عملائي
    |--------------------------------------------------------------------------
    | التحقق من الصلاحية هل هي العملاء او عملائي (منفصلة)
    */
    private function canAccessViewType(string $viewType): bool
    {
        if ($viewType === 'all') {
            return auth()->user()->can('customers.view');
        } else {
            return auth()->user()->can('customers.my_view');
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Set Primary Phone
    |--------------------------------------------------------------------------
    */
    public function setPrimaryPhone(Request $request, Customer $customer): JsonResponse
    {
        if (!auth()->user()->can('customers.add_phone')) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لتعديل هذا العميل'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'phone_id' => 'required|integer|exists:customer_phones,id'
        ], [
            'phone_id.required' => 'معرف رقم الهاتف مطلوب',
            'phone_id.integer' => 'معرف رقم الهاتف يجب أن يكون رقمًا صحيحًا',
            'phone_id.exists' => 'رقم الهاتف المحدد غير موجود'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $phoneId = (int) $request->phone_id;

            $phone = CustomerPhone::where('id', $phoneId)->where('customer_id', $customer->id)->first();

            if (!$phone) {
                return response()->json([
                    'success' => false,
                    'message' => 'رقم الهاتف غير موجود أو لا ينتمي لهذا العميل'
                ], 404);
            }

            if ($phone->is_primary) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا الرقم افتراضي بالفعل'
                ], 409);
            }

            DB::transaction(function () use ($customer, $phone) {
                CustomerPhone::where('customer_id', $customer->id)
                    ->update(['is_primary' => false]);
                $phone->update(['is_primary' => true]);
            });

            return response()->json([
                'success' => true,
                'message' => 'تم تعيين رقم الهاتف كافتراضي بنجاح',
                'data' => [
                    'phone_id' => $phone->id,
                    'phone_number' => $phone->phone,
                    'customer_id' => $customer->id
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تعيين الرقم الافتراضي: ' . $e->getMessage()
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Get customer phone number for WhatsApp sharing
    |--------------------------------------------------------------------------
    */
    public function getCustomerPhoneNumber(Customer $customer): JsonResponse
    {
        try {
            // إرجاع جميع أرقام الهواتف
            $phones = [];

            // إضافة الهاتف الأساسي
            if ($customer->phone) {
                $phones[] = [
                    'phone' => $customer->phone,
                    'type' => 'أساسي',
                    'formatted' => $this->formatSaudiPhoneNumberForWhatsApp($customer->phone)
                ];
            }

            // إضافة بقية الأرقام
            foreach ($customer->phones as $phone) {
                $type = $phone->is_primary ? 'افتراضي' : 'إضافي';
                $phones[] = [
                    'phone' => $phone->phone,
                    'type' => $type,
                    'formatted' => $this->formatSaudiPhoneNumberForWhatsApp($phone->phone)
                ];
            }

            if (!empty($phones)) {
                return response()->json([
                    'success' => true,
                    'phones' => $phones,
                    'primary_phone' => $customer->phone
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'لا يوجد رقم هاتف لهذا العميل'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الحصول على رقم الهاتف: ' . $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Format Saudi Phone Number for WhatsApp --  Whatsapp Sharing
    |--------------------------------------------------------------------------
    | تنسيق رقم الهاتف للمشاركة عبر للواتساب
    */
    private function formatSaudiPhoneNumberForWhatsApp($phone)
    {
        // إزالة أي مسافات أو رموز غير مرغوب فيها
        $phone = preg_replace('/[\s\-()]/', '', $phone);

        // إذا كان الرقم يبدأ بـ 05 (الصيغة السعودية المحلية)
        if (strpos($phone, '05') === 0 && strlen($phone) === 10) {
            return '966' . substr($phone, 1);
        }
        // إذا كان الرقم يبدأ بـ 5 بدون صفر (الصيغة السعودية المختصرة)
        elseif (strpos($phone, '5') === 0 && strlen($phone) === 9) {
            return '966' . $phone;
        }
        // إذا كان الرقم يبدأ بـ +966 (الصيغة السعودية الدولية مع +)
        elseif (strpos($phone, '+966') === 0) {
            return substr($phone, 1);
        }
        // إذا كان الرقم يبدأ بـ 966 بدون + (الصيغة السعودية الدولية)
        elseif (strpos($phone, '966') === 0) {
            return $phone;
        }

        // إذا لم يكن الرقم سعودياً، أعد الرقم كما هو
        return $phone;
    }

    /*
    |--------------------------------------------------------------------------
    | Preview WhatsApp Template Message  --  Whatsapp Sharing
    |--------------------------------------------------------------------------
    | معاينة قالب الواتساب مع استبدال المتغيرات
    */
    public function previewWhatsAppTemplate(Request $request): JsonResponse
    {
        $request->validate([
            'template_id' => 'required|exists:message_templates,id',
            'customer_id' => 'required|exists:customers,id'
        ]);

        try {
            $template = MessageTemplate::findOrFail($request->template_id);
            $customer = Customer::with([
                'nationalityCountry',
                'customerStatus',
                'relationshipManager',
                'company',
                'portfolio',
                'dynamicValues.portfolioField',
                'phones'
            ])->findOrFail($request->customer_id);

            // حساب المديونية الصحيحة
            $totalDebit = CustomerTransaction::where('customer_id', $customer->id)
                ->where('type', 'debit')
                ->sum('amount');

            $totalCredit = CustomerTransaction::where('customer_id', $customer->id)
                ->where('type', 'credit')
                ->sum('amount');

            $remainingDebt = $totalDebit - $totalCredit;

            $content = $template->content;

            // استبدال المتغيرات بالبيانات الفعلية
            $content = str_replace('{customer_name}', $customer->name ?? '', $content);
            $content = str_replace('{first_name}', $customer->first_name ?? '', $content);
            $content = str_replace('{father_name}', $customer->father_name ?? '', $content);
            $content = str_replace('{grandfather_name}', $customer->grandfather_name ?? '', $content);
            $content = str_replace('{family_name}', $customer->family_name ?? '', $content);
            $content = str_replace('{customer_type_label}', $customer->customer_type === 'individual' ? 'فردي' : 'شركة', $content);

            $identityNumber = $customer->identity_number ?? '';
            if (strlen($identityNumber) > 4) {
                $maskedIdentity = str_repeat('*', strlen($identityNumber) - 4) . substr($identityNumber, -4);
            } else {
                $maskedIdentity = $identityNumber;
            }
            $content = str_replace('{identity_number}', $maskedIdentity, $content);

            // تحضير أرقام الهواتف
            $phones = [];
            if ($customer->phone) {
                $phones[] = $customer->phone . ' (أساسي)';
            }

            foreach ($customer->phones as $phone) {
                $phoneLabel = $phone->is_primary ? ' (افتراضي)' : ' (إضافي)';
                $phones[] = $phone->phone . $phoneLabel;
            }

            $phonesList = implode(' - ', $phones);
            $content = str_replace('{phone}', $phonesList, $content);

            $content = str_replace('{account_number}', $customer->account_number ?? '', $content);
            $content = str_replace('{formatted_debt_amount}', number_format($remainingDebt, 2), $content);
            $content = str_replace('{formatted_commission_rate}', $customer->commission_rate ? $customer->commission_rate . '%' : '', $content);
            $content = str_replace('{formatted_contract_date}', $customer->contract_date ?? '', $content);
            $content = str_replace('{formatted_default_date}', $customer->default_date ?? '', $content);
            $content = str_replace('{nationality}', $customer->nationalityCountry->name ?? '', $content);
            $content = str_replace('{status_label}', $customer->customerStatus->name ?? '', $content);
            $content = str_replace('{relationship_manager}', $customer->relationshipManager->name ?? '', $content);
            $content = str_replace('{company_name}', $customer->company->name ?? '', $content);
            $content = str_replace('{portfolio_name}', $customer->portfolio->name ?? '', $content);
            $content = str_replace('{service_number}', $this->getServiceNumber($customer), $content);
            // إضافة متغير رقم المطالبة
            $content = str_replace('{claim_number}', $this->getClaimNumber($customer), $content);
            $content = str_replace('{date}', now()->format('Y-m-d'), $content);
            $content = str_replace('{time}', now()->format('H:i'), $content);
            $content = str_replace('{datetime}', now()->format('Y-m-d H:i'), $content);
            $content = str_replace('{year}', now()->format('Y'), $content);
            $content = str_replace('{month}', now()->format('m'), $content);
            $content = str_replace('{day}', now()->format('d'), $content);

            return response()->json([
                'success' => true,
                'preview' => $content,
                'phones' => $customer->phones,
                'primary_phone' => $customer->phone
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء معالجة القالب: ' . $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على رقم الخدمة من بيانات العميل الديناميكية  --  Whatsapp Sharing
    |--------------------------------------------------------------------------
    | تبحث هذه الدالة في الحقول الديناميكية للعميل وتعيد قيمة حقل "رقم الخدمة" إذا وُجد.
    */
    private function getServiceNumber(Customer $customer): string
    {
        foreach ($customer->dynamicValues as $dynamicValue) {
            if (!$dynamicValue->portfolioField) {
                continue;
            }

            $fieldName = strtolower(trim($dynamicValue->portfolioField->field_name));

            if (in_array($fieldName, [
                'service_number',
                'رقم الخدمة',
                'service_no',
                'خدمة',
                'رقم خدمة'
            ])) {
                return $dynamicValue->value ?? '';
            }
        }

        return '';
    }


    /*
    |--------------------------------------------------------------------------
    | الحصول على رقم المطالبة من بيانات العميل الديناميكية  --  Whatsapp Sharing
    |--------------------------------------------------------------------------
    | تبحث هذه الدالة في الحقول الديناميكية للعميل وتعيد قيمة حقل "رقم المطالبة" إذا وُجد.
    */
    private function getClaimNumber(Customer $customer): string
    {
        foreach ($customer->dynamicValues as $dynamicValue) {
            if (!$dynamicValue->portfolioField) {
                continue;
            }

            $fieldName = strtolower(trim($dynamicValue->portfolioField->field_name));

            if (in_array($fieldName, [
                'claim_number',
                'رقم المطالبة',
                'claim_no',
                'مطالبة',
                'رقم مطالبة'
            ])) {
                return $dynamicValue->value ?? '';
            }
        }

        return '';
    }
}
