<?php

namespace App\Http\Controllers\Tenants\Domains;

use App\DataTables\Admin\Setting\SettingDomainDatatable;
use App\DataTables\Tenants\Bills\DomainDataTable;
use App\DataTables\Tenants\Domain\DomainDataTable as DomainDomainDataTable;
use App\Http\Controllers\Controller;
use App\Models\Admin\Cpanel\CpanelAccount;
use App\Models\Admin\Tenants\Domain;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Settings\TransferDomain;
use App\Services\shared\Cpanel\CpanelService;
use App\Services\shared\Domain\DomainService;
use App\Services\shared\NameCheap\NameCheapService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

// TODO islam
// الكنترول يجب ان يتم تنظيمه وحذف الدوال التي لافائدة منها
class DomainController extends Controller
{
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
    protected $domain_service;
    protected $cpanelService;
    public function __construct(private NameCheapService $namecheapService, DomainService $domain_service, CpanelService $cpanelService)
    {
        // لتحقق هل الباقة تحتوي على ميزة الدومينات ام لا
        $this->middleware('check.domain.management');

        $this->cpanelService = $cpanelService;
        $this->domain_service = $domain_service;
        // $this->domainName = $domainName;
        $this->plan = 'levan_default';
        $this->tenantId = $tenantId ?? tenant('id');

        $this->apiUser = env('NAMECHEAP_API_USER');
        $this->apiKey = env('NAMECHEAP_API_KEY');
        $this->clientIp = env('NAMECHEAP_CLIENT_IP');
        $this->baseUrl = 'https://api.sandbox.namecheap.com/xml.response';
        $this->whmUrl = env('WHM_HOST');
        $this->whmToken = env('WHM_TOKEN');
        $this->serverIp = env('SERVER_IP', '135.181.215.105');
    }

