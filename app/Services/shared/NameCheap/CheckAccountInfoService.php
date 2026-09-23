<?php

namespace App\Services\shared\NameCheap;

use App\Services\Admin\Microsoft\Mail\MicrosoftMailService;
use Illuminate\Support\Facades\Http;

class CheckAccountInfoService
{

    private $apiUser;
    private $apiKey;
    private $clientIp;
    private $baseUrl;
    private $whmUrl;
    private $whmToken;
    private $serverIp;
    public function __construct(private MicrosoftMailService $mail_service)
    {
        $this->apiUser = "nhjsa";
        $this->apiKey = "f38b308ef2fb45dcac4145a4f216b27b";
        $this->clientIp = "41.237.215.140";
        $this->baseUrl = "https://api.namecheap.com/xml.response";
        $this->whmUrl = 'https://server7.e-tec.sa:2087/json-api';
        $this->whmToken = "C94RS4JLTPKTTCXMD14IZ75JADUSG3TC";
        $this->serverIp = '41.237.215.140';
    }
    public function getBalance()
    {
        $params = [
            'ApiUser'  => $this->apiUser,
            'ApiKey'   => $this->apiKey,
            'UserName' => $this->apiUser,
            'ClientIp' => $this->clientIp,
            'Command'  => 'namecheap.users.getBalances',
        ];

        $response = Http::timeout(10)->get($this->baseUrl, $params);

        if (!$response->ok()) {
            return [
                'ok' => false,
                'status_code' => $response->status(),
                'body' => $response->body(),
            ];
        }

        $xml = simplexml_load_string($response->body());

        if ($xml === false) {
            return [
                'ok' => false,
                'error' => '[translate:فشل في تحويل XML]',
                'raw' => $response->body(),
            ];
        }

        $json = json_decode(json_encode($xml), true);
        $attributes = $json['CommandResponse']['UserGetBalancesResult']['@attributes'];
        $balance = $attributes['AccountBalance'];
        return [
            'ok' => true,
            'balance' => (float) $balance,
            // 'parsed' => $json,
        ];
    }


    public function getDomainPrice(string $tld)
    {
        $baseUrl = 'https://api.namecheap.com/xml.response';

        $tld = str_contains($tld, '.') ? ltrim($tld, '.') : $tld;

        try {
            $priceResponse = Http::get($baseUrl, [
                'ApiUser'        => $this->apiUser,
                'ApiKey'         => $this->apiKey,
                'UserName'       => $this->apiUser,
                'ClientIp'       => $this->clientIp,
                'Command'        => 'namecheap.users.getPricing',
                'ProductType'    => 'DOMAIN',
                'ProductCategory' => 'DOMAINS',
                'Action'         => 'REGISTER',
                'ProductName'    => $tld,
            ]);

            if (!$priceResponse->successful()) {
                throw new \Exception('Failed to get pricing from Namecheap');
            }


            $priceXml  = simplexml_load_string($priceResponse->body());
            $priceJson = json_decode(json_encode($priceXml), true);

            $productData = $priceJson['CommandResponse']['UserGetPricingResult']['ProductType']['ProductCategory'][0]["Product"] ?? null;
            if (!$productData) {
                throw new \Exception("Pricing data not available for TLD: $tld");
            }

            $priceData = $productData['Price'] ?? null;
            if (!$priceData) {
                throw new \Exception("Price not found for TLD: $tld");
            }

            $priceAttr = isset($priceData[0]) ? $priceData[0]['@attributes'] : $priceData['@attributes'];

            $totalPrice = 0;
            if (isset($priceAttr['Price']) && isset($priceAttr['AdditionalCost'])) {
                $totalPrice = (float) $priceAttr['Price'] + (float) $priceAttr['AdditionalCost'];
            }

            return [
                'tld'         => $tld,
                'total_price' => $totalPrice,
                'currency'    => $priceAttr['Currency'] ?? 'USD',
            ];
        } catch (\Exception $e) {
            return [
                'tld'         => $tld,
                'price'       => 0,
                'additional'  => 0,
                'total_price' => 0,
                'currency'    => 'USD',
                'error'       => $e->getMessage(),
            ];
        }
    }


    public function CalculateResults($domain)
    {
        $domain = explode('.', $domain);
        $balance = $this->getBalance();
        $domain_price = $this->getDomainPrice($domain[1]);
        // return $balance['balance'];
        if ($balance['balance'] > $domain_price['total_price']) {
            return "can register";
        }

        $tenantId = tenant('id'); // اسم العميل

        $mail = $this->mail_service->sendEmail(
            'is606ay@gmail.com',
            'تنبيه - محاولة تسجيل دومين',
            "تنبيه:\n\nالعميل {$tenantId} حاول تسجيل الدومين .{$domain[1]}، ولكن العملية لم تتم بسبب عدم كفاية الرصيد.\nالرصيد الحالي للعميل: {$balance['balance']} {$domain_price['currency']}.\nالسعر المطلوب لتسجيل الدومين: {$domain_price['total_price']} {$domain_price['currency']}.\n\nيرجى متابعة الأمر مع العميل."
        );

        if ($mail) {
            return "Email sent successfully. Your balance is sufficient to register the domain .{$domain[1]}.";
        }
    }
}
