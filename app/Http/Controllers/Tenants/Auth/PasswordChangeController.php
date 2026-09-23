<?php

namespace App\Http\Controllers\Tenants\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class PasswordChangeController extends Controller
{
    /**
     * عرض نموذج تغيير كلمة المرور.
     */
    public function showChangeForm()
    {
        return view('tenants.auth.passwords.change');
    }

    /**
     * معالجة تغيير كلمة المرور.
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password'          => 'required',
            'new_password'              => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return redirect()->tenant_route_name('password.change')
                ->withErrors($validator)
                ->withInput();
        }

        $user = Auth::user();

        // التحقق من صحة كلمة المرور الحالية
        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->tenant_route_name('password.change')
                ->withErrors(['current_password' => 'كلمة المرور الحالية غير صحيحة.'])
                ->withInput();
        }

        // تحديث كلمة المرور الجديدة وتعيين must_change_password إلى false
        $user->password = Hash::make($request->new_password);
        $user->must_change_password = false;
        $user->save();

        return redirect()->tenant_route_name('dashboard')->with('success', 'تم تغيير كلمة المرور بنجاح.');
    }
}
