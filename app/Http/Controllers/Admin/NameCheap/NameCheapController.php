<?php

namespace App\Http\Controllers\Admin\NameCheap;



use App\Http\Controllers\Controller;
use App\Services\shared\NameCheap\NameCheapService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NameCheapController extends Controller
{
    private $apiUser;
    private $apiKey;
    private $clientIp;
    private $baseUrl;
    private $whmUrl;
    private $whmToken;
    private $serverIp;
    private $nameCheapService;
    public function __construct(NameCheapService $nameCheapService)
    {
        $this->nameCheapService = $nameCheapService;
        $this->apiUser = env('NAMECHEAP_API_USER');
        $this->apiKey = env('NAMECHEAP_API_KEY');
        $this->clientIp = env('NAMECHEAP_CLIENT_IP');
        $this->baseUrl = env('NAMECHEAP_URL');
        $this->whmUrl = 'https://server.levan.com:2087/json-api';
        $this->whmToken = env('WHM_TOKEN');
        $this->serverIp = '135.181.215.105';
    }


    public function getBalances()
    {
       return $this->nameCheapService->getBalances();
    }

    public function modifyAccount(Request $request)
    {
       return $this->nameCheapService->modifyAccount($request);
    }

}
