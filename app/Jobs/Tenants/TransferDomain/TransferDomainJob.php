<?php

namespace App\Jobs\Tenants\TransferDomain;

use App\Jobs\Domain\CreateloginAndTokenJob;
use App\Models\Admin\Tenants\Domain;
use App\Services\shared\NameCheap\NameCheapService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TransferDomainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private NameCheapService $namecheapService;

    public function handle(): void
    {

        $pendingTransfers = tenancy()->central(function () {
            return Domain::where('trasnfer_status', 'pending')->get();
        });

        foreach ($pendingTransfers as $domain) {
            $this->checkTransferStatus($domain);
        }
    }

    private function checkTransferStatus($domain)
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
            tenancy()->central(function () use ($domain, $status, $tenantId) {
                Log::info($tenantId.'in');
                $domain->update([
                    'trasnfer_status' => $status,
                ]);
                if ($status == 'Completed') {
                    $domain->update([
                        'trasnfer_status' => null,
                        'transfer_code' => null,
                    ]);
                    $cpanel = DB::table('cpanel_accounts')->where('tenant_id', $tenantId)->first();
                    if (! $cpanel) {
                        return Bus::chain([
                            new CreateloginAndTokenJob($domain, $tenantId),
                        ])->dispatch();
                    } else {
                        return $this->namecheapService->modifyAccount($domain, $tenantId);
                    }
                }
            });

            Log::info("Domain {$domain->domain} updated to: $status");
        } catch (\Exception $e) {
            Log::error('TransferDomainJob Exception: '.$e->getMessage());
        }
    }
}
