<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Customer\Follow;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\BusinessCenter\Customer\CustomerFollow\StoreCustomerFollowRequest;
use App\Http\Requests\Tenants\BusinessCenter\Customer\CustomerFollow\UpdateCustomerFollowRequest;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Follow\CustomerFollow;
use App\Models\Tenants\FinancialCollection\Collection\Collection as CollectionModel;
use App\Models\Tenants\FinancialCollection\Collection\CustomerTransaction;
use App\Services\Tenants\BusinessCenter\Customer\Follow\ContactResultService;
use App\Services\Tenants\BusinessCenter\Customer\Follow\CustomerFollowService;
use App\Services\Tenants\FinancialCollection\CollectionProcess\CollectionSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CustomerFollowController extends Controller
{
    protected CustomerFollowService $customerFollowService;

    protected ContactResultService $contactResultService;

    protected CollectionSessionService $collectionSessionService;

    public function __construct(
        CustomerFollowService $customerFollowService,
        ContactResultService $contactResultService,
        CollectionSessionService $collectionSessionService
    ) {
        $this->customerFollowService = $customerFollowService;
        $this->contactResultService = $contactResultService;
        $this->collectionSessionService = $collectionSessionService;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Customer Follows List (for Ajax)
    |--------------------------------------------------------------------------
    */
    public function getFollowsList(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        try {
            $customerFollows = $this->customerFollowService->getCustomerFollowsQuery($customer->id)->get();

            $html = view('tenants.business_center.customers.customer_follows.list', [
                'customerFollows' => $customerFollows,
                'customer' => $customer,
                'hasAnyActions' => $this->hasAnyActions($customerFollows),
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب البيانات',
            ], 500);
        }
    }

    public function index(Customer $customer): JsonResponse
    {
        return $this->getFollowsList($customer);
    }

    public function show(Customer $customer, CustomerFollow $customerFollow): JsonResponse
    {
        $this->authorize('view', $customer);
        abort_unless($customerFollow->customer_id === $customer->getKey(), 404);

        return response()->json([
            'success' => true,
            'data' => $customerFollow->loadMissing([
                'contactMethod',
                'customerStatus',
                'contactResult',
                'createdBy',
            ]),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Check if any follow has actions
    |--------------------------------------------------------------------------
    */
    private function hasAnyActions($customerFollows): bool
    {
        foreach ($customerFollows as $follow) {
            $canEdit = true;
            if ($follow->collection_id && $follow->collection) {
                $canEdit = ! in_array($follow->collection->status, ['approved', 'rejected']);
            }

            $hasEditButton = $canEdit && auth()->user()->can('customer_follows.edit');
            $hasDeleteButton = auth()->user()->can('customer_follows.delete');

            if ($hasEditButton || $hasDeleteButton) {
                return true;
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Contact Result Fields
    |--------------------------------------------------------------------------
    */
    public function getContactResultFields(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        try {
            $contactResultId = $request->input('contact_result_id');

            if (! $contactResultId) {
                return response()->json([
                    'success' => false,
                    'message' => 'معرف نتيجة الاتصال مطلوب',
                ], 400);
            }

            $fields = $this->contactResultService->getResultFields($contactResultId);

            return response()->json([
                'success' => true,
                'data' => $fields,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الحقول: '.$e->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Create Customer Follow
    |--------------------------------------------------------------------------
    */
    public function create(Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        try {
            $hasPendingCollection = CollectionModel::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->where('customer_id', $customer->id)
                ->where('status', 'pending')
                ->exists();

            if ($hasPendingCollection) {
                return response()->json([
                    'success' => false,
                    'message' => 'يوجد سند سابق بانتظار الاعتماد. يرجى اعتماده أولاً.',
                ], 400);
            }

            // ------- التحقق من المديونية المتبقية للعميل  -------\\

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
                    'message' => 'العميل قام بالسداد الكامل. لا يمكن إضافة متابعة جديدة.',
                ], 400);
            }

            // ------- التحقق من المديونية المتبقية للعميل  -------\\

            $formData = $this->customerFollowService->getFormData();

            $html = view('tenants.business_center.customers.customer_follows.create', [
                'customer' => $customer,
                'contact_methods' => $formData['contact_methods'],
                'customer_statuses' => $formData['customer_statuses'],
                'contact_results' => $formData['contact_results'],
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'message' => 'تم تحميل النموذج بنجاح',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل النموذج: '.$e->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | new Store Customer Follow
    |--------------------------------------------------------------------------
    */
    public function store(StoreCustomerFollowRequest $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        try {
            $data = $request->validated();
            $data['customer_id'] = $customer->id;

            // معالجة الملفات
            if ($request->hasFile('payment_receipt')) {
                $data['payment_receipt'] = $request->file('payment_receipt');
            }
            if ($request->hasFile('partial_payment_receipt')) {
                $data['partial_payment_receipt'] = $request->file('partial_payment_receipt');
            }
            if ($request->hasFile('recovery_document')) {
                $data['recovery_document'] = $request->file('recovery_document');
            }

            // ✅ إضافة معرفات الحسابات المختارة
            if ($request->has('selected_account_ids')) {
                $data['selected_account_ids'] = $request->input('selected_account_ids');
            }

            $customerFollow = $this->customerFollowService->createCustomerFollow($data);

            $this->collectionSessionService->recordActivity('follow', $data['contact_result_id']);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة متابعة العميل بنجاح',
                'data' => $customerFollow,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة متابعة العميل: '.$e->getMessage(),
            ], 500);
        }
    }

    /*
   |--------------------------------------------------------------------------
   | Edit Customer Follow
   |--------------------------------------------------------------------------
   */
    public function edit(Customer $customer, CustomerFollow $customerFollow): JsonResponse
    {
        $this->authorize('update', $customer);
        abort_unless($customerFollow->customer_id === $customer->getKey(), 404);

        try {
            $formData = $this->customerFollowService->getFormData();
            $customerFollow->load([
                'fullPaymentDetails.paymentMethod',
                'partialPaymentDetails.paymentMethod',
                'callbackDetails',
                'documentRequestDetails.documentReason',
                'objectionDetails',
                'recoveryDetails',
                'temporaryContactLossDetails',
            ]);

            $html = view('tenants.business_center.customers.customer_follows.edit', [
                'customer' => $customer,
                'customerFollow' => $customerFollow,
                'contact_methods' => $formData['contact_methods'],
                'customer_statuses' => $formData['customer_statuses'],
                'contact_results' => $formData['contact_results'],
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'message' => 'تم تحميل النموذج بنجاح',
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل النموذج: '.$e->getMessage(),
            ], 500);
        }
    }

    /*
   |--------------------------------------------------------------------------
   | Update Customer Follow
   |--------------------------------------------------------------------------
   */
    public function update(UpdateCustomerFollowRequest $request, Customer $customer, CustomerFollow $customerFollow): JsonResponse
    {
        $this->authorize('update', $customer);
        abort_unless($customerFollow->customer_id === $customer->getKey(), 404);

        try {
            $data = $request->validated();

            $data['customer_id'] = $customer->id;

            if ($request->hasFile('payment_receipt')) {
                $data['payment_receipt'] = $request->file('payment_receipt');
            }

            if ($request->hasFile('partial_payment_receipt')) {
                $data['partial_payment_receipt'] = $request->file('partial_payment_receipt');
            }

            if ($request->hasFile('recovery_document')) {
                $data['recovery_document'] = $request->file('recovery_document');
            }

            $updatedFollow = $this->customerFollowService->updateCustomerFollow($customerFollow, $data);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث متابعة العميل بنجاح',
                'data' => $updatedFollow,
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث متابعة العميل: '.$e->getMessage(),
            ], 500);
        }
    }

    /*
   |--------------------------------------------------------------------------
   | Delete Customer Follow
   |--------------------------------------------------------------------------
   */
    public function destroy(Customer $customer, CustomerFollow $customerFollow): JsonResponse
    {
        $this->authorize('delete', $customer);
        abort_unless($customerFollow->customer_id === $customer->getKey(), 404);

        try {
            $this->customerFollowService->deleteCustomerFollow($customerFollow);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف متابعة العميل بنجاح',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف متابعة العميل: '.$e->getMessage(),
            ], 500);
        }
    }

    /*
   |--------------------------------------------------------------------------
   | Get Customer Follows Data (for DataTables)
   |--------------------------------------------------------------------------
   */
    public function getData(Request $request, Customer $customer)
    {
        $this->authorize('view', $customer);

        if ($request->ajax()) {
            $query = $this->customerFollowService->getCustomerFollowsQuery($customer->id);

            return DataTables::of($query)
                ->addColumn('contact_method', function ($follow) {
                    return $follow->contactMethod->name ?? 'غير محدد';
                })
                ->addColumn('customer_status', function ($follow) {
                    return $follow->customerStatus->name ?? 'غير محدد';
                })
                ->addColumn('contact_result', function ($follow) {
                    return $follow->contactResult->name ?? 'غير محدد';
                })
                ->addColumn('created_by', function ($follow) {
                    return $follow->createdBy->name ?? 'غير محدد';
                })
                ->addColumn('created_at', function ($follow) {
                    return $follow->created_at->format('Y-m-d H:i');
                })
                ->addColumn('actions', function ($follow) {
                    $actions = '';

                    if (auth()->user()->can('customer_follows.edit')) {
                        $actions .= '<button type="button" class="btn btn-icon btn-outline-primary me-1" onclick="editCustomerFollow('.$follow->id.')" title="تعديل">
                           <i class="ti ti-edit"></i>
                       </button>';
                    }

                    if (auth()->user()->can('customer_follows.delete')) {
                        $actions .= '<button type="button" class="btn btn-icon btn-outline-danger" onclick="deleteCustomerFollow('.$follow->id.')" title="حذف">
                           <i class="ti ti-trash"></i>
                       </button>';
                    }

                    return $actions;
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        return response()->json(['error' => 'طلب غير صالح'], 400);
    }

    /*
   |--------------------------------------------------------------------------
   | Section Title
   |--------------------------------------------------------------------------
   | Description of this section.
   */
    public function getNextCustomer(Customer $customer)
    {
        $this->authorize('view', $customer);

        try {
            $currentUserId = auth()->id();
            $nextCustomer = Customer::where('relationship_manager_id', $currentUserId)
                ->where('id', '>', $customer->id)
                ->orderBy('id', 'asc')
                ->first();
            if (! $nextCustomer) {
                $nextCustomer = Customer::where('relationship_manager_id', $currentUserId)
                    ->where('id', '!=', $customer->id)
                    ->orderBy('id', 'asc')
                    ->first();
            }
            if (! $nextCustomer) {
                return redirect()
                    ->back()
                    ->with('info', 'لا يوجد عملاء آخرون مُسندون إليك');
            }

            return redirect()->to(tenant_route_name('customers.show', $nextCustomer->id));
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'حدث خطأ أثناء البحث عن العميل التالي');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function getCustomerDebt(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        try {
            $remainingDebt = $customer->remaining_debt ?? 0;

            return response()->json([
                'success' => true,
                'remaining_debt' => $remainingDebt,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب بيانات المديونية',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Related Accounts
    |--------------------------------------------------------------------------
    */
    public function getRelatedAccounts(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        try {
            if (! $customer->identity_number) {
                return response()->json([
                    'success' => false,
                    'message' => 'رقم الهوية غير موجود',
                ], 404);
            }
            $relatedAccounts = Customer::where('identity_number', $customer->identity_number)
                ->where('id', '!=', $customer->id)
                ->where('company_id', $customer->company_id)
                ->where('relationship_manager_id', $customer->relationship_manager_id)
                ->where(function ($query) {
                    $query->whereNull('last_contact_result_id')
                        ->orWhere('last_contact_result_id', '!=', 1);
                })
                ->with(['transactions'])
                ->with([
                    'company:id,name,logo_path',
                    'portfolio:id,name',
                    'customerStatus:id,name',
                ])
                ->get()
                ->map(function ($account) {
                    return [
                        'id' => $account->id,
                        'account_number' => $account->account_number,
                        'company_name' => $account->company->name ?? 'غير محدد',
                        'portfolio_name' => $account->portfolio->name ?? 'غير محدد',
                        'remaining_debt' => $account->remaining_debt ?? 0,
                        'status' => $account->customerStatus ? [
                            'name' => $account->customerStatus->name,
                        ] : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $relatedAccounts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب الحسابات المرتبطة: '.$e->getMessage(),
            ], 500);
        }
    }
}
