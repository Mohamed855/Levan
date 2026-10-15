<?php

namespace App\Jobs\Domain;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CreateFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $username;

    public $token;

    public $domain;

    public $tries = 1;

    public $backoff = 10;

    public function __construct($username, $domain, $token = null)
    {
        $this->username = $username;
        $this->domain = $domain;
        $this->token = $token;
    }

    public function handle(): void
    {
        Log::info('📁 Starting file creation process for domain: '.$this->domain);

        try {
            // ✅ جيب البيانات من الـ Database (Central)
            $cpanelAccount = tenancy()->central(function () {
                return DB::table('cpanel_accounts')
                    ->where('domain', $this->domain)
                    ->first();
            });

            if (! $cpanelAccount) {
                Log::error("❌ cPanel account not found in database for domain: {$this->domain}");
                throw new \Exception('❌ cPanel account not found. Previous job may have failed.');
            }

            if (empty($cpanelAccount->cpanel_token)) {
                Log::error("❌ cPanel token is empty in database for domain: {$this->domain}");
                throw new \Exception('❌ cPanel token is empty. Token creation may have failed.');
            }

            // ✅ استخدم البيانات من الداتابيز
            $this->token = $cpanelAccount->cpanel_token;
            $this->username = $cpanelAccount->cpanel_username;

            Log::info('✅ cPanel credentials retrieved from database successfully');
            Log::info("✅ Username: {$this->username}, Domain: {$this->domain}");
            Log::info('🔑 Using token: '.substr($this->token, 0, 20).'...');

            $host = '135.181.215.105';

            // محتوى الملف
            $content = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Welcome to {$this->domain}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        h1 {
            font-size: 3em;
            margin: 0;
        }
        p {
            font-size: 1.2em;
            margin-top: 1.25rem;
        }
    </style>
</head>
<body>
    <h1>🎉 Welcome to {$this->domain}</h1>
    <p>Your domain is now active and ready to use!</p>
    <p>Created on: <?php echo date('Y-m-d H:i:s'); ?></p>
</body>
</html>
HTML;

            $dir = 'public_html';

            $url = "https://{$host}:2083/execute/Fileman/upload_files";

            Log::info('📤 Uploading index.php file to cPanel...');

            $response = Http::withoutVerifying()
                ->timeout(30)
                ->withHeaders([
                    'Authorization' => "cpanel {$this->username}:{$this->token}",
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
                        'contents' => $content,
                    ],
                ]);

            if (! $response->successful()) {
                Log::error('❌ HTTP Error: '.$response->status());
                Log::error('Response body: '.$response->body());
                throw new \Exception('❌ HTTP Error: '.$response->status().' - '.$response->body());
            }

            $data = $response->json();
            Log::info('📄 cPanel upload_files Response:', $data);

            if (isset($data['errors']) && ! empty($data['errors'])) {
                Log::error('❌ File upload failed with errors:', $data['errors']);
                throw new \Exception('File upload failed: '.json_encode($data['errors']));
            }

            Log::info("✅ File uploaded successfully to {$this->domain}");

            // ✅ حدّث حالة السجل في الداتابيز (اختياري)
            tenancy()->central(function () {
                DB::table('cpanel_accounts')
                    ->where('domain', $this->domain)
                    ->update([
                        'file_created' => true,
                        'file_created_at' => now(),
                        'updated_at' => now(),
                    ]);
            });

            Log::info('✅ CreateFileJob completed successfully');

        } catch (\Exception $e) {
            Log::error('❌ CreateFileJob failed: '.$e->getMessage());
            Log::error('Stack trace: '.$e->getTraceAsString());

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('❌ CreateFileJob failed permanently after all retries');
        Log::error('Domain: '.$this->domain);
        Log::error('Username: '.$this->username);
        Log::error('Error: '.$exception->getMessage());
    }
}
