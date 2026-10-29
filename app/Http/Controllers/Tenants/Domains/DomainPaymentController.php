<?php

namespace App\Http\Controllers\Tenants\Domains;

use App\Exceptions\Payment\PaymentInitiationException;
use App\Exceptions\Payment\PaymentVerificationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Domains\DomainPaymentCallbackRequest;
use App\Http\Requests\Tenants\Domains\StartDomainRegistrationPaymentRequest;
use App\Http\Requests\Tenants\Domains\StartDomainRenewalPaymentRequest;
use App\Http\Requests\Tenants\Domains\StartDomainTransferPaymentRequest;
use App\Services\Shared\Payment\Moyasar\DomainRegistrationPaymentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DomainPaymentController extends Controller
{
    public function __construct(private DomainRegistrationPaymentService $paymentService)
    {
        $this->middleware('can:domain.reserve')->only([
            'startRegistration',
            'registrationCallback',
            'startRenewal',
            'renewalCallback',
        ]);
        $this->middleware('can:domain.transfer')->only(['startTransfer', 'transferCallback']);
    }

    public function startRegistration(StartDomainRegistrationPaymentRequest $request): View|RedirectResponse
    {
        try {
            $attempt = $this->paymentService->start(
                (string) tenant('id'),
                $request->validated('domain'),
            );

            return view('tenant.payment.moyasar-checkout', array_merge(
                $this->paymentService->checkoutData(
                    $attempt,
                    tenant_route_name('domains.payment.callbacknewdomain', ['attempt' => $attempt->public_id]),
                ),
                [
                    'amount' => $attempt->amount,
                    'backUrl' => tenant_route_name('domains.setting'),
                ],
            ));
        } catch (PaymentInitiationException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        } catch (ModelNotFoundException) {
            return back()->withInput()->with('error', 'تعذر تحديد اشتراك صالح لإتمام عملية شراء النطاق.');
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withInput()->with('error', 'تعذر بدء عملية الدفع. حاول مرة أخرى.');
        }
    }

    public function registrationCallback(DomainPaymentCallbackRequest $request): RedirectResponse
    {
        if (config('services.moyasar.mode') !== 'test') {
            return redirect(tenant_route_name('domains.setting'))->with('error', 'دفع النطاقات غير مفعّل خارج بيئة الاختبار حالياً.');
        }

        $data = $request->validated();

        try {
            $attempt = $this->paymentService->verifyAndMarkPaid(
                $data['attempt'],
                (string) tenant('id'),
                $data['id'],
                'register',
            );

            return redirect(tenant_route_name('domains.setting'))->with(
                'success',
                "تم التحقق من الدفع التجريبي للنطاق {$attempt->domain}. لم يتم تنفيذ أي شراء خارجي في بيئة الاختبار.",
            );
        } catch (PaymentVerificationException|ModelNotFoundException $exception) {
            return redirect(tenant_route_name('domains.setting'))->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);

            return redirect(tenant_route_name('domains.setting'))->with('error', 'تعذر التحقق من نتيجة الدفع. حاول مرة أخرى.');
        }
    }

    public function startRenewal(StartDomainRenewalPaymentRequest $request): View|RedirectResponse
    {
        try {
            $attempt = $this->paymentService->startRenewal(
                (string) tenant('id'),
                (int) $request->validated('domain_id'),
                (int) $request->validated('years'),
            );

            return view('tenant.payment.moyasar-checkout', array_merge(
                $this->paymentService->checkoutData(
                    $attempt,
                    tenant_route_name('domains.moyasar.renewal.callback', ['attempt' => $attempt->public_id]),
                ),
                [
                    'amount' => $attempt->amount,
                    'backUrl' => tenant_route_name('domains.setting'),
                ],
            ));
        } catch (PaymentInitiationException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        } catch (ModelNotFoundException) {
            return back()->withInput()->with('error', 'تعذر العثور على نطاق صالح لتجديده.');
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withInput()->with('error', 'تعذر بدء عملية الدفع. حاول مرة أخرى.');
        }
    }

    public function renewalCallback(DomainPaymentCallbackRequest $request): RedirectResponse
    {
        return $this->completeCallback(
            $request,
            'renew',
            'تم التحقق من الدفع التجريبي لتجديد النطاق. لم يتم تنفيذ أي تجديد خارجي في بيئة الاختبار.',
        );
    }

    public function startTransfer(StartDomainTransferPaymentRequest $request): View|RedirectResponse
    {
        try {
            $attempt = $this->paymentService->startTransfer(
                (string) tenant('id'),
                $request->validated('domain'),
                $request->validated('epp_code'),
            );

            return view('tenant.payment.moyasar-checkout', array_merge(
                $this->paymentService->checkoutData(
                    $attempt,
                    tenant_route_name('domains.payment.callback.transfer', ['attempt' => $attempt->public_id]),
                ),
                [
                    'amount' => $attempt->amount,
                    'backUrl' => tenant_route_name('domains.transfer-domain'),
                ],
            ));
        } catch (PaymentInitiationException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        } catch (ModelNotFoundException) {
            return back()->withInput()->with('error', 'تعذر تحديد اشتراك صالح لإتمام نقل النطاق.');
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withInput()->with('error', 'تعذر بدء عملية الدفع. حاول مرة أخرى.');
        }
    }

    public function transferCallback(DomainPaymentCallbackRequest $request): RedirectResponse
    {
        return $this->completeCallback(
            $request,
            'transfer',
            'تم التحقق من الدفع التجريبي لنقل النطاق. لم يتم إرسال طلب نقل خارجي في بيئة الاختبار.',
        );
    }

    private function completeCallback(DomainPaymentCallbackRequest $request, string $expectedOperation, string $testSuccessMessage): RedirectResponse
    {
        if (config('services.moyasar.mode') !== 'test') {
            return redirect(tenant_route_name('domains.setting'))->with('error', 'دفع النطاقات غير مفعّل خارج بيئة الاختبار حالياً.');
        }

        $data = $request->validated();

        try {
            $this->paymentService->verifyAndMarkPaid(
                $data['attempt'],
                (string) tenant('id'),
                $data['id'],
                $expectedOperation,
            );

            return redirect(tenant_route_name('domains.setting'))->with('success', $testSuccessMessage);
        } catch (PaymentVerificationException|ModelNotFoundException $exception) {
            return redirect(tenant_route_name('domains.setting'))->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);

            return redirect(tenant_route_name('domains.setting'))->with('error', 'تعذر التحقق من نتيجة الدفع. حاول مرة أخرى.');
        }
    }
}
