<?php

namespace App\Http\Controllers\Tenants\Domains;

use App\Http\Controllers\Controller;
use App\Services\shared\NameCheap\GetDomainInfoService;

class GetDomainInfoController extends Controller
{
    public function __construct(
        private GetDomainInfoService $get_domain_info_service
    ) {}

    public function getDomainInfo($domain)
    {
        return $this->get_domain_info_service->getDomainInfo($domain);
    }
}
