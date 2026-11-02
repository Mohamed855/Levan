<?php

namespace App\Jobs\Domain;

use App\Models\Admin\Cpanel\CpanelAccount;
use App\Services\Shared\NameCheap\NameCheapService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateloginAndTokenJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $domain;

    public $tries = 1;

    public $backoff = 10;

    public $tenantId;

    private NameCheapService $namecheapService;

    public function __construct($domain, $tenantId)
    {
        $this->domain = $domain;
        $this->tenantId = $tenantId;
    }

    public function handle(): void
    {
        Log::info('🔐 Starting login and token process for domain: '.$this->domain);

        try {
            $tenantId = tenant('id');
            // $cpanel = tenancy()->central(function () use ($tenantId) {
            //     return  CpanelAccount::where('tenant_id', $tenantId)->first();
            // });
            // $cpanelUser = explode('.', $this->domain)[0];
            // $cpanelPassword =  Str::random(16) . '@1Aa';
            $cpanelHost = '135.181.215.105';
            // if ($cpanel) {
            //     $cpanelUser = $cpanel->cpanel_username;
            //     $cpanelPassword = $cpanel->cpanel_password;
            // }
            $this->createCpanelAccount();
            $cpanel = tenancy()->central(function () use ($tenantId) {
                return DB::table('cpanel_accounts')->where('tenant_id', $tenantId)->first();
            });

            // ✅ تسجيل الدخول
            $loginUrl = "https://{$cpanelHost}:2083/login/?login_only=1";

            Log::info("🔑 Attempting login for user: {$cpanel->cpanel_username}");

            $loginResponse = Http::withoutVerifying()
                ->timeout(30)
                ->asForm()
                ->post($loginUrl, [
                    'user' => $cpanel->cpanel_username,
                    'pass' => $cpanel->cpanel_password,
                ]);

            if (! $loginResponse->successful()) {
                throw new Exception('❌ Login HTTP request failed with status: '.$loginResponse->status());
            }

            $loginData = $loginResponse->json();

            // if (!isset($loginData['security_token'])) {
            //     Log::error('❌ Login failed - no security token in response', $loginData);
            //     throw new \Exception("❌ Login failed - Invalid credentials or response");
            // }

            $securityToken = $loginData['security_token'];
            Log::info('✅ Login successful, security token obtained');

            // ✅ حول الـ cookies لـ array
            $cookies = [];
            foreach ($loginResponse->cookies() as $cookie) {
                $cookies[$cookie->getName()] = $cookie->getValue();
            }

            // ✅ إنشاء التوكن
            $tokenUrl = "https://{$cpanelHost}:2083{$securityToken}/execute/Tokens/create_full_access";

            Log::info("🎫 Creating full access token for user: {$cpanel->cpanel_username}");

            $tokenResponse = Http::withoutVerifying()
                ->timeout(30)
                ->withCookies($cookies, $cpanelHost)
                ->asForm()
                ->post($tokenUrl, [
                    'name' => $cpanel->cpanel_username.'_token_'.time(),
                ]);

            $tokenData = $tokenResponse->json();

            Log::info('Token API Response:', $tokenData);
            //    $this->writeFileContentForUser($tokenData['data']['token']);
            // ✅ تحقق من وجود التوكن
            if (empty($tokenData['data']['token'])) {
                Log::error('❌ Token creation failed - no token in response', $tokenData);
                throw new Exception('❌ Token creation failed - Invalid response');
            }

            $token = $tokenData['data']['token'];
            $cpanelUser = $cpanel->cpanel_username;
            $cpanelPassword = $cpanel->cpanel_password;
            // ✅ احفظ في الـ Database (Central)
            $saved = tenancy()->central(function () use ($token, $tenantId) {
                // ✅ امسح أي سجلات قديمة للدومين ده
                // DB::table('cpanel_accounts')
                //     ->where('domain', $this->domain)
                //     ->delete();
                return DB::table('cpanel_accounts')
                    ->where('tenant_id', $tenantId)
                    ->update(['cpanel_token' => $token]);

                // ✅ احفظ السجل الجديد

            });
            if ($saved) {
                // tenancy()->central(function () use ($token) {
                //     $cpanel = DB::table('cpanel_accounts')
                //         ->where('cpanel_token', $token)
                //         ->first();

                $this->writeFileContentForUser($token);

                // });
            }
        } catch (Exception $e) {
            Log::error('❌ CreateloginAndTokenJob failed: '.$e->getMessage());
            Log::error('Stack trace: '.$e->getTraceAsString());

            throw $e;
        }
    }

    protected function createCpanelAccount()
    {
        $cpanelUsername = explode('.', $this->domain)[0];

        // CpanelAccount::find($this->tenantId)->update([
        //     'domain' => $cpanelUsername,
        // ]);

        // توليد باسورد قوي
        $password = Str::random(16).rand(10, 99).'!@';

        // إنشاء الحساب
        $whmUser = env('WHM_USER', 'lot');
        $whmToken = env('WHM_TOKEN', '8EPSQ67GW3XWGGRBYN51EP105R6C31P5');
        $whmHost = env('WHM_HOST', 'server.lot.sa');
        $response = Http::withHeaders([
            'Authorization' => "whm {$whmUser}:{$whmToken}",
        ])->timeout(60)->get("https://{$whmHost}:2087/json-api/createacct", [
            'username' => $cpanelUsername,
            'domain' => $this->domain,
            'password' => $password,
            'plan' => env('WHM_DEFAULT_PLAN', 'default_lot'),
            'contactemail' => 'islamay124@gmail.com',
            'featurelist' => 'default',
            'quota' => 0, // Unlimited
            'hasshell' => 0,
            'ip' => 'n', // Use shared IP
        ]);
        $tenantId = $this->tenantId;
        $domain = $this->domain;

        return $cpanel = tenancy()->central(function () use ($cpanelUsername, $password, $tenantId, $domain) {
            return DB::table('cpanel_accounts')->insert([
                'cpanel_username' => $cpanelUsername,
                'cpanel_password' => $password,
                'tenant_id' => $tenantId,
                'domain' => $domain,
                'created_at' => now(),
                // 'updated_at' => now(),
            ]);
        });
        if ($cpanel) {
            Log::info('cpanel account created');
        } else {
            Log::info('not created');
        }

        if (! $response->successful()) {
            throw new Exception('Failed to create cPanel account: HTTP '.$response->status());
        }

    }

    public function failed(\Throwable $exception): void
    {
        Log::error('❌ CreateloginAndTokenJob failed permanently after all retries');
        Log::error('Domain: '.$this->domain);
        Log::error('Error: '.$exception->getMessage());
    }

    // شغال مع cpanel
    protected function writeFileContentForUser($token)
    {
        $username = explode('.', $this->domain)[0]; // اسم حساب cPanel
        $host = '135.181.215.105';
        $domain = $this->domain;

        // ✅ محتوى ملف .htaccess
        $htaccessContent = <<<'HTACCESS'
<IfModule mod_rewrite.c>
    RewriteEngine On

    # ✅ كل حاجة تروح لـ index.php (اللي هو هيعمل routing)
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [L]
</IfModule>
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

// ✅ مسار لوت / Lot
$lotPublicPath = env('LOT_PUBLIC_PATH', '/home/lot/public_html/releases/v1.0.0-beta/public');
if (!file_exists($lotPublicPath) && file_exists('/home/levan/public_html/releases/v1.0.0-beta/public')) {
    $lotPublicPath = '/home/levan/public_html/releases/v1.0.0-beta/public';
}

// ===============================
// ✅ Assets Routing
// ===============================
if (in_array(strtolower($fileExtension), $assetExtensions) || preg_match('#^/(lot|levan)/(build|storage)/#i', $path)) {
    $assetPath = preg_replace('#^/(lot|levan)#i', '', $path);
    $fullAssetPath = $lotPublicPath . $assetPath;

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
// ✅ Lot Routing
// ===============================
if ($firstSegment === 'lot' || $firstSegment === 'levan') {
    
    // 🎯 حقن الهيدر قبل ما تشغل Laravel
    $_SERVER['HTTP_LOT'] = 'true';
    $_SERVER['HTTP_LEVAN'] = 'true';
     putenv('CACHE_DRIVER=array');
    putenv('LOG_CHANNEL=errorlog');
    
    $_ENV['CACHE_DRIVER'] = 'array';
    $_ENV['LOG_CHANNEL'] = 'errorlog';
    $lotIndexPath = $lotPublicPath . '/index.php';

    if (!file_exists($lotIndexPath)) {
        http_response_code(500);
        echo "❌ ملف Lot غير موجود";
        exit;
    }

    // ✅ ضبط الـ URI
    $originalUri = $_SERVER['REQUEST_URI'];
    if (!str_starts_with($originalUri, '/'.$firstSegment)) {
        $originalUri = '/'.$firstSegment . $originalUri;
    }

    $queryString = $_SERVER['QUERY_STRING'] ?? '';
    if ($queryString && !str_contains($originalUri, '?')) {
        $originalUri .= '?' . $queryString;
    }

    $_SERVER['REQUEST_URI'] = $originalUri;
    $_SERVER['SCRIPT_NAME'] = '/'.$firstSegment.'/index.php';
    $_SERVER['PHP_SELF'] = '/'.$firstSegment.'/index.php';

    // ✅ تشغيل Laravel
    chdir(dirname($lotIndexPath));
    require_once $lotIndexPath;
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
echo "<p><a href='/lot'>Go to Lot</a></p>";
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

        if (! $htaccessResponse->successful()) {
            throw new Exception('❌ HTTP Error uploading .htaccess: '.$htaccessResponse->status().' - '.$htaccessResponse->body());
        }

        $htaccessData = $htaccessResponse->json();
        info('📄 cPanel .htaccess upload Response', $htaccessData);

        if (! isset($htaccessData['status']) || $htaccessData['status'] != 1) {
            throw new Exception('⚠️ Could not create .htaccess: '.json_encode($htaccessData));
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

        if (! $indexResponse->successful()) {
            throw new Exception('❌ HTTP Error uploading index.php: '.$indexResponse->status().' - '.$indexResponse->body());
        }

        $indexData = $indexResponse->json();
        info('📄 cPanel index.php upload Response', $indexData);

        if (! isset($indexData['status']) || $indexData['status'] != 1) {
            throw new Exception('⚠️ Could not create index.php: '.json_encode($indexData));
        }

        info("✅ File 'index.php' created successfully inside {$dir}");

        return true;
    }
}
