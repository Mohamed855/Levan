<?php

namespace App\Http\Controllers\Tenants\Domains;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Domains\ShowDomainRenewalCheckoutRequest;
use App\Services\shared\NameCheap\RenewalDomainService;
use App\Services\shared\Payment\Moyasar\DomainRegistrationPaymentService;

class RenewalDomainController extends Controller
{
    public function __construct(
        private RenewalDomainService $renewal_domain_service,
        private DomainRegistrationPaymentService $paymentService,
    ) {}

    public function renewalDomainBlade(ShowDomainRenewalCheckoutRequest $request)
    {
        $preview = $this->paymentService->renewalPreview(
            (string) tenant('id'),
            (int) $request->validated('domain_id'),
        );

        return view('tenant.domains.renewalpayment', [
            'domain' => $preview['domain'],
            'unitPrice' => $preview['unit_price'],
        ]);
    }

    public function renewalDomain($domain)
    {
        return $this->renewal_domain_service->renewDomain($domain);
    }
}
