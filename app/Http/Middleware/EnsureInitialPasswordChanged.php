<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Controllers\Admin\AdminMangment\Users\AdminUserController;
use App\Http\Controllers\Admin\Auth\AdminAuthController;
use App\Http\Controllers\Shared\Wizard\WizardDraftController;
use App\Http\Controllers\Tenants\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Tenants\UserMangment\User\UserController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureInitialPasswordChanged
{
    public function handle(Request $request, Closure $next, string $guard): Response
    {
        if (! in_array($guard, ['admin', 'tenant'], true) || ! $request->user($guard)?->must_change_password) {
            return $next($request);
        }

        $controller = $guard === 'admin' ? AdminUserController::class : UserController::class;
        $logout = $guard === 'admin' ? AdminAuthController::class.'@logout' : AuthenticatedSessionController::class.'@destroy';
        $action = $request->route()?->getActionName();

        if (in_array($action, [$controller.'@showChangePasswordForm', $controller.'@updatePassword', $logout], true)) {
            return $next($request);
        }

        $passwordUrl = $guard === 'admin' ? route('admin.users.password.change') : tenant_route_name('users.password.change');
        $passwordPath = parse_url($passwordUrl, PHP_URL_PATH);

        if ($action === WizardDraftController::class.'@validateStep'
            && $request->input('target_path') === $passwordPath
            && strtoupper((string) $request->input('target_method')) === 'POST') {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تغيير كلمة المرور المؤقتة قبل المتابعة.',
                'redirect' => $passwordUrl,
            ], 403);
        }

        return redirect()->to($passwordUrl);
    }
}
