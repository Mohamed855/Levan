<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\Domains;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Domains\DomainLookupRequest;
use App\Services\shared\APILayer\apiLayerService;
use App\Services\Tenants\Domains\DomainDnsLookupService;
use App\Services\Tenants\Shared\CentralDomainGateway;
use Illuminate\Http\JsonResponse;

final class DomainLookupController extends Controller
{
    public function __construct(
        private readonly apiLayerService $namecheap,
        private readonly CentralDomainGateway $domains,
        private readonly DomainDnsLookupService $dns,
    ) {
        $this->middleware('can:domain.view');
    }

    public function checkAvailability(DomainLookupRequest $request, string $domain): JsonResponse
    {
        return $this->namecheap->checkDomainNamecheap($request->validated('domain'));
    }

    public function checkTransferAvailability(DomainLookupRequest $request, string $domain): JsonResponse
    {
        $domain = $request->validated('domain');

        if ($this->domains->findTransferred($domain)) {
            return response()->json([
                'status' => 'exists',
                'message' => 'هذا النطاق منقول بالفعل.',
            ]);
        }

        return $this->namecheap->checkDomainNamecheap($domain);
    }

    public function inspectDns(DomainLookupRequest $request, string $domain): JsonResponse
    {
        return response()->json($this->dns->inspect($request->validated('domain')));
    }

    public function checkSubDomain(string $subdomain): JsonResponse
    {
        return response()->json([
            'result' => checkdnsrr($subdomain, 'A') || checkdnsrr($subdomain, 'CNAME') ? 'registered' : 'available',
        ]);
    }
}
