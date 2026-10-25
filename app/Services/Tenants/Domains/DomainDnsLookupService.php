<?php

declare(strict_types=1);

namespace App\Services\Tenants\Domains;

final class DomainDnsLookupService
{
    public function inspect(string $domain): array
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('/^(https?:\/\/)?(www\.)?/', '', $domain) ?? $domain;
        $domain = rtrim($domain, '/');
        $parts = explode('.', $domain);
        $isSubdomain = count($parts) > 2;
        $baseDomain = $isSubdomain ? $parts[count($parts) - 2].'.'.$parts[count($parts) - 1] : $domain;

        $aRecords = collect(@dns_get_record($domain, DNS_A) ?: [])
            ->pluck('ip')
            ->filter()
            ->values()
            ->all();
        $nameServers = collect(@dns_get_record($isSubdomain ? $baseDomain : $domain, DNS_NS) ?: [])
            ->pluck('target')
            ->filter()
            ->values()
            ->all();

        $serverIp = (string) config('services.domains.server_ip');
        $serverNameServers = (array) config('services.domains.name_servers', []);
        $matchingNameServers = collect($nameServers)
            ->filter(fn (string $nameServer): bool => collect($serverNameServers)->contains(
                fn (string $serverNameServer): bool => strcasecmp(rtrim($nameServer, '.'), rtrim($serverNameServer, '.')) === 0,
            ))
            ->values()
            ->all();
        $aRecordMatch = $serverIp !== '' && in_array($serverIp, $aRecords, true);
        $nameServerMatch = $matchingNameServers !== [];
        $isPointing = $aRecordMatch || $nameServerMatch;
        $status = $aRecordMatch && $nameServerMatch ? 'complete' : ($isPointing ? 'partial' : 'not_pointing');
        $subject = $isSubdomain ? 'النطاق الفرعي' : 'النطاق';
        $message = match ($status) {
            'complete' => "{$subject} متوجه بشكل كامل إلى خوادمنا.",
            'partial' => "{$subject} متوجه جزئياً إلى خوادمنا.",
            default => "{$subject} غير متوجه إلى خوادمنا.",
        };

        return [
            'dns_info' => [
                'name_servers' => $nameServers,
                'a_records' => $aRecords,
                'domain_type' => $isSubdomain ? 'subdomain' : 'domain',
                'checked_domain' => $domain,
                'base_domain' => $baseDomain,
            ],
            'pointing_status' => [
                'is_pointing' => $isPointing,
                'a_record_match' => $aRecordMatch,
                'ns_match' => $nameServerMatch,
                'matching_ns' => $matchingNameServers,
                'server_ip' => $serverIp,
                'server_ns' => $serverNameServers,
                'status' => $status,
                'message' => $message,
                'domain_type' => $isSubdomain ? 'subdomain' : 'domain',
            ],
        ];
    }
}
