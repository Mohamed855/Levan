<?php
namespace App\Http\Controllers\Tenants\__LinkDomain;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LinkDomainController extends Controller
{
      public function linkExternalDomain()
    {
        return view('domain.link-external-domain');
    }


    
    protected $whmToken;
    protected $whmUrl;
    protected $serverIp;

    public function __construct()
    {
        $this->whmToken = env('WHM_TOKEN');
        $this->whmUrl = env('WHM_HOST');
        $this->serverIp = env('135.181.215.105');
    }

  
    public function processLinkExternalDomain(Request $request)
    {
        $domainName = trim($request->domain_name);
        $dns1 = trim($request->ns1);
        $dns2 = trim($request->ns2);

        try {
            // ============================
            // 1️⃣ التحقق من البيانات
            // ============================
            if (empty($domainName) || empty($dns1) || empty($dns2)) {
                return $this->respondError('❌ جميع الحقول مطلوبة (الدومين + NS1 + NS2)', $request);
            }

            // ============================
            // 2️⃣ إضافة Zone للدومين الخارجي
            // ============================
            $zoneResponse = Http::withHeaders([
                'Authorization' => 'whm root:' . $this->whmToken,
            ])->timeout(60)->get($this->whmUrl . '/addzone', [
                'domain'      => $domainName,
                'ip'          => $this->serverIp,
                'nameserver1' => $dns1,
                'nameserver2' => $dns2,
            ]);

            $zoneJson = $zoneResponse->json();

            if (!isset($zoneJson['metadata']['result']) || $zoneJson['metadata']['result'] != 1) {
                $errorMsg = $zoneJson['metadata']['reason'] ?? 'خطأ في إضافة Zone';
                Log::error('WHM Zone Addition Failed', ['error' => $errorMsg, 'domain' => $domainName]);
                return $this->respondError('❌ فشل إضافة الدومين: ' . $errorMsg, $request);
            }

            Log::info('External Domain Zone Added Successfully', [
                'domain' => $domainName,
                'ns1' => $dns1,
                'ns2' => $dns2,
            ]);

            sleep(2);
 
            $this->addARecord($domainName, $domainName . '.', $this->serverIp);
            sleep(1);

             $this->addARecord($domainName, 'www.' . $domainName . '.', $this->serverIp);
            sleep(1);

           
            foreach ([$dns1, $dns2] as $ns) {
                $this->addNSRecord($domainName, $domainName . '.', $ns);
                sleep(1);
            }

            $successMessage = "✅ تم ربط الدومين الخارجي بنجاح!\n\nأشر NameServers الدومين على:\n🔹 {$dns1}\n🔹 {$dns2}";

            Log::info('External Domain Linked Successfully', [
                'domain' => $domainName,
                'ns1' => $dns1,
                'ns2' => $dns2,
                'ip' => $this->serverIp,
            ]);

            return $this->respondSuccess($successMessage, [
                'domain' => $domainName,
                'ns1' => $dns1,
                'ns2' => $dns2,
                'ip' => $this->serverIp,
                'type' => 'external'
            ], $request);

        } catch (\Throwable $th) {
            Log::error('Link External Domain Error', [
                'domain' => $domainName ?? 'N/A',
                'error' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);

            return $this->respondError('❌ حدث خطأ: ' . $th->getMessage(), $request);
        }
    }

 
    public function linkSubDomain()
    {
        return view('domain.link-subdomain');
    }

    // ========================================
    // 🔗 معالجة ربط صب دومين
    // ========================================
    public function processLinkSubDomain(Request $request)
    {
        $domainName = trim($request->domain_name);
        $subDomainName = trim($request->subdomain_name);
        $plan = $request->plan ?? 'default_levan';

        try {
            // ============================
            // 1️⃣ التحقق من البيانات
            // ============================
            if (empty($domainName) || empty($subDomainName)) {
                return $this->respondError('❌ الدومين الأساسي واسم الصب دومين مطلوبان', $request);
            }

            // ============================
            // 2️⃣ إنشاء حساب الاستضافة
            // ============================
            $prefix = 'sub';
            $unique = substr(md5($subDomainName . microtime()), 0, 6);
            $whmUser = strtolower($prefix . $unique);
            $whmPass = Str::random(16) . '@1Aa';
            $whmEmail = 'admin@example.com';

            // النطاق الكامل للصب دومين
            $fullSubDomain = $subDomainName . '.' . $domainName;

            $whmResponse = Http::withHeaders([
                'Authorization' => 'whm root:' . $this->whmToken,
            ])->timeout(60)->get($this->whmUrl . '/createacct', [
                'api.version'     => 1,
                'username'        => $whmUser,
                'domain'          => $fullSubDomain,
                'plan'            => $plan,
                'password'        => $whmPass,
                'contactemail'    => $whmEmail,
                'ip'              => $this->serverIp,
                'skipdomaincheck' => 1,
            ]);

            $whmJson = $whmResponse->json();

            if (!isset($whmJson['metadata']['result']) || $whmJson['metadata']['result'] != 1) {
                $errorMsg = $whmJson['metadata']['reason'] ?? 'خطأ غير معروف';
                Log::error('WHM Account Creation Failed for SubDomain', [
                    'error' => $errorMsg,
                    'subdomain' => $fullSubDomain
                ]);
                return $this->respondError('❌ فشل إنشاء الحساب: ' . $errorMsg, $request);
            }

            Log::info('WHM Account Created for SubDomain', [
                'subdomain' => $fullSubDomain,
                'username' => $whmUser,
            ]);

            sleep(3);

            // ============================
            // 3️⃣ إضافة A Records للصب دومين
            // ============================
            // A Record للـ Subdomain
            $this->addARecord($domainName, $fullSubDomain . '.', $this->serverIp);
            sleep(1);

            // A Record للـ www.subdomain
            $this->addARecord($domainName, 'www.' . $fullSubDomain . '.', $this->serverIp);

            $successMessage = "🎉 تم إنشاء الصب دومين بنجاح!\n\n📋 بيانات الوصول:\n🔹 النطاق: {$fullSubDomain}\n🔹 اسم المستخدم: {$whmUser}\n🔹 لوحة التحكم: https://{$fullSubDomain}:2083";

            Log::info('SubDomain Setup Completed Successfully', [
                'subdomain' => $fullSubDomain,
                'user' => $whmUser,
                'ip' => $this->serverIp,
            ]);

            return $this->respondSuccess($successMessage, [
                'subdomain' => $fullSubDomain,
                'username' => $whmUser,
                'cpanel_url' => "https://{$fullSubDomain}:2083",
                'type' => 'subdomain'
            ], $request);

        } catch (\Throwable $th) {
            Log::error('Link SubDomain Error', [
                'domain' => $domainName ?? 'N/A',
                'error' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);

            return $this->respondError('❌ حدث خطأ: ' . $th->getMessage(), $request);
        }
    }

    // ========================================
    // 🔧 دوال مساعدة
    // ========================================

    private function addARecord($domain, $name, $address)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'whm root:' . $this->whmToken,
            ])->timeout(30)->get($this->whmUrl . '/addzonerecord', [
                'domain'  => $domain,
                'name'    => $name,
                'type'    => 'A',
                'address' => $address,
                'ttl'     => 14400,
            ]);

            Log::info("A Record Added", [
                'name' => $name,
                'ip' => $address,
                'response' => $response->json()
            ]);

            return $response->json();
        } catch (\Throwable $th) {
            Log::error("Failed to Add A Record", [
                'name' => $name,
                'error' => $th->getMessage()
            ]);
            throw $th;
        }
    }

    private function addNSRecord($domain, $name, $nsdname)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'whm root:' . $this->whmToken,
            ])->timeout(30)->get($this->whmUrl . '/addzonerecord', [
                'domain'  => $domain,
                'name'    => $name,
                'type'    => 'NS',
                'nsdname' => $nsdname,
                'ttl'     => 86400,
            ]);

            Log::info("NS Record Added", [
                'ns' => $nsdname,
                'response' => $response->json()
            ]);

            return $response->json();
        } catch (\Throwable $th) {
            Log::error("Failed to Add NS Record", [
                'ns' => $nsdname,
                'error' => $th->getMessage()
            ]);
            throw $th;
        }
    }

    private function respondSuccess($message, $data, Request $request)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'type' => 'success',
                'data' => $data
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    private function respondError($message, Request $request)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'type' => 'error'
            ], 400);
        }

        return redirect()->back()->with('error', $message);
    }
}