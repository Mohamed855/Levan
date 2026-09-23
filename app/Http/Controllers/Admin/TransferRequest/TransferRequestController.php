<?php

namespace App\Http\Controllers\Admin\TransferRequest;

use App\DataTables\Admin\TransferRequest\TransferRequestDataTable;
use App\Http\Controllers\Controller;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Admin\Microsoft\Mail\MicrosoftMailService;
use App\Services\Admin\TransferRequest\TransferRequestService;
use App\Services\shared\Billing\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Mpdf\Tag\Tr;

class TransferRequestController extends Controller
{
    public function __construct(
        public TransferRequestService $transferRequestService,
        public MicrosoftMailService $mailService,
        public BillingService $billingService
    ) {}

    public function index(TransferRequestDataTable $datatable)
    {
        $all_transfer_requests = Subscription::where('status', 'suspended')->whereNotNull('transfer_bank_attachment')->get()->count();
        $approved_transfer_requests = Subscription::where('status', 'active')->whereNotNull('transfer_bank_attachment')->get()->count();
        $rejected_transfer_requests = Subscription::where('status', 'cancelled')->whereNotNull('transfer_bank_attachment')->get()->count();

        return $datatable->render('admin.transferrequests.index', compact('all_transfer_requests', 'approved_transfer_requests', 'rejected_transfer_requests'));
    }

    public function details(Subscription $subscription)
    {
        $subscriptionDetails = $subscription->with(['tenant', 'plan', 'periods'])->where('id', $subscription->id)->first();

        return view('admin.transferrequests.details', compact('subscriptionDetails'));
    }

    public function rejectTransferRequest($subscription)
    {
        Log::info("Rejecting transfer request for subscription ID: " . $subscription);
        $subscription = Subscription::findOrFail($subscription);
        $subscription->update([
            'status' => 'cancelled',
        ]);

        $subscription->periods()->update([
            'status' => 'cancelled',
        ]);

        tenancy()->initialize($subscription->tenant->id);

        Log::info("Tenant: " .  User::first()->email);
        $mail = $this->mailService->sendEmail(User::first()->email, "تم رفض  طلب الاشتراك الخاص بك", "<p>تم رفض  طلب الاشتراك الخاص بك</p>");
        if ($mail) {
            return redirect()->route('admin.transfer-requests.index')->with('success', 'تم رفض  طلب الاشتراك و ارسال ايميل للعميل.');
        }
        tenancy()->end();

        // return redirect()->route('admin.transfer-requests.index')->with('success', 'تم رفض طلب نقل الاشتراك بنجاح.');
    }

    public function approveTransferRequest($subscription)
    {

        Log::info("Approving transfer request for subscription ID: " . $subscription);
        $subscription =  Subscription::with('tenant')->where('id', $subscription)->first();
        $subscription->update([
            'status' => 'active',
        ]);

        $subscription->periods()->update([
            'status' => 'active',
        ]);
        $data = [
            'originalTotal' => $subscription->price,
            'price' => $subscription->price,
            'payment_method' => 'bank_transfer',
            'payment_type' => 'paid'
        ];
        $this->billingService->createTransferBankBillByAdmin($subscription, $subscription->currentPeriod, $data);

        Log::info($this->billingService->createTransferBankBillByAdmin($subscription, $subscription->currentPeriod, $data));
        tenancy()->initialize($subscription->tenant->id);
        Log::info("Tenant: " .  User::first()->email);
        $user = User::first(); // أو المستخدم المناسب

        $token = Password::createToken($user);

        $resetLink = URL::to(
            '/reset-password/' . $token .
                '?email=' . urlencode(User::first()->email) .
                '&tenant_id=' . $subscription->tenant->id
        );

        // إرسال الإيميل مع اللينك
        $this->mailService->sendEmail(
            User::first()->email,
            "إعادة تعيين كلمة المرور",
            "<p>تمت الموافقة على طلب نقل الاشتراك بنجاح.</p>
         <p>اضغط هنا لتغيير كلمة المرور: <a href='{$resetLink}'>تغيير كلمة المرور</a></p>"
        );
        tenancy()->end();

        // return redirect()->route('admin.transfer-requests.index')->with('success', 'تم الموافقة على طلب نقل الاشتراك بنجاح.');
    }
}
