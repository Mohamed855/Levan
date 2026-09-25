<?php

namespace App\Services\shared\Integration;

use App\Models\Admin\Settings\Settings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class EtmamConnector
{
    protected ?Settings $settings;
    protected ?string   $tenantId;

    public function __construct(?string $tenantId = null)
    {
        $this->settings = Settings::first();
        $this->tenantId = $tenantId ?? $this->resolveCurrentTenantId();
    }

    public function isConfigured(): bool
    {
        $this->settings = Settings::first();
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


    // POST With Files
    public function postWithFiles(string $endpoint, array $data = [], array $files = []): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'إعدادات الاتصال مع النظام المركزي غير مكتملة.',
            ];
        }

        $url    = $this->buildUrl($endpoint);
        $data   = $this->enrichPayload($data);

        try {
            //
            $headers = [
                'X-API-Key'     => $this->settings->etmam_api_token,
                'X-Tenant-ID'       => $this->tenantId,
                'X-Tenant-User-ID'  => auth()->id(),
                'Accept'        => 'application/json',
            ];

            $multipart = [];

            foreach ($data as $key => $value) {
                $multipart[] = [
                    'name'     => $key,
                    'contents' => is_array($value) ? json_encode($value) : (string) $value,
                ];
            }

            if (!empty($files)) {
                //
                foreach ($files as $index => $file) {

                    if ($file instanceof UploadedFile && $file->isValid()) {
                        //
                        $multipart[] = [
                            'name'     => "attachments[{$index}]",
                            'contents' => fopen($file->getRealPath(), 'r'),
                            'filename' => $file->getClientOriginalName(),
                        ];
                    }
                }
            }

            $response = Http::withHeaders($headers)->timeout(30)->retry(1, 500)->asMultipart()->post($url, $multipart);

            return $this->handleResponse($response, 'POST', $endpoint, true);
            //
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->handleException($e, 'POST', $endpoint);
        }
    }


    // GET Request
    public function get(string $endpoint): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'إعدادات الاتصال مع النظام المركزي غير مكتملة.',
            ];
        }

        $url = $this->buildUrl($endpoint);

        $signature = $this->sign('');

        try {
            $response = Http::withHeaders($this->buildHeaders($signature))
                ->timeout(15)
                ->retry(1, 300)
                ->get($url);

            return $this->handleResponse($response, 'GET', $endpoint);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'GET', $endpoint);
        }
    }

    /**
     * PUT Request
     */
    public function put(string $endpoint, array $payload = []): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'إعدادات الاتصال مع النظام المركزي غير مكتملة.',
            ];
        }

        $url = $this->buildUrl($endpoint);
        $payload = $this->enrichPayload($payload);

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $signature = $this->sign($body);

        try {
            $response = Http::withHeaders($this->buildHeaders($signature))
                ->timeout(15)
                ->retry(1, 300)
                ->withBody($body, 'application/json')
                ->put($url);

            return $this->handleResponse($response, 'PUT', $endpoint);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'PUT', $endpoint);
        }
    }

    /**
     * DELETE Request
     */
    public function delete(string $endpoint, array $query = []): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'إعدادات الاتصال مع النظام المركزي غير مكتملة.',
            ];
        }

        $url = $this->buildUrl($endpoint, $query);

        //  DELETE with query = empty body
        $signature = $this->sign('');

        try {
            $response = Http::withHeaders($this->buildHeaders($signature))
                ->timeout(15)
                ->retry(1, 300)
                ->delete($url);

            return $this->handleResponse($response, 'DELETE', $endpoint);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'DELETE', $endpoint);
        }
    }

    /**
     * PATCH Request
     */
    public function patch(string $endpoint, array $payload = []): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'إعدادات الاتصال مع النظام المركزي غير مكتملة.',
            ];
        }

        $url = $this->buildUrl($endpoint);
        $payload = $this->enrichPayload($payload);

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $signature = $this->sign($body);

        try {
            $response = Http::withHeaders($this->buildHeaders($signature))
                ->timeout(15)
                ->retry(1, 300)
                ->withBody($body, 'application/json')
                ->patch($url);

            return $this->handleResponse($response, 'PATCH', $endpoint);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'PATCH', $endpoint);
        }
    }

    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }



    // اضافة البيانات المطلوبة في كل رابط
    protected function enrichPayload(array $payload): array
    {
        $payload['system_code'] = $this->settings->etmam_system_code;

        if ($this->tenantId) {
            $payload['tenant_id'] = $this->tenantId;
        }

        // إضافة المستخدم الحالي
        if (auth()->check()) {
            $payload['tenant_user_id'] = auth()->id();
        }

        return $payload;
    }





    /*
    |============================================================================
    |============================================================================
    |                         Private Methods
    |============================================================================
    |============================================================================
    */
    protected function resolveCurrentTenantId(): ?string
    {
        if (function_exists('tenant') && tenant()) {
            return tenant('id');
        }
        return null;
    }


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
            'X-Tenant-ID'       => $this->tenantId,
            'X-Tenant-User-ID'  => auth()->id(),
            'Accept'            => 'application/json',
            'Content-Type'      => 'application/json',
        ];
    }


    protected function handleResponse($response, string $method, string $endpoint, bool $hasFiles = false): array
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
            'message' => 'حدث خطأ أثناء الاتصال بالنظام المركزي'
        ];
    }
}