    public function domainSetting(DomainDomainDataTable $dataTable)
    {
        try {
            $statistics = $this->domain_service->getDomainStatistics();

            return $dataTable->render('tenants.domain_setting.setting', [
                'totalDomains'       => $statistics['total'],
                'activeDomains'      => $statistics['active'],
                'inactiveDomains'    => $statistics['inactive'],
            ]);
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    public function linkDomain()
    {
        return view('tenants.domain_setting.linkdomain');
    }
    public function linkSubDomain()
    {
        return view('tenants.domain_setting.linksubdomain');
    }
    public function transferDom(Request $request)
    {
        $step = $request->get('step', 1);
        $domain = $request->get('domain');
        $message = $request->get('message');

        return view('tenants.domain_setting.transferdom', compact('step', 'domain', 'message'));
    }
    public function faq()
    {
        return view('tenants.domain_setting.faq');
    }
    public function explain()
    {
        return view('tenants.domain_setting.explain');
    }
    public function newdomain()
    {
        return view('tenants.domain_setting.index');
    }
    public function subdomainblade()
    {
        return view('tenants.domain_setting.explain-create-subdomain');
    }



    protected function getCpanelUserForTenant()
    {
        try {
            $tenant = Tenant::find(tenant('id'));
            return  $tenant->update([
                'cpanel_username' => "aewg"
            ]);

            // التحقق من وجود username محفوظ
            if ($tenant) {
                // التحقق من أن الحساب موجود فعلياً على WHM
                $response = Http::withHeaders([
                    'Authorization' => 'whm levan:' . $this->whmToken,
                ])->timeout(30)->get($this->whmUrl . '/accountsummary', [
                    'api.version' => 1,
                    'user' => "tn386a65"
                ]);

                $result = $response->json();

                if (isset($result['data']['acct'])) {
                    Log::info("✅ Found existing cPanel account", [
                        'cpanel_user' => $tenant->cpanel_username,
                        'tenant_id' => $this->tenantId
                    ]);
                    return true;
                } else {
                    Log::warning("⚠️ cPanel username found in DB but account doesn't exist on WHM", [
                        'cpanel_user' => $tenant->cpanel_username,
                        'tenant_id' => $this->tenantId
                    ]);
                    return 'no';
                }
            }
        } catch (\Exception $e) {
            Log::warning("⚠️ Error checking cPanel user", [
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    protected function createCpanelAccount($domain)
    {


        $cpanelUsername = explode('.', $domain)[0];

        // Tenant::find($this->tenantId)->update([
        //     'cpanel_username' => $cpanelUsername,
        // ]);
        // إضافة رقم عشوائي للتأكد من عدم التكرار

        // توليد باسورد قوي
        $password = Str::random(16) . rand(10, 99) . '!@';

        // إنشاء الحساب
        $response = Http::withHeaders([
            'Authorization' => 'whm levan:' . $this->whmToken
        ])->timeout(60)->get($this->whmUrl . '/createacct', [
            'username' => $cpanelUsername,
            'domain' => $this->domainName,
            'password' => $password,
            'plan' => 'default_levan',
            'contactemail' => 'islamay124@gmail.com',
            'featurelist' => 'default',
            'quota' => 0, // Unlimited
            'hasshell' => 0,
            'ip' => 'n', // Use shared IP
        ]);

        if (!$response->successful()) {
            throw new Exception("Failed to create cPanel account: HTTP " . $response->status());
        }

        return    $result = $response->json();
    }





    public function checkDomainExists(Request $request)
    {
        $request->validate([
            'domain' => 'required|string|regex:/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'
        ], [
            'domain.required' => 'الرجاء إدخال اسم الدومين',
            'domain.regex' => 'صيغة الدومين غير صحيحة'
        ]);

        try {
            $domain = $request->domain;

            Log::info("Checking if domain exists: " . $domain);

            $apiUser = env('NAMECHEAP_API_USER');
            $apiKey = env('NAMECHEAP_API_KEY');
            $clientIp = env('NAMECHEAP_CLIENT_IP');

            if (!$apiUser || !$apiKey || !$clientIp) {
                return redirect()->back()
                    ->with('error', 'إعدادات Namecheap API غير مكتملة')
                    ->withInput();
            }

            $url = env('NAMECHEAP_URL');

            $params = [
                'ApiUser' => $apiUser,
                'ApiKey' => $apiKey,
                'UserName' => $apiUser,
                'ClientIp' => $clientIp,
                'Command' => 'namecheap.domains.check',
                'DomainList' => $domain
            ];

            $response = Http::timeout(30)->get($url, $params);

            if ($response->failed()) {
                Log::error("Namecheap API failed", ['status' => $response->status()]);
                return redirect()->back()
                    ->with('error', 'فشل الاتصال بخادم Namecheap')
                    ->withInput();
            }

            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($response->body());

            if (!$xml) {
                return redirect()->back()
                    ->with('error', 'خطأ في قراءة البيانات من Namecheap')
                    ->withInput();
            }

            $status = (string)$xml['Status'];

            if ($status !== 'OK') {
                $errorMessage = isset($xml->Errors->Error) ? (string)$xml->Errors->Error : 'خطأ غير معروف';
                Log::error("Namecheap API error: " . $errorMessage);
                return redirect()->back()
                    ->with('error', $errorMessage)
                    ->withInput();
            }

            $domainResult = $xml->CommandResponse->DomainCheckResult;
            $isAvailable = (string)$domainResult['Available'] === 'true';

            Log::info("Domain check result", ['domain' => $domain, 'available' => $isAvailable]);

            // Available = true يعني الدومين غير مسجل
            if ($isAvailable) {
                return redirect()->back()
                    ->with('error', 'الدومين غير مسجل. لا يمكن نقل دومين غير مسجل')
                    ->withInput();
            }

            // Available = false يعني الدومين مسجل، ننتقل للخطوة 2
            return redirect('/' . tenant('id') . '/transfer-dom-blade?step=2&domain=' . urlencode($domain))
                ->with('success', 'الدومين موجود! أدخل كود EPP للمتابعة');
        } catch (\Exception $e) {
            Log::error('Domain Check Exception: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء التحقق من الدومين: ' . $e->getMessage())
                ->withInput();
        }
    }


    public function processTransfer(Request $request)
    {
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
                return Domain::where('domain', $domain)->where('type', 'transfered')->first();
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
                'is_active' => "1",
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




    public function switchActiveDomain(Request $request)
    {
        return $this->domain_service->switchActiveDomain($request);
    }




    // TODO islam
    // يجب ان ياتي من env or config  ليسهل تعديله و مراقبته
    const SERVER_IP = '135.181.215.105'; // IP الخاص بالسيرفر


    public function checkSubDomain($subdomain)
    {
        try {
            // تنظيف الدومين
            $cleanDomain = $this->cleanDomain($subdomain);

            // التحقق من صحة الدومين
            if (!$this->isValidDomain($cleanDomain)) {
                return response()->json([
                    'result' => 'invalid',
                    'message' => 'اسم الدومين غير صالح'
                ], 400);
            }

            // التحقق من A Record
            $aRecord = $this->getARecord($cleanDomain);

            if (!$aRecord) {
                return response()->json([
                    'result' => 'not_found',
                    'message' => 'لم يتم العثور على A Record لهذا الدومين',
                    'domain' => $cleanDomain
                ]);
            }

            // التحقق إذا كان A Record يشير لسيرفرنا
            $pointsToOurServer = ($aRecord === self::SERVER_IP);

            return response()->json([
                'result' => $pointsToOurServer ? 'ready' : 'not_configured',
                'message' => $pointsToOurServer
                    ? 'الدومين مهيأ بشكل صحيح ويشير إلى سيرفرنا'
                    : 'الدومين لا يشير إلى سيرفرنا بعد',
                'domain' => $cleanDomain,
                'current_ip' => $aRecord,
                'required_ip' => self::SERVER_IP,
                'is_configured' => $pointsToOurServer
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'result' => 'error',
                'message' => 'حدث خطأ أثناء التحقق: ' . $e->getMessage()
            ], 500);
        }
    }

    private function isValidDomain($domain)
    {
        return (bool) preg_match('/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/i', $domain);
    }


    public function transferDomainProcess(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'domain' => 'required|string', // تغيير من subdomain إلى domain
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'type' => 'error'
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $domain = $this->cleanDomain($request->domain);

        // تحديد نوع الدومين (دومين أو صب دومين)
        $domainType = $this->getDomainType($domain);

        try {
            $result = $this->createCpanelAccount($domain, $domainType);

            // معالجة النتائج المختلفة
            if ($result === true) {
                $message = '✅ تم إنشاء حساب الـ cPanel بنجاح وربط الدومين!';
                $type = 'success';
                $success = true;
            } elseif ($result === 'exists') {
                $message = 'ℹ️ الحساب موجود بالفعل على السيرفر. الدومين مرتبط بشكل صحيح.';
                $type = 'info';
                $success = true;
            } elseif ($result === 'failed') {
                $message = '⚠️ لم يتم إنشاء الحساب، يرجى المحاولة لاحقًا أو التواصل مع الدعم الفني.';
                $type = 'warning';
                $success = false;
            } else {
                $message = '⚠️ حدثت استجابة غير متوقعة من السيرفر. يرجى التواصل مع الدعم الفني.';
                $type = 'warning';
                $success = false;
            }

            // حفظ البيانات في قاعدة البيانات إذا كان الربط ناجح
            if ($success && $result === true) {
                // حفظ Domain في الداتابيز
                // Domain::create([
                //     'tenant_id' => tenant('id'),
                //     'domain' => $domain,
                //     'type' => $domainType,
                //     'status' => 'active',
                // ]);
            }

            // Return JSON for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => $success,
                    'message' => $message,
                    'type' => $type,
                    'result' => $result,
                    'domain_type' => $domainType
                ]);
            }

            return back()->with($type, $message);
        } catch (\Exception $e) {
            Log::error('Transfer Domain Error: ' . $e->getMessage(), [
                'domain' => $domain,
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = '❌ حدث خطأ أثناء الربط: ' . $e->getMessage();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'type' => 'error'
                ], 500);
            }

            return back()->with('error', $errorMessage);
        }
    }

    private function getDomainType($domain)
    {
        $parts = explode('.', $domain);
        return count($parts) > 2 ? 'subdomain' : 'domain';
    }


    private function cleanDomain($domain)
    {

        $domain = trim($domain);
        $domain = preg_replace('#^https?://#i', '', $domain);
        $domain = preg_replace('#^www\.#i', '', $domain);
        $domain = strtolower($domain);

        return $domain;
    }


    public function checkDomain($domain)
    {
        $domain = $this->cleanDomain($domain);
        $requiredIp = env('SERVER_IP', '135.181.215.105');

        try {
            // محاولة الحصول على A Record
            $records = @dns_get_record($domain, DNS_A);

            if (empty($records)) {
                return response()->json([
                    'result' => 'not_found',
                    'message' => 'لم يتم العثور على A Record لهذا الدومين',
                    'domain' => $domain,
                    'required_ip' => $requiredIp
                ]);
            }

            // التحقق من IP
            $currentIp = $records[0]['ip'] ?? null;

            if ($currentIp === $requiredIp) {
                return response()->json([
                    'result' => 'ready',
                    'message' => 'الدومين جاهز للربط',
                    'domain' => $domain,
                    'current_ip' => $currentIp,
                    'required_ip' => $requiredIp
                ]);
            } else {
                return response()->json([
                    'result' => 'not_configured',
                    'message' => 'A Record غير صحيح',
                    'domain' => $domain,
                    'current_ip' => $currentIp,
                    'required_ip' => $requiredIp
                ]);
            }
        } catch (\Exception $e) {
            Log::error("DNS Check Error: " . $e->getMessage(), ['domain' => $domain]);

            return response()->json([
                'result' => 'error',
                'message' => 'حدث خطأ أثناء التحقق من الدومين',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function checkTransferStatus($domain)
    {
        $transfer = tenancy()->central(function () use ($domain) {
            return  Domain::where('domain', $domain)->first();
        });

        if (!$transfer) {
            return response()->json([
                'error' => 'لم يتم العثور على هذا الدومين في سجل النقل.'
            ], 404);
        }

        $transferId = $transfer->transfer_code;

        $apiUser  = env('NAMECHEAP_API_USER');
        $apiKey   = env('NAMECHEAP_API_KEY');
        $clientIp = '154.180.114.3';
        $baseUrl  = 'https://api.sandbox.namecheap.com/xml.response';

        $params = [
            'ApiUser'    => $apiUser,
            'ApiKey'     => $apiKey,
            'UserName'   => $apiUser,
            'ClientIp'   => $clientIp,
            'Command'    => 'namecheap.domains.transfer.getStatus',
            'TransferID' => $transferId,
        ];

        try {
            $response = Http::get($baseUrl, $params);

            if ($response->failed()) {
                return response()->json([
                    'error' => 'فشل الاتصال بـ Namecheap API',
                    'details' => $response->body()
                ], 500);
            }

            $xml = simplexml_load_string($response->body());

            if (isset($xml->Errors->Error)) {
                return response()->json([
                    'error' => (string)$xml->Errors->Error,
                    'code' => (string)$xml->Errors->Error['Number'] ?? null
                ], 400);
            }

            $status = (string)($xml->CommandResponse->DomainTransferStatusResult['Status'] ?? 'غير معروف');

            // تحديث الحالة في الجدول المركزي
            // tenancy()->central(function () use ($transfer, $status) {
            //     $transfer->update(['status' => $status]);
            // });

            return response()->json([
                'domain' => $domain,
                'status' => $status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'حدث خطأ غير متوقع أثناء جلب الحالة',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private array $rdapServers = [
        'com'    => 'https://rdap.verisign.com/com/v1/domain/',
        'net'    => 'https://rdap.verisign.com/net/v1/domain/',
        'org'    => 'https://rdap.publicinterestregistry.org/rdap/domain/',
        'io'     => 'https://rdap.nic.io/domain/',
        'co'     => 'https://rdap.nic.co/domain/',
        'info'   => 'https://rdap.afilias.net/rdap/info/domain/',
        'biz'    => 'https://rdap.afilias.net/rdap/biz/domain/',
        'me'     => 'https://rdap.nic.me/domain/',
        'tv'     => 'https://rdap.nic.tv/domain/',
        'us'     => 'https://rdap.nic.us/domain/',
        'uk'     => 'https://rdap.nominet.uk/uk/domain/',
        'de'     => 'https://rdap.denic.de/domain/',
        'fr'     => 'https://rdap.nic.fr/domain/',
        'nl'     => 'https://rdap.sidn.nl/rdap/domain/',
        'au'     => 'https://rdap.auda.org.au/domain/',
        'ca'     => 'https://rdap.cira.ca/domain/',
        'ru'     => 'https://rdap.tcinet.ru/domain/',
        'eu'     => 'https://rdap.eu/domain/',
        'app'    => 'https://rdap.nic.google/domain/',
        'dev'    => 'https://rdap.nic.google/domain/',
        'page'   => 'https://rdap.nic.google/domain/',
        'shop'   => 'https://rdap.donuts.co/domain/',
        'online' => 'https://rdap.centralnic.com/online/domain/',
        'site'   => 'https://rdap.centralnic.com/site/domain/',
        'store'  => 'https://rdap.centralnic.com/store/domain/',
        'tech'   => 'https://rdap.centralnic.com/tech/domain/',
    ];

    // ============================================================
    // Public Methods
    // ============================================================

    public function verifyDNS(Request $request)
    {
        $domain   = trim($request->domain);
        $method   = $request->method;

        $serverIp = env('SERVER_IP', '135.181.215.105');
        $serverNS = [
            env('NS1', 'ns1.levan.com'),
            env('NS2', 'ns2.levan.com'),
        ];

        try {

            if ($method === 'nameserver') {

                $domainNS = $this->checkDomainDNS($domain, 'NS');

                if (empty($domainNS)) {
                    return response()->json([
                        'success'     => false,
                        'message'     => '❌ لا يمكن العثور على Name Servers للدومين. يرجى التحقق من إعدادات DNS.',
                        'current_ns'  => [],
                        'required_ns' => $serverNS,
                    ]);
                }

                $domainNS = array_map(fn($ns) => strtolower(trim($ns, '.')), $domainNS);
                $matched  = array_intersect($domainNS, $serverNS);

                if (empty($matched)) {
                    return response()->json([
                        'success'     => false,
                        'message'     => '❌ Name Servers غير متطابقة. يرجى تحديث Name Servers.',
                        'current_ns'  => array_values($domainNS),
                        'required_ns' => $serverNS,
                    ]);
                }
            } elseif ($method === 'cname') {

                $cname = $this->checkDomainDNS($domain, 'CNAME');

                if (!empty($cname)) {

                    $target       = $cname[0] ?? '';
                    $serverDomain = env('SERVER_DOMAIN', 'levan.com');

                    if (!str_contains($target, $serverDomain)) {
                        return response()->json([
                            'success' => false,
                            'message' => '❌ CNAME غير متطابق. يجب أن يشير إلى: ' . $serverDomain,
                        ]);
                    }
                } else {

                    $a = $this->checkDomainDNS($domain, 'A');

                    if (!empty($a)) {

                        if ($a[0] !== $serverIp) {
                            return response()->json([
                                'success' => false,
                                'message' => '❌ A Record غير متطابق. IP الحالي: ' . $a[0] . ' | IP المطلوب: ' . $serverIp,
                            ]);
                        }
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => '❌ لا يمكن العثور على CNAME أو A Record للدومين.',
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => '✅ الدومين متصل بنجاح! جاري إكمال عملية الربط...',
            ]);
        } catch (\Exception $e) {

            Log::error('DNS Verification Error', [
                'domain' => $domain,
                'error'  => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '❌ حدث خطأ في التحقق من الدومين: ' . $e->getMessage(),
            ]);
        }
    }

    // ============================================================
    // Private Methods
    // ============================================================

    private function checkDomainDNS(string $domain, string $type): array
    {
        $domain = $this->cleanDomain2($domain);

        if ($type === 'NS') {
            return $this->getNSviaRDAP($domain);
        }

        $url = match ($type) {
            'A'     => "https://dns.google/resolve?name={$domain}&type=A",
            'CNAME' => "https://dns.google/resolve?name={$domain}&type=CNAME",
            default => null,
        };

        if (!$url) return [];

        $response = Http::timeout(10)->get($url);

        if (!$response->successful()) return [];

        return collect($response->json('Answer', []))
            ->pluck('data')
            ->toArray();
    }

    private function getNSviaRDAP(string $domain): array
    {
        $tld        = $this->extractTld($domain);
        $rdapServer = $this->rdapServers[$tld] ?? $this->getServerFromIANA($tld);

        if (!$rdapServer) return [];

        $response = Http::timeout(10)
            ->withHeaders(['Accept' => 'application/rdap+json'])
            ->withOptions(['verify' => false])
            ->get($rdapServer . urlencode($domain));

        if (!$response->successful()) return [];

        $nameservers = $response->json('nameservers', []);

        return collect($nameservers)
            ->pluck('ldhName')
            ->filter()
            ->map(fn($ns) => strtolower(rtrim($ns, '.')))
            ->values()
            ->toArray();
    }

    private function getServerFromIANA(string $tld): string
    {
        $response = Http::timeout(10)
            ->withOptions(['verify' => false])
            ->get('https://data.iana.org/rdap/dns.json');

        if (!$response->successful()) return '';

        $services = $response->json('services', []);

        foreach ($services as $service) {
            [$tlds, $servers] = $service;
            if (in_array($tld, $tlds) && !empty($servers[0])) {
                return rtrim($servers[0], '/') . '/domain/';
            }
        }

        return '';
    }

    // TODO islam
    // يوجد دالة اخر cleanDomain يجب مراجعة الدالتين و توحيدها او حذف واحدة منهم
    private function cleanDomain2(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = trim($domain, '/');
        $domain = preg_replace('/\s+/', '', $domain);

        return $domain;
    }

    private function extractTld(string $domain): string
    {
        $parts = explode('.', $domain);
        return end($parts);
    }


    public function setupdnsForDomain(Request $request)
    {

        $domainName = $request->domain;
        if (blank($this->whmUrl) || blank($this->whmToken)) {
            Log::error('WHM configuration is missing while linking a domain.', [
                'tenant_id' => tenant('id'),
                'domain' => $domainName,
            ]);

            return redirect()->back()->with('error', 'إعدادات استضافة الدومين غير مكتملة.');
        }

        // $method = $request->method;
        $exists = Domain::where('domain', $domainName)->first();
        if ($exists) {
            return redirect()->back()->with('info', 'الدومين موجود بالفعل ');
        }
        $tenantId = tenant('id');
        // Domain is already verified by verifyDNS() before reaching here
        return  $saved = tenancy()->central(function () use ($domainName, $tenantId, $request) {
            $cpanel = CpanelAccount::where('tenant_id', $tenantId)->first();

            if ($cpanel) {
                $this->namecheapService->createDomainRowWithActivation($request->domain, 'linked', $tenantId);
                $this->namecheapService->modifyAccount($domainName, $tenantId);
                return $this->cpanelService->createLoginAndToken($domainName, $cpanel->cpanel_username, $tenantId);
            }

            $prefix = 'tn';
            $unique = substr(md5($domainName . microtime()), 0, 6);
            $whmUser = strtolower($prefix . $unique);
            $whmPass = Str::random(16) . '@1Aa';

            $whmResponse = Http::withHeaders([
                'Authorization' => 'whm levan:' . $this->whmToken,
            ])->timeout(60)->get(rtrim($this->whmUrl, '/') . '/json-api/createacct', [
                'username' => $whmUser,
                'domain' => $domainName,
                'password' => $whmPass,
                'plan' => 'default_levan',
                'contactemail' => 'islamay124@gmail.com',
                'featurelist' => 'default',
                'quota' => 0,
                'hasshell' => 0,
                'ip' => 'n',
            ]);

            $whmJson = $whmResponse->json();

            if ($whmJson['result'][0]['status'] == 0) {
                return redirect()->back()->with('info', $whmJson['result'][0]['statusmsg']);
            }

            $cpanelAcc =  CpanelAccount::create([
                'cpanel_username' => $whmUser,
                'cpanel_password' => $whmPass,
                'tenant_id' => $tenantId,
                'domain' => $domainName,
                // 'created_at' => now(),
                // 'updated_at' => now(),
            ]);
            if ($cpanelAcc) {
                // $this->namecheapService->modifyAccount($request);
                $this->namecheapService->createDomainRowWithActivation($request->domain, 'linked', $tenantId);

                return $this->cpanelService->createLoginAndToken($domainName, $cpanelAcc->cpanel_username, $tenantId);
            }
        });
    }



    public function modifyaccount(Request $request)
    {
        return  $this->namecheapService->modifyAccount($request->domain, $request->tenant_id);
    }
}
