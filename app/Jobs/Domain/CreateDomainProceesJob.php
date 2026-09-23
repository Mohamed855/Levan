<?php

namespace App\Jobs\Domain;

use App\Models\Admin\Cpanel\CpanelAccount;
use App\Models\Admin\Tenants\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\DB;

class CreateDomainProceesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $tries = 1;

    protected $domainName;
    protected $plan;
    protected $apiUser;
    protected $apiKey;
    protected $clientIp;
    protected $baseUrl;
    protected $whmUrl;
    protected $whmToken;
    protected $serverIp;
    protected $tenantId;
    protected $cpanelUsername;
    protected $cpanelPassword;

    public function __construct($domainName, $plan = 'default_levan', $tenantId = null)
    {
        $this->domainName = $domainName;
        $this->plan = $plan;
        $this->tenantId = $tenantId;

        $this->apiUser = config('namecheap.api_user');
        $this->apiKey = config('namecheap.api_key');
        $this->clientIp = '135.181.215.105';
        $this->baseUrl = config('namecheap.api_url');
        $this->whmUrl = 'https://server.levan.com:2087/json-api';
        $this->whmToken = '8EPSQ67GW3XWGGRBYN51EP105R6C31P5';
        $this->serverIp = '135.181.215.105';
    }

    public function handle()
    {
        Log::info("🚀 Starting domain registration", [
            'domain' => $this->domainName,
            'tenant_id' => $this->tenantId
        ]);

        $ns1 = 'ns1.levan.com';
        $ns2 = 'ns2.levan.com';

        try {
             [$sld, $tld] = explode('.', $this->domainName);

             $this->cpanelUsername = $sld;

            Log::info("📝 Using cPanel username", ['username' => $this->cpanelUsername]);

            $this->createDomainOnNamecheap($sld, $tld);
            sleep(5);

             $this->createGlueRecords($sld, $tld, $ns1, $ns2);
            sleep(5);

             $this->setNameservers($sld, $tld, $ns1, $ns2);
            sleep(7);
             $tenantId = $this->tenantId;
            
            
        } catch (Exception $e) {
            Log::error("❌ Registration failed", [
                'domain' => $this->domainName,
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($this->attempts() < $this->tries) {
                Log::info("🔄 Retrying job in 2 minutes", [
                    'attempt' => $this->attempts(),
                    'domain' => $this->domainName
                ]);
                $this->release(120);
                return;
            }

            throw $e;
        }
    }

   
    protected function sanitizeUsername($sld)
    {
        // إزالة الرموز والشرطات
        $clean = preg_replace('/[^a-z0-9]/i', '', $sld);

        // تحويل لـ lowercase
        $clean = strtolower($clean);

        // أقصى 16 حرف (أول 8 لازم unique)
        $clean = substr($clean, 0, 16);

        // لو الاسم فاضي، استخدم random string
        if (empty($clean)) {
            $clean = 'usr' . strtolower(Str::random(6));
        }

        return $clean;
    }

    /**
     * إنشاء الدومين على Namecheap
     */
    protected function createDomainOnNamecheap($sld, $tld)
    {
        Log::info("📝 Creating domain on Namecheap", ['domain' => $this->domainName]);

        $createParams = [
            'ApiUser' => $this->apiUser,
            'ApiKey' => $this->apiKey,
            'UserName' => $this->apiUser,
            'ClientIp' => $this->clientIp,
            'Command' => 'namecheap.domains.create',
            'DomainName' => $this->domainName,
            'Years' => 1,
            'RegistrantFirstName' => 'Islam',
            'RegistrantLastName' => 'Ayman',
            'RegistrantAddress1' => 'Cairo Street',
            'RegistrantCity' => 'Cairo',
            'RegistrantStateProvince' => 'Cairo',
            'RegistrantPostalCode' => '12345',
            'RegistrantCountry' => 'EG',
            'RegistrantPhone' => '+20.1000000000',
            'RegistrantEmailAddress' => 'islamay124@gmail.com',
            'TechFirstName' => 'Islam',
            'TechLastName' => 'Ayman',
            'TechAddress1' => 'Cairo Street',
            'TechCity' => 'Cairo',
            'TechStateProvince' => 'Cairo',
            'TechPostalCode' => '12345',
            'TechCountry' => 'EG',
            'TechPhone' => '+20.1000000000',
            'TechEmailAddress' => 'islamay124@gmail.com',
            'AdminFirstName' => 'Islam',
            'AdminLastName' => 'Ayman',
            'AdminAddress1' => 'Cairo Street',
            'AdminCity' => 'Cairo',
            'AdminStateProvince' => 'Cairo',
            'AdminPostalCode' => '12345',
            'AdminCountry' => 'EG',
            'AdminPhone' => '+20.1000000000',
            'AdminEmailAddress' => 'islamay124@gmail.com',
            'AuxBillingFirstName' => 'Islam',
            'AuxBillingLastName' => 'Ayman',
            'AuxBillingAddress1' => 'Cairo Street',
            'AuxBillingCity' => 'Cairo',
            'AuxBillingStateProvince' => 'Cairo',
            'AuxBillingPostalCode' => '12345',
            'AuxBillingCountry' => 'EG',
            'AuxBillingPhone' => '+20.1000000000',
            'AuxBillingEmailAddress' => 'islamay124@gmail.com',
        ];

        $createResponse = Http::timeout(60)->get($this->baseUrl, $createParams);

        if (!$createResponse->successful()) {
            throw new Exception("Failed to connect to Namecheap API: " . $createResponse->status());
        }

        $createXml = simplexml_load_string($createResponse->body());
        $createJson = json_decode(json_encode($createXml), true);

        if (($createJson['@attributes']['Status'] ?? '') !== 'OK') {
            $errorMsg = $createJson['Errors']['Error'] ?? 'Unknown error';
            throw new Exception("Failed to create domain on Namecheap: " . $errorMsg);
        }

        Log::info("✅ Domain created on Namecheap successfully");
    }

    /**
     * ✅ إنشاء Glue Records - معدّل
     */
    protected function createGlueRecords($sld, $tld, $ns1, $ns2)
    {
        Log::info("📝 Creating Glue Records", ['domain' => $this->domainName]);

        // ✅ إنشاء Glue Records بناءً على الدومين نفسه
        $glueNs1 = 'ns1.' . $this->domainName;  // مثال: ns1.klniosg.com
        $glueNs2 = 'ns2.' . $this->domainName;  // مثال: ns2.klniosg.com

        // إنشاء Glue Record الأول
        $glue1Response = Http::timeout(30)->get($this->baseUrl, [
            'ApiUser' => $this->apiUser,
            'ApiKey' => $this->apiKey,
            'UserName' => $this->apiUser,
            'ClientIp' => $this->clientIp,
            'Command' => 'namecheap.domains.ns.create',
            'SLD' => $sld,
            'TLD' => $tld,
            'Nameserver' => $glueNs1,  // ✅ استخدام subdomain من الدومين نفسه
            'IP' => $this->serverIp,
        ]);

        $this->validateResponse($glue1Response, "Glue Record 1");

        sleep(2);

        // إنشاء Glue Record الثاني
        $glue2Response = Http::timeout(30)->get($this->baseUrl, [
            'ApiUser' => $this->apiUser,
            'ApiKey' => $this->apiKey,
            'UserName' => $this->apiUser,
            'ClientIp' => $this->clientIp,
            'Command' => 'namecheap.domains.ns.create',
            'SLD' => $sld,
            'TLD' => $tld,
            'Nameserver' => $glueNs2,  // ✅ استخدام subdomain من الدومين نفسه
            'IP' => $this->serverIp,
        ]);

        $this->validateResponse($glue2Response, "Glue Record 2");

        Log::info("✅ Glue records created successfully", [
            'ns1' => $glueNs1,
            'ns2' => $glueNs2,
            'ip' => $this->serverIp
        ]);
    }

    /**
     * ✅ تعيين Nameservers - معدّل
     */
    protected function setNameservers($sld, $tld, $ns1, $ns2)
    {
        Log::info("📝 Setting nameservers", ['domain' => $this->domainName]);

        // ✅ استخدام نفس الـ nameservers اللي عملناها في Glue Records
        $glueNs1 = 'ns1.' . $this->domainName;
        $glueNs2 = 'ns2.' . $this->domainName;

        $nsResponse = Http::timeout(30)->get($this->baseUrl, [
            'ApiUser' => $this->apiUser,
            'ApiKey' => $this->apiKey,
            'UserName' => $this->apiUser,
            'ClientIp' => $this->clientIp,
            'Command' => 'namecheap.domains.dns.setCustom',
            'SLD' => $sld,
            'TLD' => $tld,
            'Nameservers' => $glueNs1 . ',' . $glueNs2,  // ✅ استخدام الـ NS الصحيحة
        ]);

        $this->validateResponse($nsResponse, "Nameservers");

        Log::info("✅ Nameservers configured successfully", [
            'ns1' => $glueNs1,
            'ns2' => $glueNs2
        ]);
    }

   
    protected function validateResponse($response, $operationName)
    {
        if (!$response->successful()) {
            throw new Exception("$operationName failed: HTTP " . $response->status());
        }

        $xml = simplexml_load_string($response->body());
        $json = json_decode(json_encode($xml), true);

        if (($json['@attributes']['Status'] ?? '') !== 'OK') {
            $errorMsg = $json['Errors']['Error'] ?? 'Unknown error';
            throw new Exception("$operationName failed: " . $errorMsg);
        }

        Log::info("✅ $operationName completed successfully");
    }

    
    public function failed(\Throwable $exception)
    {
        Log::error("❌ Job failed completely after all retries", [
            'domain' => $this->domainName,
            'tenant_id' => $this->tenantId,
            'cpanel_username' => $this->cpanelUsername ?? null,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}