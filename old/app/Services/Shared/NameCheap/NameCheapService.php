<?php

namespace App\Services\shared\NameCheap;

use App\Models\Admin\Cpanel\CpanelAccount;
use App\Models\Admin\Tenants\Domain;
use App\Models\Admin\Tenants\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NameCheapService
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
        $this->apiUser = env('NAMECHEAP_API_USER');
        $this->apiKey = env('NAMECHEAP_API_KEY');
        $this->clientIp = env('NAMECHEAP_CLIENT_IP');
        $this->baseUrl = env('NAMECHEAP_URL');
        $this->whmUrl = 'https://server7.e-tec.sa:2087/json-api';
        $this->whmToken = env('WHM_TOKEN');
        $this->serverIp = '135.181.215.105';
    }

    

    public function registerDomainAndSetupHosting2($domainName, $plan = 'levan_default')
    {
        $ns1 = 'ns1.levan.com';
        $ns2 = 'ns2.levan.com';

        try {
            [$sld, $tld] = explode('.', $domainName, 2);

            $createParams = [
                'ApiUser'   => $this->apiUser,
                'ApiKey'    => $this->apiKey,
                'UserName'  => $this->apiUser,
                'ClientIp'  => $this->clientIp,
                'Command'   => 'namecheap.domains.create',
                'DomainName' => $domainName,
                'Years'     => 1,
                'RegistrantFirstName' => 'Islam',
                'RegistrantLastName'  => 'Ayman',
                'RegistrantAddress1'  => '[translate:شارع القاهرة]',
                'RegistrantCity'      => '[translate:القاهرة]',
                'RegistrantStateProvince' => '[translate:القاهرة]',
                'RegistrantPostalCode' => '12345',
                'RegistrantCountry'   => 'EG',
                'RegistrantPhone'     => '+20.1000000000',
                'RegistrantEmailAddress' => 'islamay124@gmail.com',
                'TechFirstName'       => 'Islam',
                'TechLastName'        => 'Ayman',
                'TechAddress1'        => '[translate:شارع القاهرة]',
                'TechCity'            => '[translate:القاهرة]',
                'TechStateProvince'   => '[translate:القاهرة]',
                'TechPostalCode'      => '12345',
                'TechCountry'         => 'EG',
                'TechPhone'           => '+20.1000000000',
                'TechEmailAddress'    => 'islamay124@gmail.com',
                'AdminFirstName'      => 'Islam',
                'AdminLastName'       => 'Ayman',
                'AdminAddress1'       => '[translate:شارع القاهرة]',
                'AdminCity'           => '[translate:القاهرة]',
                'AdminStateProvince'  => '[translate:القاهرة]',
                'AdminPostalCode'     => '12345',
                'AdminCountry'        => 'EG',
                'AdminPhone'          => '+20.1000000000',
                'AdminEmailAddress'   => 'islamay124@gmail.com',
                'AuxBillingFirstName' => 'Islam',
                'AuxBillingLastName'  => 'Ayman',
                'AuxBillingAddress1'  => '[translate:شارع القاهرة]',
                'AuxBillingCity'      => '[translate:القاهرة]',
                'AuxBillingStateProvince' => '[translate:القاهرة]',
                'AuxBillingPostalCode' => '12345',
                'AuxBillingCountry'   => 'EG',
                'AuxBillingPhone'     => '+20.1000000000',
                'AuxBillingEmailAddress' => 'islamay124@gmail.com',
            ];

            $createResponse = Http::timeout(60)->get($this->baseUrl, $createParams);
            $createXml = simplexml_load_string($createResponse->body());
            $createJson = json_decode(json_encode($createXml), true);

            if (($createJson['@attributes']['Status'] ?? '') !== 'OK') {
                return [
                    'ok' => false,
                    'step' => 'domain_creation',
                    'error' => '❌ [translate:فشل إنشاء الدومين على Namecheap]',
                    'response' => $createJson,
                ];
            }

            sleep(15);

            $glueRecords = [
                ['ns' => $ns1, 'ip' => $this->serverIp],
                ['ns' => $ns2, 'ip' => $this->serverIp],
            ];
            $glueResults = [];

            foreach ($glueRecords as $glue) {
                $hostParams = [
                    'ApiUser'   => $this->apiUser,
                    'ApiKey'    => $this->apiKey,
                    'UserName'  => $this->apiUser,
                    'ClientIp'  => $this->clientIp,
                    'Command'   => 'namecheap.domains.ns.create',
                    'SLD'       => $sld,
                    'TLD'       => $tld,
                    'Nameserver' => $glue['ns'] . '.' . $domainName,
                    'IP'        => $glue['ip'],
                ];

                $hostResponse = Http::timeout(30)->get($this->baseUrl, $hostParams);
                $hostXml = simplexml_load_string($hostResponse->body());
                $hostJson = json_decode(json_encode($hostXml), true);

                $glueResults[] = [
                    'nameserver' => $glue['ns'] . '.' . $domainName,
                    'ip' => $glue['ip'],
                    'response' => $hostJson,
                ];

                sleep(2);
            }

            $nsParams = [
                'ApiUser'   => $this->apiUser,
                'ApiKey'    => $this->apiKey,
                'UserName'  => $this->apiUser,
                'ClientIp'  => $this->clientIp,
                'Command'   => 'namecheap.domains.dns.setCustom',
                'SLD'       => $sld,
                'TLD'       => $tld,
                'Nameservers' => $ns1 . '.' . $domainName . ',' . $ns2 . '.' . $domainName,
            ];

            $nsResponse = Http::timeout(30)->get($this->baseUrl, $nsParams);
            $nsXml = simplexml_load_string($nsResponse->body());
            $nsJson = json_decode(json_encode($nsXml), true);

            sleep(30);

            $prefix = 'tn';
            $unique = substr(md5($domainName . microtime()), 0, 6);
            $whmUser = strtolower($prefix . $unique);
            $whmPass = Str::random(16) . '@1Aa';
            $whmEmail = 'admin@levan.com';
            $whmIp = $this->serverIp;
            $whmResponse = Http::withHeaders([
                'Authorization' => 'whm levan:' . $this->whmToken,
            ])->timeout(60)->get($this->whmUrl . '/createacct', [
                'api.version' => 1,
                'username' => $whmUser,
                'domain' => $domainName,
                'plan' => $plan,
                'password' => $whmPass,
                'contactemail' => $whmEmail,
                'ip' => $whmIp,
                'skipdomaincheck' => 1,
            ]);

            $whmJson = $whmResponse->json();

            if (!isset($whmJson['metadata']['result']) || $whmJson['metadata']['result'] != 1) {
                return [
                    'ok' => false,
                    'step' => 'whm_account_creation',
                    'error' => '❌ [translate:فشل إنشاء الأكاونت على WHM]',
                    'response' => $whmJson,
                ];
            }

            $dnsRecords = [];

            $dnsResponse1 = Http::withHeaders([
                'Authorization' => 'whm levan:' . $this->whmToken,
            ])->timeout(30)->get($this->whmUrl . '/addzonerecord', [
                'domain' => $domainName,
                'name' => $domainName . '.',
                'type' => 'A',
                'address' => $this->serverIp,
                'ttl' => 14400,
            ]);
            $dnsRecords[] = ['type' => 'A', 'name' => $domainName, 'response' => $dnsResponse1->json()];

            sleep(2);

            $dnsResponse2 = Http::withHeaders([
                'Authorization' => 'whm levan:' . $this->whmToken,
            ])->timeout(30)->get($this->whmUrl . '/addzonerecord', [
                'domain' => $domainName,
                'name' => 'www.' . $domainName . '.',
                'type' => 'A',
                'address' => $this->serverIp,
                'ttl' => 14400,
            ]);
            $dnsRecords[] = ['type' => 'A', 'name' => 'www', 'response' => $dnsResponse2->json()];

            return [
                'ok' => true,
                'message' => '[translate:✅ تم إنشاء الدومين والاستضافة بنجاح!]',
                'domain' => [
                    'name' => $domainName,
                    'nameservers' => [
                        $ns1 . '.' . $domainName,
                        $ns2 . '.' . $domainName,
                    ],
                ],
                'hosting' => [
                    'username' => $whmUser,
                    'password' => $whmPass,
                    'email' => $whmEmail,
                    'plan' => $plan,
                    'ip' => $whmIp,
                    'cpanel_url' => 'https://' . $domainName . ':2083',
                    'whm_url' => 'https://server7.e-tec.sa:2087',
                ],
                'dns_records' => $dnsRecords,
                'api_responses' => [
                    '1_domain_creation' => $createJson,
                    '2_glue_records' => $glueResults,
                    '3_nameservers_set' => $nsJson,
                    '4_whm_account' => $whmJson,
                ],
                'next_steps' => [
                    '1' => '[translate:انتظر 1-24 ساعة حتى ينتشر DNS عالمياً]',
                    '2' => '[translate:ادخل على cPanel:] https://' . $domainName . ':2083',
                    '3' => '[translate:ارفع ملفات الموقع على] /public_html',
                ],
            ];
        } catch (\Throwable $th) {
            Log::error('NameCheapService Exception', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
            ]);

            return [
                'ok' => false,
                'error' => $th->getMessage(),
            ];
        }
    }

    public function setupdnsForDomain($domainName, $dns1, $dns2, $plan = 'levan_default')
    {
        try {
            if (empty($domainName) || empty($dns1) || empty($dns2)) {
                return [
                    'ok' => false,
                    'error' => '❌ [translate:جميع الحقول مطلوبة]'
                ];
            }

            $prefix = 'tn';
            $unique = substr(md5($domainName . microtime()), 0, 6);
            $whmUser = strtolower($prefix . $unique);
            $whmPass = Str::random(16) . '@1Aa';
            $whmEmail = 'admin@levan.com';

            $whmResponse = Http::withHeaders([
                'Authorization' => 'whm levan:' . $this->whmToken,
            ])->timeout(60)->get($this->whmUrl . '/createacct', [
                'api.version' => 1,
                'username' => $whmUser,
                'domain' => $domainName,
                'plan' => $plan,
                'password' => $whmPass,
                'contactemail' => $whmEmail,
                'ip' => $this->serverIp,
                'skipdomaincheck' => 1,
            ]);

            $whmJson = $whmResponse->json();

            if (!isset($whmJson['metadata']['result']) || $whmJson['metadata']['result'] != 1) {
                return [
                    'ok' => false,
                    'error' => $whmJson['metadata']['reason'] ?? '[translate:خطأ غير معروف]'
                ];
            }

            sleep(3);

            foreach ([$dns1, $dns2] as $ns) {
                $nsResponse = Http::withHeaders([
                    'Authorization' => 'whm levan:' . $this->whmToken,
                ])->timeout(30)->get($this->whmUrl . '/addzonerecord', [
                    'domain' => $domainName,
                    'name' => $domainName . '.',
                    'type' => 'NS',
                    'nsdname' => $ns,
                    'ttl' => 86400,
                ]);
            }

            $aResponse1 = Http::withHeaders([
                'Authorization' => 'whm levan:' . $this->whmToken,
            ])->timeout(30)->get($this->whmUrl . '/addzonerecord', [
                'domain' => $domainName,
                'name' => $domainName . '.',
                'type' => 'A',
                'address' => $this->serverIp,
                'ttl' => 14400,
            ]);

            sleep(1);

            $aResponse2 = Http::withHeaders([
                'Authorization' => 'whm levan:' . $this->whmToken,
            ])->timeout(30)->get($this->whmUrl . '/addzonerecord', [
                'domain' => $domainName,
                'name' => 'www.' . $domainName . '.',
                'type' => 'A',
                'address' => $this->serverIp,
                'ttl' => 14400,
            ]);

            return [
                'ok' => true,
                'message' => '[translate:✅ تم إعداد الدومين بنجاح على السيرفر]',
                'whmUser' => $whmUser,
                'whmPass' => $whmPass,
                'email' => $whmEmail,
            ];
        } catch (\Throwable $th) {
            Log::error('NameCheapService SetupDNS Exception', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
            ]);

            return [
                'ok' => false,
                'error' => $th->getMessage(),
            ];
        }
    }

    public function checkDomain()
    {
        $params = [
            'ApiUser'   => $this->apiUser,
            'ApiKey'    => $this->apiKey,
            'UserName'  => $this->apiUser,
            'ClientIp'  => $this->clientIp,
            'Command'   => 'namecheap.domains.check',
            'DomainList' => "islamaym.com",
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

        return [
            'ok' => true,
            'parsed' => $json,
        ];
    }


    public function modifyAccount($domain, $tenantId)
    {
         $user = tenancy()->central(function () use ($domain, $tenantId) {
            DB::table('cpanel_accounts')->where('tenant_id', $tenantId)->update([
                'domain' => $domain
            ]);
             Domain::where('tenant_id' , $tenantId)->update(['is_active' => "0"]);
             Domain::where('domain',$domain)->update(['is_active' => "1"]);
            return DB::table('cpanel_accounts')->where('tenant_id', $tenantId)->first();
        });
        $whmUrl   = env('WHM_HOST');
        $whmUser  = env('WHM_USER');
        $whmToken = env('WHM_TOKEN');

        // $newDomain = $domain;

        try {
            $endpoint = 'https://server.levan.com:2087/json-api/modifyacct';
            $params   = [
                'api.version' => 1,
                'user'        => $user->cpanel_username,
                'domain'      => $domain,
            ];

            $response = Http::withHeaders([
                'Authorization' => "whm levan:8EPSQ67GW3XWGGRBYN51EP105R6C31P5",
            ])
                ->withoutVerifying()
                ->get($endpoint, $params);

            if ($response->failed()) {
                Log::error("WHM API failed response", ['body' => $response->body()]);
                return redirect()->back()
                    ->with('error', 'فشل الاتصال بخادم WHM');
            }

            $json = $response->json();
            return  redirect()->to(tenant('id').'/domains/setting')->with('success', 'تمت الخدمة بنجاح');
            // مثلاً: نتحقق إن metadata.result == 1
            if (isset($json['metadata']['result']) && $json['metadata']['result'] == 1) {
                $reason = $json['metadata']['reason'] ?? 'تم تغيير الدومين بنجاح';
                return redirect()->back()
                    ->with('success', $reason);
            } else {
                $reason = $json['metadata']['reason'] ?? 'حدث خطأ أثناء تعديل الحساب';
                return redirect()->back()
                    ->with('error', $reason);
            }
        } catch (\Exception $e) {
            Log::error('Exception calling WHM API', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'خطأ داخلي: ' . $e->getMessage());
        }
    }

    public function createDomainRowWithActivation($domainName, $type, $tenantId, $transfer_code = null, $transfer_status = null)
    {
        return tenancy()->central(function () use ($domainName, $type, $tenantId, $transfer_code, $transfer_status) {

            // 1️⃣ إلغاء تفعيل كل الدومينات القديمة للـ tenant
            Domain::where('tenant_id', $tenantId)->update(['is_active' => "0"]);
            Log::info('in create domain with row');
            // 2️⃣ إنشاء الدومين الجديد وتفعيله
            $newDomain = Domain::create([
                'domain'          => $domainName,
                'type'            => $type,
                'is_active'       => "1", // ✅ الدومين الجديد هيكون 1
                'transfer_code'   => $transfer_code,
                'tenant_id'       => $tenantId,
                'trasnfer_status' => $transfer_status
            ]);

            Log::info($newDomain);
        });
    }

    public function transferProcess($request){
         $request->validate([
            'domain' => 'required|string',
            'eppCode' => 'required|string|min:6'
        ], [
            'eppCode.required' => 'الرجاء إدخال كود EPP',
            'eppCode.min' => 'كود EPP يجب أن يكون 6 أحرف على الأقل'
        ]);

        try {
            $domain = $request->domain;
            $eppCode = $request->eppCode;

            Log::info("Processing transfer for: " . $domain);

            // تحقق إذا كان الدومين قيد النقل حالياً
            $existingTransfer = tenancy()->central(function () use ($domain) {
                return Domain::where('domain', $domain)->where('type','transfered')->first();
            });
            if ($existingTransfer) {
                return redirect()->back()
                    ->with('info', 'تم بالفعل تقديم طلب نقل لهذا الدومين وهو قيد المعالجة. الرجاء الانتظار حتى الانتهاء من النقل.')
                    ->withInput();
            }

            $apiUser = config('namecheap.api_user');
            $apiKey = config('namecheap.api_key');
            $clientIp = env('NAMECHEAP_CLIENT_IP');
            $url = config('namecheap.api_url');

            // التحقق من حالة القفل
            $lockParams = [
                'ApiUser' => $apiUser,
                'ApiKey' => $apiKey,
                'UserName' => $apiUser,
                'ClientIp' => $clientIp,
                'Command' => 'namecheap.domains.getRegistrarLock',
                'DomainName' => $domain
            ];

            $lockResponse = Http::timeout(30)->get($url, $lockParams);

            if ($lockResponse->ok()) {
                $lockXml = simplexml_load_string($lockResponse->body());

                if ($lockXml && (string)$lockXml['Status'] === 'OK') {
                    $lockStatus = (string)$lockXml->CommandResponse->DomainGetRegistrarLockResult;

                    if (strtolower($lockStatus) === 'true') {
                        return redirect()->back()
                            ->with('error', 'الدومين مقفول (Locked). يجب فتح القفل من المزود الحالي أولاً')
                            ->withInput();
                    }
                }
            }

            // إرسال طلب النقل
            $transferParams = [
                'ApiUser' => $apiUser,
                'ApiKey' => $apiKey,
                'UserName' => $apiUser,
                'ClientIp' => $clientIp,
                'Command' => 'namecheap.domains.transfer.create',
                'DomainName' => $domain,
                'EPPCode' => $eppCode,
                'Years' => 1
            ];

             $response = Http::timeout(30)->get($url, $transferParams);

            if ($response->failed()) {
                return redirect()->back()
                    ->with('error', 'فشل الاتصال بخادم Namecheap')
                    ->withInput();
            }

            $xml = simplexml_load_string($response->body());

            if (!$xml) {
                return redirect()->back()
                    ->with('error', 'خطأ في قراءة البيانات من Namecheap')
                    ->withInput();
            }

            $status = (string)$xml['Status'];

            if ($status !== 'OK') {
                $errorMessage = isset($xml->Errors->Error) ? (string)$xml->Errors->Error : 'فشل في إرسال طلب النقل';

                // أخطاء شائعة
                if (stripos($errorMessage, 'EPP') !== false || stripos($errorMessage, 'Authorization') !== false) {
                    $errorMessage = 'كود EPP غير صحيح. تأكد من الكود وحاول مرة أخرى';
                } elseif (stripos($errorMessage, 'locked') !== false) {
                    $errorMessage = 'الدومين مقفول. يجب فتح القفل من المزود الحالي أولاً';
                } elseif (stripos($errorMessage, '60 days') !== false) {
                    $errorMessage = 'لم يمر 60 يوم على تسجيل/نقل الدومين';
                }

                Log::error("Transfer error: " . $errorMessage);
                return redirect()->back()
                    ->with('error', $errorMessage)
                    ->withInput();
            }

            $transferResult = $xml->CommandResponse->DomainTransferCreateResult;
            $transferId = (string)$transferResult['Transfer'];
            $orderId = (string)$transferResult['OrderID'];
            $tenantId = tenant('id');
             

           
            $selectedDomain = $request->domain;


            Log::info("🔄 Switching active domain", [
                'tenant_id' => $tenantId,
                'selected_domain' => $selectedDomain
            ]);
            Domain::create([
                'domain' => $request->domain,
                'tenant_id' => $tenantId,
                'is_active' => "0",
                'type' => 'transfered',
                'transfer_code' => $orderId,
            ]);

            $mainDomain = Domain::where('tenant_id', $tenantId)->first();   


            if (!$mainDomain) {
                return redirect()->back()->with('error', 'لم يتم العثور على دومينات مسجلة');
            }
            

            return redirect('/' . tenant('id') . '/domains/transfer-dom-blade?step=3&domain=' . urlencode($domain) . '&transfer_id=' . $transferId . '&order_id=' . $orderId)
                ->with('success', 'تم إرسال طلب النقل بنجاح!');
        } catch (\Exception $e) {
            Log::error('Transfer Exception: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء معالجة طلب النقل: ' . $e->getMessage())
                ->withInput();
        }
    }
}
