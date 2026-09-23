<?php

namespace App\Http\Controllers\Tenants\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Admin\Microsoft\Mail\MicrosoftMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function __construct(private MicrosoftMailService $mailService) {}
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('tenants.auth.forgot-password');
    }
 public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                    : back()->withInput($request->only('email'))
                            ->withErrors(['email' => __($status)]);
    }
    
    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'email' => 'required|email',
    //     ]);

    //      $tenants = Tenant::all();

    //     foreach ($tenants as $tenant) {
             
    //         tenancy()->initialize($tenant->id);
              
    //         $customer = User::first()->email;

    //         if ($customer) {

    //             // إنشاء token
    //              $this->mailService->sendEmail($customer , 'حبيب اخوك' , "<p>حبيب اخوك منور</p>");

    //             // إرسال الإيميل


    //             tenancy()->end();

    //             return back()->with('status', 'تم إرسال رابط استعادة كلمة المرور');
    //         }

    //         tenancy()->end();
    //     }

    //     return back()->withErrors([
    //         'email' => 'البريد الإلكتروني غير موجود'
    //     ]);
    // }
}
