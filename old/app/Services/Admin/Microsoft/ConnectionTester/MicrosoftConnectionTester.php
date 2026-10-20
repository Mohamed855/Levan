<?php

namespace App\Services\Admin\Microsoft\ConnectionTester;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class MicrosoftConnectionTester
{
    protected Client $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client;
    }

    public function validate(string $clientId, string $clientSecret, string $tenantId, string $mainEmail): array
    {
        try {
            $tokenResponse = $this->httpClient->post(
                "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token",
                [
                    'form_params' => [
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                        'scope' => 'https://graph.microsoft.com/.default',
                        'grant_type' => 'client_credentials',
                    ],
                ]
            );

            $data = json_decode($tokenResponse->getBody()->getContents(), true);
            $token = $data['access_token'] ?? null;

            if (! $token) {
                return [false, 'تعذر الحصول على رمز الوصول من Microsoft، تحقق من Client ID و Client Secret.'];
            }

            $userResponse = $this->httpClient->get(
                "https://graph.microsoft.com/v1.0/users/{$mainEmail}",
                [
                    'headers' => [
                        'Authorization' => "Bearer {$token}",
                        'Accept' => 'application/json',
                    ],
                ]
            );

            if ($userResponse->getStatusCode() !== 200) {
                return [false, 'تمت المصادقة بنجاح، لكن تعذر التحقق من البريد الإلكتروني داخل التينانت.'];
            }

            return [true, null];
        } catch (ClientException $e) {
            $status = $e->getResponse()?->getStatusCode();

            if (in_array($status, [400, 401])) {
                return [false, 'بيانات Microsoft (Client ID / Client Secret / Tenant ID) غير صحيحة أو لا تملك صلاحية الوصول.'];
            }

            if ($status === 404) {
                return [false, 'تمت المصادقة ولكن البريد الإلكتروني غير موجود في هذا التينانت.'];
            }

            return [false, 'فشل الاتصال بخدمة Microsoft Graph، يرجى التحقق من البيانات والصلاحيات.'];
        } catch (\Throwable $e) {
            return [false, 'حدث خطأ غير متوقع أثناء اختبار إعدادات Microsoft.'];
        }
    }
}
