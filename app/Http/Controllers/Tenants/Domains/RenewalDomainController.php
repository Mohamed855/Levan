<?php

namespace App\Http\Controllers\Tenants\Domains;

use App\Http\Controllers\Controller;
use App\Models\Admin\ControlDomain\ControlDomain;
use App\Services\shared\NameCheap\RenewalDomainService;
use Illuminate\Http\Request;

class RenewalDomainController extends Controller
{
    public function __construct(
        private RenewalDomainService $renewal_domain_service
    ) {}

    public function renewalDomainBlade(Request $request)
    {
        $domain_name = $request->domain_name;
        $domain_id   = $request->domain_id;

        $parts = explode('.', $domain_name);
        $extension = end($parts);

        $price = tenancy()->central(function () use ($extension) {
            return ControlDomain::where('domain_extension', 'like', '%' . $extension . '%')->first()->price;
        });

        $tax = 0.15;
        $taxPrice = $price * $tax;
        $priceWithoutTax = $price - $taxPrice;

        return view('tenants.domain_setting.renewalpayment', compact('domain_name', 'domain_id', 'taxPrice', 'priceWithoutTax', 'price'));
    }


    public function renewalDomain($domain)
    {
        return $this->renewal_domain_service->renewDomain($domain);
    }
}
