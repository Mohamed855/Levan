<?php

namespace App\Services\Admin\Microsoft\Graph;

use App\Services\Admin\Microsoft\Settings\MicrosoftSettingsService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MicrosoftGraphService
{
    protected string $clientId;

    protected string $clientSecret;

    protected string $tenantId;

    protected Client $httpClient;

    protected string $graphBaseUrl = 'https://graph.microsoft.com/v1.0';

    protected string $tokenUrlTemplate = 'https://login.microsoftonline.com/%s/oauth2/v2.0/token';

    public function __construct(MicrosoftSettingsService $settingsService)
    {
        $config = $settingsService->get();

        $this->clientId = $config['client_id'];
        $this->clientSecret = $config['client_secret'];
        $this->tenantId = $config['tenant_id'];

        $this->httpClient = new Client;
    }

    public function getAccessToken(): ?string
    {
        return Cache::remember('microsoft_graph_access_token', 55 * 60, function () {
            try {
                $response = $this->httpClient->post(
                    sprintf($this->tokenUrlTemplate, $this->tenantId),
                    [
                        'form_params' => [
                            'client_id' => $this->clientId,
                            'client_secret' => $this->clientSecret,
                            'scope' => 'https://graph.microsoft.com/.default',
                            'grant_type' => 'client_credentials',
                        ],
                    ]
                );

                $data = json_decode($response->getBody()->getContents(), true);

                return $data['access_token'] ?? null;
            } catch (\Throwable $e) {
                Log::error('MicrosoftGraphService::getAccessToken error: '.$e->getMessage());

                return null;
            }
        });
    }

    public function request(string $method, string $uri, array $options = [])
    {
        $token = $this->getAccessToken();

        if (! $token) {
            throw new \RuntimeException('Cannot obtain Microsoft Graph access token');
        }

        $url = $this->graphBaseUrl.'/'.ltrim($uri, '/');

        $options['headers'] = array_merge(
            [
                'Authorization' => "Bearer {$token}",
                'Accept' => 'application/json',
            ],
            $options['headers'] ?? []
        );

        try {
            $response = $this->httpClient->request($method, $url, $options);

            $content = $response->getBody()->getContents();

            return json_decode($content, true) ?: $content;
        } catch (\Throwable $e) {
            Log::error("MicrosoftGraphService::request error [{$method} {$uri}]: ".$e->getMessage());
            throw $e;
        }
    }
}
