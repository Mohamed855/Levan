<?php

namespace App\Helpers\Tenants\GeneralSms;

use App\Models\Tenants\Settings\Settings;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class General
{
    /**
     * اختبار صحة بيانات مزود 4Jawaly.
     *
     * @param string $apiKey
     * @param string $apiSecret
     * @return bool
     */
    public static function test4JawalyCredentials($apiKey, $apiSecret, $senderId)
    {
        $appHash = base64_encode("{$apiKey}:{$apiSecret}");
        $baseUrl = "https://api-sms.4jawaly.com/api/v1/";

        $query = [
            "page_size" => 50,
            "page" => 1,
            "status" => 1,
            "sender_name" => '',
            "is_ad" => '',
            "return_collection" => 1
        ];

        try {

            $guzzleClient = new Client();
            $response = $guzzleClient->get($baseUrl . 'account/area/senders', [
                'query' => $query,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic ' . $appHash,
                ],
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody(), true);

            if ($statusCode === 200 && is_array($body)) {
                if (isset($body["items"]) && is_array($body["items"])) {
                    $found = false;
                    foreach ($body["items"] as $sender) {
                        if (isset($sender["sender_name"]) && $sender["sender_name"] === $senderId) {
                            $found = true;
                            break;
                        }
                    }

                    if ($found) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * إرسال رسالة نصية (SMS) باستخدام إعدادات من قاعدة البيانات.
     *
     * @param string $message محتوى الرسالة
     * @param string $mobileNumber رقم الهاتف
     * @return bool نتيجة الإرسال
     */
    public static function sendSMS($message, $mobileNumber)
    {

        $settings = Settings::first();
        if (!$settings) {
            return false;
        }

        $apiKey = $settings->sms_api_key ?? null;
        $apiSecret = $settings->sms_api_secret ?? null;
        $apiEndpoint = 'https://api-sms.4jawaly.com/api/v1/account/area/sms/send';
        $senderId = $settings->sms_sender_id ?? '4jawaly';
        $smsEnabled = $settings->sms_enabled ?? true;

        if (!$smsEnabled) {
            return false;
        }

        if (!$apiKey || !$apiSecret || !$apiEndpoint || !$senderId) {
            return false;
        }

        $appHash = base64_encode("{$apiKey}:{$apiSecret}");

        $messages = [
            "messages" => [
                [
                    "text" => $message,
                    "numbers" => [ltrim($mobileNumber, '+')],
                    "sender" => $senderId,
                ],
            ],
        ];

        $headers = [
            "Accept: application/json",
            "Content-Type: application/json",
            "Authorization: Basic {$appHash}",
        ];

        try {
            $curl = curl_init($apiEndpoint);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($messages));
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($curl);
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            $responseJson = json_decode($response, true);

            // التحقق من الرد
            if ($statusCode == 200) {
                if (isset($responseJson["messages"][0]["err_text"])) {
                    return false;
                } else {
                    return true;
                }
            } elseif ($statusCode == 400) {
                return false;
            } elseif ($statusCode == 422) {
                return false;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }
}
