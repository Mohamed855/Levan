<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;
use Symfony\Component\HttpFoundation\Response;

final class IsolatePanelSession
{
    public function __construct(private SessionManager $sessions, private AuthManager $auth) {}

    public function handle(Request $request, Closure $next): Response
    {
        // The array driver has no persisted browser sessions.
        if (! config('session.driver') || config('session.driver') === 'array') {
            return $next($request);
        }

        $routeName = preg_replace('/^central-domain-\d+\./', '', (string) $request->route()?->getName());
        $panel = str_starts_with($routeName, 'admin.') ? 'admin' : 'tenant';
        $originalCookie = config('session.cookie');
        $originalConnection = config('session.connection');
        config(['session.cookie' => config('session.panel_cookies.'.$panel, $originalCookie.'_'.$panel)]);
        if (config('session.driver') === 'database' && ! $originalConnection) {
            config(['session.connection' => config('tenancy.database.central_connection', 'mysql')]);
        }

        // A new store and handler prevent attributes and database-handler state
        // from surviving into another panel when the application is reused.
        $this->sessions->forgetDrivers();
        app()->forgetInstance('session.store');
        app()->forgetInstance('auth.driver');
        $this->auth->forgetGuards();
        $this->auth->shouldUse('admin');

        try {
            return $next($request);
        } finally {
            config(['session.cookie' => $originalCookie, 'session.connection' => $originalConnection]);
        }
    }
}
