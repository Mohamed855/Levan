<?php

namespace App\Http\Controllers\Tenants\FinancialCollection\CollectionProcess;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\FinancialCollection\CollectionProcess\SearchCollectionCustomerRequest;
use App\Http\Requests\Tenants\BusinessCenter\Customer\CustomerFollow\StoreCustomerFollowRequest;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Phone\CustomerPhone;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use App\Models\Tenants\FinancialCollection\Collection\CustomerTransaction;
use App\Models\Tenants\GeneralSetting\MessageTemplate\MessageTemplate;
use App\Services\Tenants\BusinessCenter\Customer\Follow\ContactResultService;
use App\Services\Tenants\BusinessCenter\Customer\Follow\CustomerFollowService;
use App\Services\Tenants\FinancialCollection\CollectionProcess\CollectionProcessService;
use App\Services\Tenants\FinancialCollection\CollectionProcess\CollectionSessionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CollectionProcessController extends Controller
{
    public function __construct(
        private CollectionProcessService $collectionProcessService,
        private CustomerFollowService $customerFollowService,
        private CollectionSessionService $collectionSessionService
    ) {
        $this->middleware('can:collection_process.start');
    }

    // عرض شاشة بدء التحصيل
    public function index(Request $request): View
    {
        $this->collectionSessionService->ensureActiveSession();

        $currentCustomerId      = $request->input('customer_id');
        $currentCustomer        = null;
        $customerData           = null;

        $remainingCustomersCount = $this->collectionProcessService->getRemainingCustomersCount();  // عدد العملاء لدي المستخدم

        if ($currentCustomerId) {
            $currentCustomer = Customer::find($currentCustomerId);
            if ($currentCustomer && $this->collectionProcessService->hasRemainingDebt($currentCustomer->id)) {
                $customerData = $this->collectionProcessService->getCustomerDetails($currentCustomer);
            } else {
                $currentCustomer = null;
            }
        }

        if (!$currentCustomer) {
            $currentCustomer = $this->collectionProcessService->getNextCustomerForUser();
            if ($currentCustomer) {
                $customerData = $this->collectionProcessService->getCustomerDetails($currentCustomer);
            }
        }

        $formData = $this->customerFollowService->getFormData();

        $templates = MessageTemplate::active()->where('category', 'customers')->select('id', 'name', 'content')->orderBy('name')->get();

        return view('tenants.financial_collection.collection_process.index', compact(
            'currentCustomer',
            'customerData',
            'formData',
            'templates',
            'remainingCustomersCount'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | التنقل للعميل التالي
    |--------------------------------------------------------------------------
    */
    public function nextCustomer(Request $request): RedirectResponse
    {
        $currentCustomerId = $request->input('current_customer_id');
        $customer = $this->collectionProcessService->navigateToCustomer('next', $currentCustomerId);

        if (!$customer) {
            return redirect()->to(tenant_route_name('customers.my-customers'))
                ->with('toastr', [
                    'type' => 'info',
                    'message' => 'تم الانتهاء من جميع عملاء التحصيل'
                ]);
        }

        // تحقق مرة أخرى من وجود مديونية للعميل
        if (!$this->collectionProcessService->hasRemainingDebt($customer->id)) {
            return redirect()->to(tenant_route_name('customers.my-customers'))
                ->with('toastr', [
                    'type' => 'info',
                    'message' => 'تم الانتهاء من جميع عملاء التحصيل'
                ]);
        }

        return redirect()->to(tenant_route_name('collection-process.index', ['customer_id' => $customer->id]));
    }

    /*
    |--------------------------------------------------------------------------
    | التنقل للعميل السابق
    |--------------------------------------------------------------------------
    */
    public function prevCustomer(Request $request): RedirectResponse
    {
        $currentCustomerId = $request->input('current_customer_id');
        $customer = $this->collectionProcessService->navigateToCustomer('prev', $currentCustomerId);

        if (!$customer) {
            return redirect()->to(tenant_route_name('collection-process.index'))
                ->with('warning', 'لا يوجد عملاء أخرين');
        }

        return redirect()->to(tenant_route_name('collection-process.index', ['customer_id' => $customer->id]));
    }

    /*
    |--------------------------------------------------------------------------
    | تخطي العميل الحالي
    |--------------------------------------------------------------------------
    */
    public function skipCustomer(Request $request): RedirectResponse
    {
        $currentCustomerId = $request->input('current_customer_id');

        if (!$currentCustomerId) {
            return redirect()->to(tenant_route_name('collection-process.index'))
                ->with('error', 'لا يوجد عميل محدد');
        }

        $customer = Customer::find($currentCustomerId);

        if (!$customer) {
            return redirect()->to(tenant_route_name('collection-process.index'))
                ->with('error', 'العميل غير موجود');
        }

        $nextCustomer = $this->collectionProcessService->skipCustomer($customer);

        if (!$nextCustomer) {
            return redirect()->to(tenant_route_name('collection-process.index'))
                ->with('warning', 'لا يوجد عملاء أخرين');
        }

        return redirect()->to(tenant_route_name('collection-process.index', ['customer_id' => $nextCustomer->id]))
            ->with('info', 'تم تخطي العميل');
    }

    /*
    |--------------------------------------------------------------------------
    | حفظ المتابعة
    |--------------------------------------------------------------------------
    */
    public function storeFollow(StoreCustomerFollowRequest $request, Customer $customer): JsonResponse
    {
        try {
            //  التحقق من وجود سند معلق قبل الإضافة
            $hasPendingCollection = Collection::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->where('customer_id', $customer->id)
                ->where('status', 'pending')
                ->exists();

            if ($hasPendingCollection) {
                return response()->json([
                    'success' => false,
                    'message' => 'يوجد سند سابق بانتظار الاعتماد. يرجى اعتماده أولاً.'
                ], 400);
            }

            //------- التحقق من المديونية المتبقية للعميل  -------\\

            $totalDebit = CustomerTransaction::where('customer_id', $customer->id)
                ->where('type', 'debit')
                ->sum('amount');

            $totalCredit = CustomerTransaction::where('customer_id', $customer->id)
                ->where('type', 'credit')
                ->sum('amount');

            $remainingDebt = $totalDebit - $totalCredit;

            if ($remainingDebt <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'العميل قام بالسداد الكامل. لا يمكن إضافة متابعة جديدة.'
                ], 400);
            }

            //------- التحقق من المديونية المتبقية للعميل  -------\\

            $data = $request->validated();

            //  إضافة معرفات الحسابات المحددة
            if ($request->has('selected_account_ids')) {
                $data['selected_account_ids'] = $request->input('selected_account_ids');
            }

            $follow = $this->customerFollowService->createCustomerFollow(
                $data + ['customer_id' => $customer->id]
            );

            $this->collectionSessionService->recordActivity('follow', $request->contact_result_id);

            if (!$this->collectionProcessService->hasRemainingDebt($customer->id)) {
                $nextCustomer = $this->collectionProcessService->getNextCustomerForUser();

                if ($nextCustomer) {
                    return response()->json([
                        'success' => true,
                        'message' => 'تم حفظ المتابعة بنجاح',
                        'data' => $follow,
                        'redirect_url' => tenant_route_name('collection-process.index', ['customer_id' => $nextCustomer->id])
                    ]);
                }

                return response()->json([
                    'success'       => true,
                    'message'       => 'تم حفظ المتابعة بنجاح - لا يوجد عملاء أخرين',
                    'data'          => $follow,
                    'redirect_url'  => tenant_route_name('collection-process.index'),
                    // 'toastr'        => [
                    //     'type'     => 'info',
                    //     'message'  => 'تم الانتهاء من جميع عملاء التحصيل'
                    // ]
                ]);
            }

            $nextCustomer = $this->collectionProcessService->navigateToCustomer('next', $customer->id);

            if ($nextCustomer) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم حفظ المتابعة بنجاح',
                    'data' => $follow,
                    'redirect_url' => tenant_route_name('collection-process.index', ['customer_id' => $nextCustomer->id])
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ المتابعة بنجاح - لا يوجد عملاء أخرين',
                'data' => $follow,
                'redirect_url' => tenant_route_name('collection-process.index'),
                // 'toastr' => [
                //     'type' => 'info',
                //     'message' => 'تم الانتهاء من جميع عملاء التحصيل'
                // ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ المتابعة: ' . $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على حقول نتيجة الاتصال
    |--------------------------------------------------------------------------
    */
    public function getContactResultFields(Request $request, Customer $customer): JsonResponse
    {
        $contactResultId = $request->input('contact_result_id');

        if (!$contactResultId) {
            return response()->json([
                'success' => false,
                'message' => 'نتيجة الاتصال مطلوبة'
            ]);
        }

        $contactResultService = app(ContactResultService::class);
        $fields = $contactResultService->getResultFields($contactResultId);

        return response()->json([
            'success' => true,
            'data' => $fields
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على مديونية العميل
    |--------------------------------------------------------------------------
    */
    public function getCustomerDebt(Customer $customer): JsonResponse
    {
        $customer->loadSum(['transactions as total_debit' => function ($query) {
            $query->where('type', 'debit');
        }], 'amount');

        $customer->loadSum(['transactions as total_credit' => function ($query) {
            $query->where('type', 'credit');
        }], 'amount');

        $remainingDebt = ($customer->total_debit ?? 0) - ($customer->total_credit ?? 0);

        return response()->json([
            'success' => true,
            'remaining_debt' => $remainingDebt
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | تسجيل خروج الموظف من شاشة التحصيل
    |--------------------------------------------------------------------------
    */
    public function recordExit(): JsonResponse
    {
        try {
            $this->collectionSessionService->recordEmployeeExit();

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الخروج بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الخروج'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | معاينة قالب الواتساب
    |--------------------------------------------------------------------------
    */
    public function previewWhatsAppTemplate(Request $request): JsonResponse
    {
        $request->validate([
            'template_id' => 'required|exists:message_templates,id',
            'customer_id' => 'required|exists:customers,id'
        ]);

        try {
            $template = MessageTemplate::findOrFail($request->template_id);
            $customer = Customer::with(['nationalityCountry', 'customerStatus', 'relationshipManager', 'company', 'portfolio', 'dynamicValues.portfolioField', 'phones'])->findOrFail($request->customer_id);

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
    | الحصول على رقم هاتف العميل للواتساب
    |--------------------------------------------------------------------------
    */
    public function getCustomerPhoneNumberForWhatsApp(Request $request): JsonResponse
    {
        try {
            $customer = Customer::with('phones')->findOrFail($request->customer_id);

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
    | تنسيق رقم الهاتف السعودي للواتساب
    |--------------------------------------------------------------------------
    */
    private function formatSaudiPhoneNumberForWhatsApp($phone)
    {
        $phone = preg_replace('/[\s\-()]/', '', $phone);
        if (strpos($phone, '05') === 0 && strlen($phone) === 10) {
            return '966' . substr($phone, 1);
        } elseif (strpos($phone, '5') === 0 && strlen($phone) === 9) {
            return '966' . $phone;
        } elseif (strpos($phone, '+966') === 0) {
            return substr($phone, 1);
        } elseif (strpos($phone, '966') === 0) {
            return $phone;
        }
        return $phone;
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على رقم الخدمة من بيانات العميل الديناميكية
    |--------------------------------------------------------------------------
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
    | الحصول على رقم المطالبة من بيانات العميل الديناميكية
    |--------------------------------------------------------------------------
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

    /*
    |--------------------------------------------------------------------------
    | إضافة رقم هاتف جديد للعميل
    |--------------------------------------------------------------------------
    */
    public function addCustomerPhone(Request $request, Customer $customer): JsonResponse
    {
        if (
            !auth()->user()->can('customers.edit_all') &&
            !(auth()->user()->can('customers.edit') && $customer->relationship_manager_id === auth()->id())
        ) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لتعديل هذا العميل'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|size:10|regex:/^05[0-9]{8}$/',
            'type' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:255',
        ], [
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.size' => 'رقم الهاتف يجب أن يتكون من 10 أرقام',
            'phone.regex' => 'رقم الهاتف يجب أن يبدأ بـ 05',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $phone = CustomerPhone::create([
                'customer_id' => $customer->id,
                'phone' => $request->phone,
                'type' => $request->type ?? 'mobile',
                'is_primary' => false,
                'notes' => $request->notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة رقم الهاتف بنجاح',
                'data' => [
                    'phone_id' => $phone->id,
                    'phone_number' => $phone->phone,
                    'customer_id' => $customer->id
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة رقم الهاتف: ' . $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | تعيين رقم هاتف افتراضي للعميل
    |--------------------------------------------------------------------------
    */
    public function setPrimaryPhone(Request $request, Customer $customer): JsonResponse
    {
        if (
            !auth()->user()->can('customers.edit_all') &&
            !(auth()->user()->can('customers.edit') && $customer->relationship_manager_id === auth()->id())
        ) {
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

            $phone = CustomerPhone::where('id', $phoneId)
                ->where('customer_id', $customer->id)
                ->first();

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
    | الحصول على الحسابات المرتبطة
    |--------------------------------------------------------------------------
    */
    public function getRelatedAccounts(Customer $customer): JsonResponse
    {
        try {
            if (!$customer->identity_number) {
                return response()->json([
                    'success' => false,
                    'message' => 'رقم الهوية غير موجود'
                ], 404);
            }

            $relatedAccounts = Customer::where('identity_number', $customer->identity_number)
                ->where('id', '!=', $customer->id)
                ->where('company_id', $customer->company_id) //  نفس الشركة
                ->where('relationship_manager_id', $customer->relationship_manager_id) //  نفس المسؤول
                ->where(function ($query) {
                    $query->whereNull('last_contact_result_id')
                        ->orWhere('last_contact_result_id', '!=', 1);
                })
                ->with([
                    'company:id,name,logo_path',
                    'portfolio:id,name',
                    'customerStatus:id,name,color',
                    'lastContactResult:id,name'
                ])
                ->get()
                ->map(function ($account) {
                    $totalDebit = CustomerTransaction::where('customer_id', $account->id)
                        ->where('type', 'debit')
                        ->sum('amount');

                    $totalCredit = CustomerTransaction::where('customer_id', $account->id)
                        ->where('type', 'credit')
                        ->sum('amount');

                    return [
                        'id' => $account->id,
                        'account_number' => $account->account_number,
                        'company_name' => $account->company->name ?? 'غير محدد',
                        'portfolio_name' => $account->portfolio->name ?? 'غير محدد',
                        'total_debt' => $totalDebit,
                        'total_paid' => $totalCredit,
                        'remaining_debt' => $totalDebit - $totalCredit,
                        'last_contact_result' => $account->lastContactResult->name ?? null,
                        'status' => $account->customerStatus ? [
                            'name' => $account->customerStatus->name,
                            'color' => $account->customerStatus->color
                        ] : null
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $relatedAccounts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب الحسابات المرتبطة: ' . $e->getMessage()
            ], 500);
        }
    }

    // البحث
    public function searchCustomer(SearchCollectionCustomerRequest $request): JsonResponse
    {
        $query = $request->validated('query', '');

        $customers = Customer::where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")->orWhere('identity_number', 'like', "%{$query}%")->orWhere('account_number', 'like', "%{$query}%");
        })
            ->where('relationship_manager_id', Auth::id())
            ->select('id', 'name', 'identity_number', 'account_number')
            ->get();

        return response()->json([
            'success'   => true,
            'data'      => $customers
        ]);
    }
}
