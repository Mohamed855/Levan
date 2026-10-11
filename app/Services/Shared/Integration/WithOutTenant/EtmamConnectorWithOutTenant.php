<?php

namespace App\Services\shared\Integration\WithOutTenant;

use App\Models\Admin\Settings\Settings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class EtmamConnectorWithOutTenant
{
    protected ?Settings $settings;

    public function __construct()
    {
        $this->settings = Settings::first();
    }

    public function isConfigured(): bool
    {
        return $this->settings
            && $this->settings->etmam_enabled
            && !empty($this->settings->etmam_base_url)
            && !empty($this->settings->etmam_api_token)
            && !empty($this->settings->etmam_system_code)
            && !empty($this->settings->etmam_hmac_secret);
    }


    // POST Request
    public function post(string $endpoint, array $payload = []): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'إعدادات الاتصال مع النظام المركزي غير مكتملة.',
            ];
        }

        $url        = $this->buildUrl($endpoint);
        $payload    = $this->enrichPayload($payload);
        $body       = json_encode($payload, JSON_UNESCAPED_UNICODE);  //  تحويل البيانات إلى JSON
        $signature  = $this->sign($body); //  التوقيع على الـ raw body

        try {
            $response = Http::withHeaders($this->buildHeaders($signature))
                ->timeout(15)
                ->retry(1, 300)
                ->withBody($body, 'application/json') //  إرسال raw body
                ->post($url);

            return $this->handleResponse($response, 'POST', $endpoint);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'POST', $endpoint);
        }
    }




    // اضافة البيانات المطلوبة في كل رابط
    protected function enrichPayload(array $payload): array
    {
        $payload['system_code'] = $this->settings->etmam_system_code;

        return $payload;
    }





    /*
    |============================================================================
    |============================================================================
    |                         Private Methods
    |============================================================================
    |============================================================================
    */
    protected function buildUrl(string $endpoint, array $query = []): string
    {
        $endpoint = ltrim($endpoint, '/'); // فقط إزالة الـ / من البداية

        $url = rtrim($this->settings->etmam_base_url, '/') . '/' . $endpoint;

        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }


    protected function sign(string $body): string
    {
        return hash_hmac('sha256', $body, $this->settings->etmam_hmac_secret);
    }


    protected function buildHeaders(string $signature): array
    {
        return [
            'X-API-Key'         => $this->settings->etmam_api_token,
            'X-Signature'       => $signature,
            'Accept'            => 'application/json',
            'Content-Type'      => 'application/json',
        ];
    }


    protected function handleResponse($response): array
    {
        $statusCode = $response->status();
        $contentType = $response->header('Content-Type');

        if (str_contains($contentType, 'application/json')) {
            $data = $response->json();
        } else {
            $data = [
                'message' => 'الاستجابة من الخادم ليست بصيغة JSON',
                'raw_response' => $response->body()
            ];
        }

        if ($response->successful()) {
            return [
                'success' => true,
                'data' => $data,
                'status' => $statusCode,
                'message' => $data['message'] ?? 'تم الإرسال بنجاح',
            ];
        }

        return [
            'success' => false,
            'message' => $data['message'] ?? $data['error'] ?? 'فشل الاتصال بالنظام المركزي.',
            'status' => $statusCode,
            'errors' => $data['errors'] ?? [],
        ];
    }


    protected function handleException(\Throwable $e, string $method, string $endpoint): array
    {
        return [
            'success' => false,
            'message' => 'حدث خطأ أثناء الاتصال بالنظام المركزي ',
        ];
    }
}
