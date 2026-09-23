<?php

namespace App\Services\shared\Cpanel;

use App\Models\Admin\Cpanel\CpanelAccount;
use App\Models\Admin\Tenants\Tenant;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CpanelService
{
    protected ?Client $client = null;
    protected ?string $cpanelUser = null;
    protected ?string $cpanelHost = null;
    protected ?string $cpanelToken = null;

    public function __construct()
    {
        $this->cpanelUser   = config('cpanel.user');
        $this->cpanelHost   = config('cpanel.shared_ip');
        $this->cpanelToken  = config('cpanel.api_token');
    }

    protected function getClient(): Client
    {
        if ($this->client === null) {
            if (empty($this->cpanelUser) || empty($this->cpanelHost) || empty($this->cpanelToken)) {
                $missing = [];
                if (empty($this->cpanelUser)) $missing[] = 'CPANEL_USER';
                if (empty($this->cpanelHost)) $missing[] = 'CPANEL_SHARED_IP';
                if (empty($this->cpanelToken)) $missing[] = 'CPANEL_API_TOKEN';

                Log::error(" Missing cPanel credentials", $missing);
                throw new Exception(
                    'Missing cPanel credentials in .env: ' . implode(', ', $missing)
                );
            }


            $this->client = new Client([
                'base_uri'          => "https://{$this->cpanelHost}:2083/execute/",
                'verify'            => config('cpanel.ssl_verify', false),
                'timeout'           => 30,
                'connect_timeout'   => 10,
                'headers' => [
                    'Authorization' => "cpanel {$this->cpanelUser}:{$this->cpanelToken}",
                    'Accept' => 'application/json',
                    'User-Agent' => 'Laravel-cPanel-Client/1.0',
                ],
            ]);
        }

        return $this->client;
    }

    public function createDatabase(string $tenantName, string $username): array
    {
        $databaseName = "{$this->cpanelUser}_{$tenantName}";
        $userName     = "{$this->cpanelUser}_{$username}";
        $userPassword = $this->generateSecurePassword();

        try {
            $this->createDatabaseOnly($databaseName);
            $this->createUser($userName, $userPassword);
            $this->setPrivileges($databaseName, $userName);

            return [
                'host'     => $this->cpanelHost,
                'database' => $databaseName,
                'username' => $userName,
                'password' => $userPassword,
            ];
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            Log::error('Connection error while provisioning cPanel DB', [
                'tenant' => $tenantName,
                'user'   => $username,
                'error'  => $e->getMessage(),
            ]);
            throw new \RuntimeException('Connection error: ' . $e->getMessage(), 0, $e);
        } catch (\Throwable $e) {
            Log::error('Provisioning failed', [
                'tenant' => $tenantName,
                'user'   => $username,
                'error'  => $e->getMessage(),
            ]);
            throw new \RuntimeException('Provisioning failed: ' . $e->getMessage(), 0, $e);
        }
    }



    /*
    |============================================================================
    |============================================================================
    |                          Private methods
    |============================================================================
    |============================================================================
    */
    private function generateSecurePassword(): string
    {
        $password = Str::random(16) . '1!Aa';
        return $password;
    }

    private function createDatabaseOnly(string $databaseName): array
    {
        $response = $this->getClient()->get("Mysql/create_database", [
            'query' => ['name' => $databaseName]
        ]);
        $database = json_decode($response->getBody(), true);

        if (empty($database['status']) || $database['status'] == 0) {
            Log::error(" Failed to create database", $database);
            throw new Exception("Failed to create database: " . ($database['errors'][0] ?? 'Unknown error'));
        }

        return $database;
    }


    protected function createUser(string $userName, string $userPassword): void
    {
        $response = $this->getClient()->get("Mysql/create_user", [
            'query' => [
                'name' => $userName,
                'password' => $userPassword
            ]
        ]);

        $user = json_decode($response->getBody(), true);

        if (empty($user['status']) || $user['status'] == 0) {
            Log::error(" Failed to create user", $user);
            throw new Exception("Failed to create user: " . ($user['errors'][0] ?? 'Unknown error'));
        }
    }


    private function setPrivileges(string $databaseName, string $userName): array
    {
        $response = $this->getClient()->get("Mysql/set_privileges_on_database", [
            'query' => [
                'user' => $userName,
                'database' => $databaseName,
                'privileges' => 'ALL PRIVILEGES'
            ]
        ]);

        $privileges = json_decode($response->getBody(), true);

        if (empty($privileges['status']) || $privileges['status'] == 0) {
            Log::error(" Failed to set privileges", $privileges);
            throw new Exception("Failed to set privileges: " . ($privileges['errors'][0] ?? 'Unknown error'));
        }

        return $privileges;
    }

    // create login and token
    // TODO islam
    // التاكد هل الدالة مستخدم واذا كانت مستخدمة ازل كل التعليقات فيها و اجعلها تعتمد على قيمة ملف الconfig
    public function createLoginAndToken($domain, $cpanelUser, $tenantId)
    {
        // $cpanelUser = tenant('cpanel_username');
        $cpanelHost = '135.181.215.105'; /// لا تستخدم قيم ثابته
        // $cpanelPassword = 'H27dH_GEgfXL?3';
        // $tenantId = tenant('id');

        $cpanel_account = tenancy()->central(function () use ($domain, $tenantId) {
            return  DB::table('cpanel_accounts')->where('tenant_id', $tenantId)->first();
        });

        $loginUrl = "https://{$cpanelHost}:2083/login/?login_only=1";

        Log::info("🔑 Attempting login for user: {$cpanelUser}");

        $loginResponse = Http::withoutVerifying()
            ->timeout(30)
            ->asForm()
            ->post($loginUrl, [
                'user' => $cpanel_account->cpanel_username,
                'pass' => $cpanel_account->cpanel_password,
            ]);

        if (!$loginResponse->successful()) {
            throw new \Exception("❌ Login HTTP request failed with status: " . $loginResponse->status());
        }

        $loginData = $loginResponse->json();

        // if (!isset($loginData['security_token'])) {
        //     Log::error('❌ Login failed - no security token in response', $loginData);
        //     throw new \Exception("❌ Login failed - Invalid credentials or response");
        // }

        $securityToken = $loginData['security_token'];
        Log::info("✅ Login successful, security token obtained");

        // ✅ حول الـ cookies لـ array
        $cookies = [];
        foreach ($loginResponse->cookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        // ✅ إنشاء التوكن
        $tokenUrl = "https://{$cpanelHost}:2083{$securityToken}/execute/Tokens/create_full_access";

        Log::info("🎫 Creating full access token for user: {$cpanelUser}");

        $tokenResponse = Http::withoutVerifying()
            ->timeout(30)
            ->withCookies($cookies, $cpanelHost)
            ->asForm()
            ->post($tokenUrl, [
                'name' => $cpanelUser . '_token_' . time(),
            ]);



        $tokenData = $tokenResponse->json();

        Log::info('Token API Response:', $tokenData);
        //    $this->writeFileContentForUser($tokenData['data']['token']);
        // ✅ تحقق من وجود التوكن
        if (empty($tokenData['data']['token'])) {
            Log::error('❌ Token creation failed - no token in response', $tokenData);
            throw new \Exception("❌ Token creation failed - Invalid response");
        }

        $token = $tokenData['data']['token'];
        $saved = DB::table('cpanel_accounts')
            ->where('tenant_id', $tenantId)
            ->update(['cpanel_token' => $token]);


        // $saved = tenancy()->central(function () use ($cpanelUser, $cpanelPassword, $token, $tenantId, $domain) {

        //     DB::table('cpanel_accounts')
        //         ->where('cpanel_username', $cpanelUser)
        //         ->delete();

        //     // ✅ احفظ السجل الجديد
        //     return DB::table('cpanel_accounts')->insert([
        //         'cpanel_username' => $cpanelUser,
        //         'cpanel_password' => $cpanelPassword,
        //         'cpanel_token' => $token,
        //         'tenant_id' => $tenantId,
        //         'domain' => $domain,
        //         // 'created_at' => now(),
        //         // 'updated_at' => now(),
        //     ]);
        // });
        if ($saved) {
            // tenancy()->central(function () use ($token) {
            //     $cpanel = DB::table('cpanel_accounts')
            //         ->where('cpanel_token', $token)
            //         ->first();



            return $this->writeFileContentForUser($token, $domain, $tenantId);

            // });
        }
    }


    // TODO islam

    // راجع وازل التعليقات
    // افصل المحتوى htaccessContent في ملف منفصل وقم بمناداته هنا
    // لا يجب الاعتماد على قيم ثابته هنا '/home/levan/public_html/releases/v1.0.0-beta/public';
    protected function writeFileContentForUser($token, $domain, $tenantId)
    {

        // $username = tenancy()->central(function () use ($domain) {
        $username =  CpanelAccount::where('tenant_id', $tenantId)->first()->cpanel_username;
        // });
        $host = '135.181.215.105';
        $domain = $domain;

        // ✅ محتوى ملف .htaccess
        $htaccessContent = <<<HTACCESS
# فعّل mod_rewrite
RewriteEngine On
RewriteBase /

# ✅ السماح بمتابعة الـ Symlinks
Options +FollowSymLinks

# ✅ استثناء الملفات والمجلدات الموجودة (بما فيها الـ Symlinks)
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d [OR]
RewriteCond %{REQUEST_FILENAME} -l
RewriteRule ^ - [L]

# ✅ توجيه الباقي لـ index.php
RewriteRule ^ index.php [L]
HTACCESS;

        // ✅ محتوى ملف index.php
        $indexContent = <<<'PHP'
<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

// ✅ استخراج المسار
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = array_filter(explode('/', trim($path, '/')));
$firstSegment = $segments ? reset($segments) : null;

// ✅ امتدادات الـ assets
$fileExtension = pathinfo($path, PATHINFO_EXTENSION);
$assetExtensions = ['js', 'css', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'woff', 'woff2', 'ttf', 'eot', 'ico', 'webp'];

// ✅ مسار مسند
$levanPublicPath = '/home/levan/public_html/releases/v1.0.0-beta/public';

// ===============================
// ✅ Assets Routing
// ===============================
if (in_array(strtolower($fileExtension), $assetExtensions) || preg_match('#^/levan/(build|storage)/#i', $path)) {
    $assetPath = preg_replace('#^/levan#i', '', $path);
    $fullAssetPath = $levanPublicPath . $assetPath;

    if (file_exists($fullAssetPath)) {
        $mimeTypes = [
            'js' => 'application/javascript', 'css' => 'text/css',
            'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif', 'svg' => 'image/svg+xml',
            'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject', 'ico' => 'image/x-icon', 'webp' => 'image/webp',
        ];

        header("Content-Type: " . ($mimeTypes[$fileExtension] ?? 'application/octet-stream'));
        readfile($fullAssetPath);
        exit;
    }

    http_response_code(404);
    exit;
}

// ===============================
// ✅ Levan Routing
// ===============================
if ($firstSegment === 'levan') {

    // 🎯 حقن الهيدر قبل ما تشغل Laravel
    $_SERVER['HTTP_LEVAN'] = 'true';
     putenv('CACHE_DRIVER=array');
    putenv('LOG_CHANNEL=errorlog');

    $_ENV['CACHE_DRIVER'] = 'array';
    $_ENV['LOG_CHANNEL'] = 'errorlog';
    $levanIndexPath = $levanPublicPath . '/index.php';

    if (!file_exists($levanIndexPath)) {
        http_response_code(500);
        echo "❌ ملف Levan غير موجود";
        exit;
    }

    // ✅ ضبط الـ URI
    $originalUri = $_SERVER['REQUEST_URI'];
    if (!str_starts_with($originalUri, '/levan')) {
        $originalUri = '/levan' . $originalUri;
    }

    $queryString = $_SERVER['QUERY_STRING'] ?? '';
    if ($queryString && !str_contains($originalUri, '?')) {
        $originalUri .= '?' . $queryString;
    }

    $_SERVER['REQUEST_URI'] = $originalUri;
    $_SERVER['SCRIPT_NAME'] = '/levan/index.php';
    $_SERVER['PHP_SELF'] = '/levan/index.php';

    // ✅ تشغيل Laravel
    chdir(dirname($levanIndexPath));
    require_once $levanIndexPath;
    exit;
}

// ===============================
// ✅ الموقع الأساسي
// ===============================
$htmlFile = __DIR__ . '/index.html';
if (file_exists($htmlFile)) {
    readfile($htmlFile);
    exit;
}

echo "<h2>Welcome to main site</h2>";
echo "<p><a href='/levan'>Go to Levan</a></p>";
PHP;

        // ✅ المسار داخل cPanel
        $dir = 'public_html';
        $url = "https://{$host}:2083/execute/Fileman/upload_files";

        // ✅ رفع ملف .htaccess
        $htaccessResponse = Http::withoutVerifying()
            ->withHeaders([
                'Authorization' => "cpanel {$username}:{$token}",
            ])
            ->asMultipart()
            ->post($url, [
                [
                    'name' => 'dir',
                    'contents' => $dir,
                ],
                [
                    'name' => 'overwrite',
                    'contents' => '1',
                ],
                [
                    'name' => 'file-1',
                    'filename' => '.htaccess',
                    'contents' => $htaccessContent,
                ],
            ]);

        if (!$htaccessResponse->successful()) {
            throw new \Exception("❌ HTTP Error uploading .htaccess: " . $htaccessResponse->status() . " - " . $htaccessResponse->body());
        }

        $htaccessData = $htaccessResponse->json();
        info('📄 cPanel .htaccess upload Response', $htaccessData);

        if (!isset($htaccessData['status']) || $htaccessData['status'] != 1) {
            throw new \Exception("⚠️ Could not create .htaccess: " . json_encode($htaccessData));
        }

        info("✅ File '.htaccess' created successfully inside {$dir}");

        // ✅ رفع ملف index.php
        $indexResponse = Http::withoutVerifying()
            ->withHeaders([
                'Authorization' => "cpanel {$username}:{$token}",
            ])
            ->asMultipart()
            ->post($url, [
                [
                    'name' => 'dir',
                    'contents' => $dir,
                ],
                [
                    'name' => 'overwrite',
                    'contents' => '1',
                ],
                [
                    'name' => 'file-1',
                    'filename' => 'index.php',
                    'contents' => $indexContent,
                ],
            ]);

        if (!$indexResponse->successful()) {
            throw new \Exception("❌ HTTP Error uploading index.php: " . $indexResponse->status() . " - " . $indexResponse->body());
        }

        $indexData = $indexResponse->json();
        if ($indexData['status'] == 1) {
            return redirect()->back()->with('success', 'تمت العميلة بنجاح');
        }
        info('📄 cPanel index.php upload Response', $indexData);

        if (!isset($indexData['status']) || $indexData['status'] != 1) {
            throw new \Exception("⚠️ Could not create index.php: " . json_encode($indexData));
        }

        info("✅ File 'index.php' created successfully inside {$dir}");

        return true;
    }


    public function checkLimits()
    {
        $host     = 'server.levan.com';  // بدون https:// ولا البورت
        $port     = '2087';
        $username = env('WHM_USER', 'levan');     // حط في .env
        $token    = env('WHM_TOKEN');            // أفضل تستخدم Token مش باسورد

        // لو بتستخدم باسورد بدل Token (مش موصى به للأمان)
        // $password = env('WHM_PASSWORD');

        $baseUrl = "https://{$host}:{$port}";

        // طريقة 1: باستخدام API Token (موصى بها جداً)
        return $response = Http::withHeaders([
            'Authorization' => "whm {$username}:{$token}",
            'Accept'        => 'application/json',
        ])->get("{$baseUrl}/json-api/get_maximum_users", [
            'api.version' => 1,
        ]);


        if ($response->failed()) {
            Log::error('WHM API failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل الاتصال بـ WHM API',
                'error'   => $response->body(),
            ], $response->status() ?: 500);
        }

        $data = $response->json();

        // التحقق من النتيجة
        $metadata = $data['metadata'] ?? [];
        if (($metadata['result'] ?? 0) !== 1) {
            return response()->json([
                'success' => false,
                'message' => $metadata['reason'] ?? 'خطأ غير معروف في الـ API',
                'raw'     => $data,
            ], 400);
        }

        // استخراج الحد الأقصى
        // في بعض النسخ يكون 'users' وفي نسخ تانية 'maximum_users'
        $maxRaw = $data['data']['users']
            ?? $data['data']['maximum_users']
            ?? 'غير معروف';

        $maxUsers = is_numeric($maxRaw) ? (int) $maxRaw : 0;

        // تفسير القيمة
        $isUnlimited = ($maxUsers === 0);
        $limitText   = $isUnlimited
            ? 'غير محدود (Unlimited - يمكنك إنشاء أي عدد من الحسابات)'
            : "الحد الأقصى: {$maxUsers} حساب";

        // لو عايز نجيب عدد الحسابات الحالية كمان (اختياري)
        // $currentCount = $this->getCurrentAccountsCount($baseUrl, $username, $token);

        $remaining = $isUnlimited
            ? 'غير محدود'
            : max(0, $maxUsers - ($currentCount ?? 0));

        return response()->json([
            'success'          => true,
            'license_max_raw'  => $maxRaw,
            'license_max'      => $limitText,
            'is_unlimited'     => $isUnlimited,
            'current_accounts' => $currentCount ?? 'غير متاح',
            'remaining_slots'  => $remaining,
            'full_response'    => $data,  // للـ debug لو عايز
        ]);
    }
}
