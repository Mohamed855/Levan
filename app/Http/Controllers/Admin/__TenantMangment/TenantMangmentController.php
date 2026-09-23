<?php

namespace App\Http\Controllers\Admin\TenantMangment;

use App\DataTables\Admin\TenantManagement\TenantManagementDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tenants\TenantRequest;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Admin\Microsoft\Mail\MicrosoftMailService;
use App\Services\Admin\Tenant\TenantService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

class TenantMangmentController extends Controller
{
    protected const VIEW_PATH  = "admin.tenant_managment";
    protected const ROUTE_NAME = "admin.tenantManage";

    public function __construct(private TenantService $tenantService, public MicrosoftMailService $mailService)
    {
        $this->middleware('auth:admin');
        $this->middleware('can:العملاء')->only(['index']);
        $this->middleware('can:إضافة عميل')->only(['create', 'store']);
        $this->middleware('can:تعديل عميل')->only(['edit', 'update']);
        $this->middleware('can:حذف عميل')->only(['destroy']);
    }

    public function index(TenantManagementDataTable $dataTable)
    {
        try {
            $statistics = $this->tenantService->getTenantStatistics();

            return $dataTable->render(self::VIEW_PATH . '.index', [
                'total'                     => $statistics['total'],
                'with_active_subscription'  => $statistics['with_active_subscription'],
                'with_expired_subscription' => $statistics['with_expired_subscription'],
                'without_subscription'      => $statistics['without_subscription'],
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    public function create()
    {
        return view(self::VIEW_PATH . '.create');
    }

    public function store(TenantRequest $request)
    {
        $this->tenantService->create($request->validated());

        return redirect()->route(self::ROUTE_NAME . '.index')->with('success', 'تم إضافة العميل بنجاح');
    }

    public function show(Tenant $tenant)
    {
        // Load relations
        $tenant->load(['lastSubscription', 'bills']);

        $lastSubscription = $tenant->lastSubscription;
        $bills = $tenant->bills;

        // Get plan from central database
        $plan = null;
        if ($lastSubscription) {
            $plan = tenancy()->central(function () use ($lastSubscription) {
                return Plan::find($lastSubscription->plan_id);
            });
        }
        // return [
        //     'tenant' => $tenant,
        //     'lastSubscription' => $lastSubscription,
        //     'bills' => $bills,
        //     'plan' => $plan,
        // ];
        return view('admin.tenant_managment.show', compact('tenant', 'lastSubscription', 'bills', 'plan'));
    }

    public function edit(Tenant $tenant)
    {
        return view(self::VIEW_PATH . '.edit', compact('tenant'));
    }

    public function update(TenantRequest $request, Tenant $tenant)
    {
        $this->tenantService->update($tenant, $request->validated());

        return redirect()->route(self::ROUTE_NAME . '.index')->with('success', 'تم تحديث العميل بنجاح');
    }

    public function destroy(Tenant $tenant)
    {
        $this->tenantService->delete($tenant);

        return redirect()->route(self::ROUTE_NAME . '.index')->with('success', 'تم حذف العميل بنجاح');
    }

    // TODO islam
    // تحتاج مراجعة
    public function resendEmail($tenant_id)
    {
        tenancy()->initialize($tenant_id);

        $user = User::first(); // أو المستخدم المناسب

        if (!$user) {
            tenancy()->end();
            return redirect()->back()->withErrors('لا يوجد مستخدم لهذا التينانت');
        }

        $token = Password::createToken($user);

        $resetLink = URL::to(
            '/reset-password/' . $token .
                '?email=' . urlencode($user->email) .
                '&tenant_id=' . $tenant_id
        );

        $sent = $this->mailService->sendEmail(
            $user->email,
            "إعادة تعيين كلمة المرور",
            "<p>تمت الموافقة على طلب نقل الاشتراك بنجاح.</p>
         <p>اضغط هنا لتغيير كلمة المرور:
            <a href='{$resetLink} '>تغيير كلمة المرور</a>
         </p>"
        );

        tenancy()->end();

        if (!$sent) {
            return redirect()->back()->withErrors('حدث خطأ أثناء إرسال البريد');
        }

        return redirect()->back()->with(
            'success',
            'تم إرسال رابط إعادة تعيين كلمة المرور للعميل بنجاح'
        );
    }
}
