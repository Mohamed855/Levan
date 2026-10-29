<?php

namespace App\Http\Controllers\Tenants\Domains;

use App\Http\Controllers\Controller;
use App\Services\Shared\Cpanel\CpanelService;

class CpanelController extends Controller
{
    public function __construct(private CpanelService $cpanelService) {}

    public function checkLimits()
    {
        return $this->cpanelService->checkLimits();
    }
}
