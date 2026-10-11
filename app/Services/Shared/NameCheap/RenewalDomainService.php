<?php

namespace App\Services\shared\NameCheap;

use Illuminate\Support\Facades\Http;

class RenewalDomainService
{
    protected $apiUser;
    protected $apiKey;
    protected $username;
    protected $clientIp;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiUser  = config('namecheap.api_user');
        $this->apiKey   = config('namecheap.api_key');
        $this->username = config('namecheap.api_user');
        $this->clientIp = config('namecheap.client_ip');
        $this->baseUrl  = config('namecheap.api_url');
    }

    public function renewDomain(string $domain, int $years = 1)
{
    $response = Http::get($this->baseUrl, [
        'ApiUser'    => $this->apiUser,
        'ApiKey'     => $this->apiKey,
        'UserName'   => $this->username,
        'ClientIp'   => $this->clientIp,
        'Command'    => 'namecheap.domains.renew',
        'DomainName' => $domain,
        'Years'      => $years,
    ]);

    // parse XML
    $xml = simplexml_load_string($response->body());

    // فشل
    if ((string) $xml['Status'] === 'ERROR') {
        $errorMessage = (string) $xml->Errors->Error;

        return redirect()
            ->to(tenant('id') . '/domains/setting')
            ->with('error', 'فشل في تجديد النطاق: ' . $errorMessage);
    }

    // نجاح
    return redirect()
            ->to(tenant('id') . '/domains/setting')
        ->with('success', 'تم تجديد النطاق بنجاح.');
}



    /**
     * Parse XML Response
     */
    protected function parseResponse(string $xml)
    {
        $data = simplexml_load_string($xml);

        if ((string) $data['Status'] === 'ERROR') {
            return [
                'success' => false,
                'errors' => $data->Errors->Error,
            ];
        }

        return [
            'success' => true,
            'data' => $data->CommandResponse->DomainRenewResult,
        ];
    }
}
