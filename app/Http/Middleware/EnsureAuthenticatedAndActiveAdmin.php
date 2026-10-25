<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAuthenticatedAndActiveAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::guard('admin')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'غير مصرح. يجب تسجيل الدخول.'], 401);
            }

            return redirect()->route('admin.login')->with('error', 'يجب تسجيل الدخول للوصول للنظام.');
        }

        $user = Auth::guard('admin')->user();

        // التحقق من حالة المستخدم
        if ($user->status->value !== 'active') {
            Auth::guard('admin')->logout();
            if ($request->expectsJson()) {
                return response()->json(['message' => 'حسابك غير نشط.'], 403);
            }

            return redirect()->route('admin.login')->with('error', 'حسابك غير نشط. يرجى التواصل مع الإدارة.');
        }

        return $next($request);
    }
}
