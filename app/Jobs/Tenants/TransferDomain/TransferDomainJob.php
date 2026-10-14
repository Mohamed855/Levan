<?php

namespace App\Jobs\Tenants\TransferDomain;

use App\Jobs\Domain\CreateloginAndTokenJob;
use App\Services\shared\NameCheap\NameCheapService;
use App\Services\Tenants\Shared\CentralDomainGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TransferDomainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(CentralDomainGateway $domainGateway, NameCheapService $namecheapService): void
    {
        $pendingTransfers = $domainGateway->pendingTransfers();

        foreach ($pendingTransfers as $domain) {
            $this->checkTransferStatus($domain, $domainGateway, $namecheapService);
        }
    }

    private function checkTransferStatus($domain, CentralDomainGateway $domainGateway, NameCheapService $namecheapService): void
    {
        $transferId = $domain->transfer_code;

        $apiUser = env('NAMECHEAP_API_USER');
        $apiKey = env('NAMECHEAP_API_KEY');
        $clientIp = '154.180.114.3';

        $baseUrl = env('NAMECHEAP_URL');

        $params = [
            'ApiUser' => $apiUser,
            'ApiKey' => $apiKey,
            'UserName' => $apiUser,
            'ClientIp' => $clientIp,
            'Command' => 'namecheap.domains.transfer.getStatus',
            'TransferID' => $transferId,
        ];

        try {
            $response = Http::get($baseUrl, $params);

            if ($response->failed()) {
                Log::error('Namecheap API Failed: '.$response->body());

                return;
            }

            $xml = simplexml_load_string($response->body());

            $status = (string) ($xml->CommandResponse->DomainTransferStatusResult['Status'] ?? 'unknown');
            $tenantId = $domain->tenant_id;
            Log::info($tenantId);
            $domainGateway->updateTransferStatus($domain, $status);
            if ($status === 'Completed') {
                $cpanel = $domainGateway->cpanelForTenant((string) $tenantId);
                if (! $cpanel) {
                    Bus::chain([new CreateloginAndTokenJob($domain, $tenantId)])->dispatch();
                } else {
                    $namecheapService->modifyAccount($domain, $tenantId);
                }
            }

            Log::info("Domain {$domain->domain} updated to: $status");
        } catch (\Exception $e) {
            Log::error('TransferDomainJob Exception: '.$e->getMessage());
        }
    }
}
