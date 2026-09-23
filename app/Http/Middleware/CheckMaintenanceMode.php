<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\SettingsHelper;
use Illuminate\Support\Facades\Route;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $maintenanceMode = SettingsHelper::get('maintenance_mode');

        if ($maintenanceMode) {

            // التعامل مع طلبات AJAX
            if ($request->ajax()) {
                return response()->json(['message' => 'الموقع تحت الصيانة'], 503);
            }
            // إذا كان المستخدم مصادق عليه
            if (auth()->check()) {
                // وإذا لم يكن لديه دور 'Technecal_Support' أو 'Admin'
                if (!auth()->user()->hasRole('Technecal_Support') && !auth()->user()->hasRole('Admin')) {
                    // إذا لم يكن الطلب AJAX، قم بعرض صفحة الصيانة
                    return response()->view('maintenance');
                }
            } else {
                // إذا كان المستخدم غير مصادق عليه وليس طلب AJAX، قم بعرض صفحة الصيانة
                return $next($request);
            }
        }

        return $next($request);
    }
}
