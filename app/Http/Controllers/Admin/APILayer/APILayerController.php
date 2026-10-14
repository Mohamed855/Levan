<?php

namespace App\Http\Controllers\Admin\APILayer;

use App\Http\Controllers\Controller;
use App\Services\shared\APILayer\apiLayerService;
use App\Services\Tenants\Shared\CentralDomainGateway;

class APILayerController extends Controller
{
    protected $apiLayerService;

    public function __construct(apiLayerService $apilayer, private readonly CentralDomainGateway $domains)
    {
        $this->apiLayerService = $apilayer;
        $this->middleware('can:domain.view');
    }

    public function apiLayerFunc($domain)
    {
        return $this->apiLayerService->checkDomainNamecheap($domain);
    }

    public function checkExistTransferDomain($domain)
    {

        $transfered = $this->domains->findTransferred($domain);

        if ($transfered) {
            return response()->json([
                'status' => 'exists',
                'message' => 'هذا الدومين منقول بالفعل',
            ], 200);
        }

        return $this->apiLayerService->checkDomainNamecheap($domain);
    }

    public function apiLayer2($domain)
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('/^(https?:\/\/)?(www\.)?/', '', $domain);
        $domain = rtrim($domain, '/');

        $domainParts = explode('.', $domain);
        $isSubdomain = count($domainParts) > 2;

        $baseDomain = $domain;
        if ($isSubdomain) {
            $baseDomain = $domainParts[count($domainParts) - 2].'.'.$domainParts[count($domainParts) - 1];
        }

        $dnsInfo = [
            'name_servers' => [],
            'a_records' => [],
            'domain_type' => $isSubdomain ? 'subdomain' : 'domain',
            'checked_domain' => $domain,
            'base_domain' => $baseDomain,
        ];

        // الحصول على A Records
        try {
            $records = @dns_get_record($domain, DNS_A);
            if ($records && is_array($records)) {
                foreach ($records as $record) {
                    if (isset($record['ip'])) {
                        $dnsInfo['a_records'][] = $record['ip'];
                    }
                }
            }
        } catch (\Exception $e) {
        }

        // الحصول على Name Servers
        $nsCheckDomain = $isSubdomain ? $baseDomain : $domain;
        try {
            $nsRecords = @dns_get_record($nsCheckDomain, DNS_NS);
            if ($nsRecords && is_array($nsRecords)) {
                foreach ($nsRecords as $record) {
                    if (isset($record['target'])) {
                        $dnsInfo['name_servers'][] = $record['target'];
                    }
                }
            }
        } catch (\Exception $e) {
        }

        // التحقق من توجيه الدومين لسيرفرنا
        $serverIp = env('SERVER_IP', '135.181.215.105');
        $serverNameServers = [
            'ns1.levan.com',
            'ns2.levan.com',
        ];

        // التحقق من A Record
        $isPointingToServer = false;
        if (! empty($dnsInfo['a_records'])) {
            foreach ($dnsInfo['a_records'] as $ip) {
                if ($ip === $serverIp) {
                    $isPointingToServer = true;
                    break;
                }
            }
        }

        // التحقق من Name Servers
        $nsPointingToServer = false;
        $matchingNameServers = [];
        if (! empty($dnsInfo['name_servers'])) {
            foreach ($dnsInfo['name_servers'] as $ns) {
                foreach ($serverNameServers as $serverNs) {
                    $cleanNs = rtrim($ns, '.');
                    $cleanServerNs = rtrim($serverNs, '.');
                    if (stripos($cleanNs, $cleanServerNs) !== false || stripos($cleanServerNs, $cleanNs) !== false) {
                        $nsPointingToServer = true;
                        $matchingNameServers[] = $ns;
                    }
                }
            }
        }

        $pointingStatus = [
            'is_pointing' => $isPointingToServer || $nsPointingToServer,
            'a_record_match' => $isPointingToServer,
            'ns_match' => $nsPointingToServer,
            'matching_ns' => $matchingNameServers,
            'server_ip' => $serverIp,
            'server_ns' => $serverNameServers,
            'message' => '',
            'domain_type' => $isSubdomain ? 'subdomain' : 'domain',
        ];

        // رسالة الحالة
        if ($isSubdomain) {
            if ($isPointingToServer && $nsPointingToServer) {
                $pointingStatus['message'] = 'الصب دومين متوجه بشكل كامل إلى سيرفرنا';
                $pointingStatus['status'] = 'complete';
            } elseif ($isPointingToServer || $nsPointingToServer) {
                $pointingStatus['message'] = 'الصب دومين متوجه جزئياً إلى سيرفرنا';
                $pointingStatus['status'] = 'partial';
            } else {
                $pointingStatus['message'] = 'الصب دومين غير متوجه إلى سيرفرنا';
                $pointingStatus['status'] = 'not_pointing';
            }
        } else {
            if ($isPointingToServer && $nsPointingToServer) {
                $pointingStatus['message'] = 'الدومين متوجه بشكل كامل إلى سيرفرنا';
                $pointingStatus['status'] = 'complete';
            } elseif ($isPointingToServer || $nsPointingToServer) {
                $pointingStatus['message'] = 'الدومين متوجه جزئياً إلى سيرفرنا';
                $pointingStatus['status'] = 'partial';
            } else {
                $pointingStatus['message'] = 'الدومين غير متوجه إلى سيرفرنا';
                $pointingStatus['status'] = 'not_pointing';
            }
        }

        return response()->json([
            'dns_info' => $dnsInfo,
            'pointing_status' => $pointingStatus,
        ]);
    }

    public function checkSubDomain($subdomain)
    {
        if (checkdnsrr($subdomain, 'A') || checkdnsrr($subdomain, 'CNAME')) {
            return response()->json([
                'result' => 'registered',
            ]);
        } else {
            return response()->json([
                'result' => 'available',
            ]);
        }
    }
}
