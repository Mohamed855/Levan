<?php

namespace App\Services\shared\NameCheap;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class GetDomainInfoService
{
     private $apiUser;
    private $apiKey;
    private $clientIp;
    private $baseUrl;
    private $whmUrl;
    private $whmToken;
    private $serverIp;
    public function __construct()
    {
        $this->apiUser = config('services.namecheap.user');
        $this->apiKey = config('services.namecheap.api_key');
        $this->clientIp = config('services.namecheap.client_ip');
        $this->baseUrl = "https://api.namecheap.com/xml.response";
        $this->whmUrl = 'https://server7.e-tec.sa:2087/json-api';
        $this->whmToken = config('services.namecheap.whm_token');
        $this->serverIp = '41.237.215.140';
    }
    public function getDomainInfo($domain)
    {
        $response = Http::get($this->baseUrl, [
            'ApiUser'    => $this->apiUser,
            'ApiKey'     => $this->apiKey,
            'UserName'   => $this->apiUser,
            'ClientIp'   => $this->clientIp,
            'Command'    => 'namecheap.domains.getInfo',
            'DomainName' => $domain,
        ]);

        if (!$response->successful()) {
            return [
                'success' => false,
                'message' => 'Failed to connect to Namecheap API',
            ];
        }

        $xml = simplexml_load_string($response->body());

        if (!$xml || (string) $xml['Status'] !== 'OK') {

            $errorMessage = 'Unknown error';

            if (isset($xml->Errors->Error)) {
                $errorMessage = (string) $xml->Errors->Error;
            }

            return [
                'success' => false,
                'message' =>  $errorMessage,
            ];
        }



        $expiredDate = (string)
        $xml->CommandResponse
            ->DomainGetInfoResult
            ->DomainDetails
            ->ExpiredDate;

        $expire = Carbon::parse($expiredDate);

        return [
            'success'        => true,
            'domain'         => $domain,
            'expires_at'     => $expire->toDateTimeString(),
            'remaining_days' => now()->startOfDay()->diffInDays($expire->startOfDay()),
            'is_expired'     => $expire->isPast(),
        ];
    }

    
}
