<?php

namespace App\Services\shared\APILayer;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class apiLayerService
{


    public function checkDomainNamecheap($domain)
    {
        $apiUser = 'nhjsa';      // API User
        $apiKey = 'f38b308ef2fb45dcac4145a4f216b27b';        // API Key
        $username = 'nhjsa';     // Username
        $clientIp = '154.180.133.192';    // IP المسجل في Namecheap API whitelist

        $query = http_build_query([
            'ApiUser' => $apiUser,
            'ApiKey' => $apiKey,
            'UserName' => $username,
            'Command' => 'namecheap.domains.check',
            'ClientIp' => $clientIp,
            'DomainList' => $domain
        ]);

        $url = "https://api.namecheap.com/xml.response?$query";

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        // تحويل XML response إلى array
        $xml = simplexml_load_string($response);
        $json = json_decode(json_encode($xml), true);

        // استخراج حالة الدومين
        $isAvailable = null;
        if (isset($json['CommandResponse']['DomainCheckResult'])) {
            $checkResult = $json['CommandResponse']['DomainCheckResult'];
            $isAvailable = isset($checkResult['@attributes']['Available']) && $checkResult['@attributes']['Available'] === 'true';
        }

        return response()->json([
            'domain' => $domain,
            'available' => $isAvailable,
            'raw_response' => $json
        ]);
    }
}
