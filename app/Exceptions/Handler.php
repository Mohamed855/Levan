<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedByPathException;
use Throwable;

class Handler extends ExceptionHandler
{
    /*
    |--------------------------------------------------------------------------
    | A list of exception types with their corresponding custom log levels.
    |--------------------------------------------------------------------------
    */
    protected $levels = [
        //
    ];

    /*
    |--------------------------------------------------------------------------
    | A list of the exception types that are not reported.
    |--------------------------------------------------------------------------
    */
    protected $dontReport = [
        //
    ];

    /*
    |--------------------------------------------------------------------------
    | A list of the inputs that are never flashed to the session on validation exceptions.
    |--------------------------------------------------------------------------
    */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /*
    |--------------------------------------------------------------------------
    | Register the exception handling callbacks for the application.
    |--------------------------------------------------------------------------
    */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // إذا لم يتم التعرف على المستأجر فلا توجد هوية tenant صالحة لعرض قالبها.
        $this->renderable(function (TenantCouldNotBeIdentifiedByPathException $e) {
            return response()->view('errors.404', [], 404);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Customize the response for validation exceptions.
    |--------------------------------------------------------------------------
    */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ValidationException) {

            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل التحقق من البيانات',
                    'errors'  => $exception->errors(),
                ], 422);
            }

            if (app()->environment('production')) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors($exception->errors())
                    ->with('error', 'هناك بعض الأخطاء في البيانات المدخلة. يرجى تصحيحها والمحاولة مرة أخرى.');
            }
        }

        return parent::render($request, $exception);
    }
}
