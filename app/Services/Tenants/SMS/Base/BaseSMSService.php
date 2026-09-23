<?php

namespace App\Services\Tenants\SMS\Base;

use App\Models\Tenants\Settings\Settings;
use App\Models\Tenants\GeneralSetting\MessageTemplate\MessageTemplate;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BaseSMSService
{
    protected $settings;
    protected $client;
    protected $feedbackService;
    protected $settingsLoaded = false;
    protected $settingsAttempted = false;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Load Settings
    |--------------------------------------------------------------------------
    */
    protected function loadSettings(): void
    {
        if ($this->settingsAttempted) {
            return;
        }

        $this->settingsAttempted = true;

        if (!tenancy()->initialized) {
            return;
        }

        try {
            if (!Schema::hasTable('settings')) {
                return;
            }
        } catch (\Exception $e) {
            return;
        }

        try {
            $this->settings = Settings::first();
            $this->settingsLoaded = true;
        } catch (\Exception $e) {
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Configuration & Validation
    |--------------------------------------------------------------------------
    */
    public function isEnabled(): bool
    {
        $this->loadSettings();
        return $this->settings && $this->settings->sms_enabled;
    }

    /*
    |--------------------------------------------------------------------------
    | Configuration Check
    |--------------------------------------------------------------------------
    */
    public function isConfigured(): bool
    {
        $this->loadSettings();

        return $this->settings &&
            $this->settings->sms_api_key &&
            $this->settings->sms_api_secret &&
            $this->settings->sms_sender_id &&
            $this->settings->sms_provider === '4jawaly';
    }

    /*
    |--------------------------------------------------------------------------
    | Readiness Check
    |--------------------------------------------------------------------------
    */
    public function isReady(): bool
    {
        return $this->isEnabled() && $this->isConfigured();
    }

    /*
    |--------------------------------------------------------------------------
    | Phone Number Processing
    |--------------------------------------------------------------------------
    */
    public function isValidPhoneNumber(string $phone): bool
    {
        $phone = $this->cleanPhoneNumber($phone);

        $patterns = [
            '/^966[0-9]{9}$/',      // 966xxxxxxxxx
            '/^\+966[0-9]{9}$/',    // +966xxxxxxxxx
            '/^05[0-9]{8}$/',       // 05xxxxxxxx
            '/^5[0-9]{8}$/',        // 5xxxxxxxx
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $phone)) {
                return true;
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Phone Number Formatting
    |--------------------------------------------------------------------------
    */
    public function cleanPhoneNumber(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone);
    }

    /*
    |--------------------------------------------------------------------------
    | Format Phone Number
    |--------------------------------------------------------------------------
    */
    public function formatPhoneNumber(string $phone): string
    {
        $phone = $this->cleanPhoneNumber($phone);

        // إذا بدأ بـ 05، استبدل بـ 9665
        if (preg_match('/^05[0-9]{8}$/', $phone)) {
            return '9665' . substr($phone, 2);
        }

        // إذا بدأ بـ 5، أضف 966
        if (preg_match('/^5[0-9]{8}$/', $phone)) {
            return '966' . $phone;
        }

        // إزالة + إذا وجدت
        return ltrim($phone, '+');
    }

    /*
    |--------------------------------------------------------------------------
    | Prepare Phone Numbers
    |--------------------------------------------------------------------------
    */
    public function preparePhoneNumbers(array $phoneNumbers): array
    {
        $validNumbers = [];

        foreach ($phoneNumbers as $phone) {
            if ($this->isValidPhoneNumber($phone)) {
                $validNumbers[] = $this->formatPhoneNumber($phone);
            }
        }

        return array_unique($validNumbers);
    }

    /*
    |--------------------------------------------------------------------------
    | HTML to Text Conversion
    |--------------------------------------------------------------------------
    */
    public function convertHtmlToText(string $html): string
    {
        // تحويل <br> إلى أسطر جديدة
        $text = preg_replace('/<br\s*\/?>/i', "\n", $html);

        // تحويل </p> إلى أسطر جديدة - ✅ مُصحح
        $text = preg_replace('/<\/p>/i', "\n\n", $text);
        $text = preg_replace('/<p[^>]*>/i', '', $text);

        // معالجة الروابط - ✅ مُصحح
        $text = preg_replace('/<a[^>]*href="([^"]*)"[^>]*>([^<]*)<\/a>/i', '$2: $1', $text);

        // إزالة HTML tags
        $text = strip_tags($text);

        // تنظيف المسافات
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);

        return trim($text);
    }

    /*
    |--------------------------------------------------------------------------
    | System Variables Processing
    |--------------------------------------------------------------------------
    */
    public function processSystemVariables(string $content): string
    {
        $this->loadSettings();

        $companyName = $this->settings?->company_name ?? 'الشركة';

        $replacements = [
            '{date}'      => now()->format('Y-m-d'),
            '{time}'      => now()->format('H:i'),
            '{company}'   => $companyName,
            '{timestamp}' => now()->format('Y-m-d H:i'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /*
    |--------------------------------------------------------------------------
    | Sending SMS via 4Jawaly
    |--------------------------------------------------------------------------
    */
    public function send4JawalyMessage(string $message, array $phoneNumbers): bool
    {
        if (!$this->isReady()) {
            return false;
        }

        $validNumbers = $this->preparePhoneNumbers($phoneNumbers);
        if (empty($validNumbers)) {
            return false;
        }

        try {
            $response = $this->makeApiCall($message, $validNumbers);
            return $this->handleApiResponse($response);
        } catch (\Exception $e) {
            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Make API Call
    |--------------------------------------------------------------------------
    */
    protected function makeApiCall(string $message, array $phoneNumbers): \Psr\Http\Message\ResponseInterface
    {
        $appHash = base64_encode("{$this->settings->sms_api_key}:{$this->settings->sms_api_secret}");
        $endpoint = 'https://api-sms.4jawaly.com/api/v1/account/area/sms/send';

        $payload = [
            "messages" => [
                [
                    "text" => $message,
                    "numbers" => $phoneNumbers,
                    "sender" => $this->settings->sms_sender_id,
                ]
            ]
        ];

        return $this->client->post($endpoint, [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $appHash,
            ],
            'json' => $payload,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Handle API Response
    |--------------------------------------------------------------------------
    */
    protected function handleApiResponse(\Psr\Http\Message\ResponseInterface $response): bool
    {
        $statusCode = $response->getStatusCode();
        $body = json_decode($response->getBody(), true);

        if ($statusCode === 200) {
            if (isset($body["messages"][0]["err_text"])) {
                return false;
            }
            return true;
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Test Connection
    |--------------------------------------------------------------------------
    */
    public function testConnection(): bool
    {
        if (!$this->isConfigured()) {
            Log::warning('SMS connection test failed: Invalid configuration');
            return false;
        }

        try {
            $appHash = base64_encode("{$this->settings->sms_api_key}:{$this->settings->sms_api_secret}");
            $endpoint = 'https://api-sms.4jawaly.com/api/v1/account/area/senders';

            $response = $this->client->get($endpoint, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Basic ' . $appHash,
                ],
                'query' => [
                    'page_size' => 10,
                    'page' => 1,
                    'status' => 1,
                ],
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody(), true);

            if ($statusCode === 200 && isset($body["items"])) {
                foreach ($body["items"] as $sender) {
                    if (isset($sender["sender_name"]) && $sender["sender_name"] === $this->settings->sms_sender_id) {
                        Log::info('SMS connection test successful');
                        return true;
                    }
                }
            }

            Log::warning('SMS connection test failed: Sender ID not found');
            return false;
        } catch (\Exception $e) {
            Log::error("SMS connection test failed", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Cost Calculation
    |--------------------------------------------------------------------------
    */
    public function getMessageCost(): float
    {
        $this->loadSettings();
        return $this->settings?->sms_cost_per_message ?? 0.05;
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate Cost
    |--------------------------------------------------------------------------
    */
    public function calculateCost(int $messageCount): float
    {
        return $messageCount * $this->getMessageCost();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Settings
    |--------------------------------------------------------------------------
    */
    public function getSettings(): ?Settings
    {
        $this->loadSettings();
        return $this->settings;
    }

    /*
    |--------------------------------------------------------------------------
    | Is Valid Template
    |--------------------------------------------------------------------------
    */
    public function isValidTemplate(int $templateId): bool
    {
        $template = MessageTemplate::find($templateId);
        return $template && $template->is_active;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Template
    |--------------------------------------------------------------------------
    */
    public function getTemplate(int $templateId): ?MessageTemplate
    {
        return MessageTemplate::where('id', $templateId)
            ->where('is_active', true)
            ->first();
    }
}
