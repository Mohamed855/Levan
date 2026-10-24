<?php

declare(strict_types=1);

namespace App\Services\Domains;

use App\Contracts\Domains\DomainAvailabilityProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class NamecheapAvailabilityProvider implements DomainAvailabilityProvider
{
    public function check(array $domains): array
    {
        if ($domains === [] || count($domains) > 10) {
            throw new RuntimeException('يمكن فحص ما بين نطاق واحد وعشرة نطاقات في الطلب.');
        }
        $config = config('services.namecheap');
        foreach (['api_user', 'api_key', 'client_ip', 'base_url'] as $key) {
            if (! filled($config[$key] ?? null)) {
                throw new RuntimeException('إعدادات فحص النطاقات غير مكتملة. تواصل مع إدارة المنصة.');
            }
        }

        $response = Http::timeout((int) ($config['timeout'] ?? 15))->get((string) $config['base_url'], [
            'ApiUser' => $config['api_user'], 'ApiKey' => $config['api_key'], 'UserName' => $config['api_user'],
            'ClientIp' => $config['client_ip'], 'Command' => 'namecheap.domains.check', 'DomainList' => implode(',', $domains),
        ]);
        if (! $response->successful()) {
            throw new RuntimeException('تعذر الاتصال بمزود فحص النطاقات.');
        }
        $xml = @simplexml_load_string($response->body());
        if ($xml === false || (string) $xml['Status'] !== 'OK') {
            throw new RuntimeException('لم يقبل مزود النطاقات طلب الفحص.');
        }

        $results = [];
        foreach ($xml->CommandResponse->DomainCheckResult as $item) {
            $domain = strtolower((string) $item['Domain']);
            $results[$domain] = [
                'domain' => $domain, 'available' => (string) $item['Available'] === 'true',
                'premium' => (string) $item['IsPremiumName'] === 'true',
            ];
        }
        return $results;
    }
}
