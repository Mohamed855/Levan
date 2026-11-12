<?php

namespace App\Services\Shared\Cpanel;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CpanelService
{
    protected ?Client $client = null;

    protected ?string $cpanelUser = null;

    protected ?string $cpanelHost = null;

    protected ?string $cpanelToken = null;

    public function __construct()
    {
        $this->cpanelUser = config('cpanel.user');
        $this->cpanelHost = config('cpanel.shared_ip');
        $this->cpanelToken = config('cpanel.api_token');
    }

    protected function getClient(): Client
    {
        if ($this->client === null) {
            if (empty($this->cpanelUser) || empty($this->cpanelHost) || empty($this->cpanelToken)) {
                $missing = [];
                if (empty($this->cpanelUser)) {
                    $missing[] = 'CPANEL_USER';
                }
                if (empty($this->cpanelHost)) {
                    $missing[] = 'CPANEL_SHARED_IP';
                }
                if (empty($this->cpanelToken)) {
                    $missing[] = 'CPANEL_API_TOKEN';
                }

                Log::error(' Missing cPanel credentials', $missing);
                throw new Exception(
                    'Missing cPanel credentials in .env: '.implode(', ', $missing)
                );
            }

            $this->client = new Client([
                'base_uri' => "https://{$this->cpanelHost}:2083/execute/",
                'verify' => config('cpanel.ssl_verify', false),
                'timeout' => 30,
                'connect_timeout' => 10,
                'headers' => [
                    'Authorization' => "cpanel {$this->cpanelUser}:{$this->cpanelToken}",
                    'Accept' => 'application/json',
                    'User-Agent' => 'Laravel-cPanel-Client/1.0',
                ],
            ]);
        }

        return $this->client;
    }

    public function createDatabase(string $tenantName, string $username): array
    {
        if (empty($this->cpanelUser) || empty($this->cpanelHost) || empty($this->cpanelToken)) {
            if (app()->environment(['local', 'testing'])) {
                $databaseName = $tenantName;
                DB::statement("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

                return [
                    'host' => env('DB_HOST', '127.0.0.1'),
                    'database' => $databaseName,
                    'username' => env('DB_USERNAME', 'admin'),
                    'password' => env('DB_PASSWORD', 'admin'),
                ];
            }
        }

        $databaseName = "{$this->cpanelUser}_{$tenantName}";
        $userName = "{$this->cpanelUser}_{$username}";
        $userPassword = $this->generateSecurePassword();

        try {
            $this->createDatabaseOnly($databaseName);
            $this->createUser($userName, $userPassword);
            $this->setPrivileges($databaseName, $userName);

            return [
                'host' => $this->cpanelHost,
                'database' => $databaseName,
                'username' => $userName,
                'password' => $userPassword,
            ];
        } catch (GuzzleException $e) {
            Log::error('Connection error while provisioning cPanel DB', [
                'tenant' => $tenantName,
                'user' => $username,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Connection error: '.$e->getMessage(), 0, $e);
        } catch (\Throwable $e) {
            Log::error('Provisioning failed', [
                'tenant' => $tenantName,
                'user' => $username,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Provisioning failed: '.$e->getMessage(), 0, $e);
        }
    }

    public function deleteDatabase(string $databaseName, ?string $userName = null): bool
    {
        $centralDb = config('database.connections.mysql.database');
        $cleanDbName = str_replace(['`', ';', ' '], '', trim($databaseName));

        if (empty($cleanDbName) || $cleanDbName === $centralDb) {
            throw new \InvalidArgumentException("Cannot drop database '{$cleanDbName}' - invalid or central database protected.");
        }

        if (empty($this->cpanelUser) || empty($this->cpanelHost) || empty($this->cpanelToken)) {
            if (app()->environment(['local', 'testing'])) {
                DB::statement("DROP DATABASE IF EXISTS `{$cleanDbName}`");

                return true;
            }
        }

        try {
            $response = $this->getClient()->get('Mysql/delete_database', [
                'query' => ['name' => $cleanDbName],
            ]);
            $database = json_decode((string) $response->getBody(), true);

            if (empty($database['status']) || $database['status'] == 0) {
                Log::error("Failed to delete cPanel database {$cleanDbName}", $database ?? []);
                throw new \RuntimeException('Failed to delete database: '.($database['errors'][0] ?? 'Unknown error'));
            }

            if ($userName) {
                $cleanUserName = str_replace(['`', ';', ' '], '', trim($userName));
                $userResponse = $this->getClient()->get('Mysql/delete_user', [
                    'query' => ['name' => $cleanUserName],
                ]);
                $userResult = json_decode((string) $userResponse->getBody(), true);
                if (empty($userResult['status']) || $userResult['status'] == 0) {
                    Log::warning("Failed to delete cPanel user {$cleanUserName}", $userResult ?? []);
                }
            }

            return true;
        } catch (GuzzleException $e) {
            Log::error("Connection error while deleting cPanel database {$cleanDbName}: ".$e->getMessage());
            throw new \RuntimeException('Connection error: '.$e->getMessage(), 0, $e);
        }
    }

    /*
    |============================================================================
    |============================================================================
    |                          Private methods
    |============================================================================
    |============================================================================
    */
    private function generateSecurePassword(): string
    {
        $password = Str::random(16).'1!Aa';

        return $password;
    }

    private function createDatabaseOnly(string $databaseName): array
    {
        $response = $this->getClient()->get('Mysql/create_database', [
            'query' => ['name' => $databaseName],
        ]);
        $database = json_decode($response->getBody(), true);

        if (empty($database['status']) || $database['status'] == 0) {
            Log::error(' Failed to create database', $database);
            throw new Exception('Failed to create database: '.($database['errors'][0] ?? 'Unknown error'));
        }

        return $database;
    }

    protected function createUser(string $userName, string $userPassword): void
    {
        $response = $this->getClient()->get('Mysql/create_user', [
            'query' => [
                'name' => $userName,
                'password' => $userPassword,
            ],
        ]);

        $user = json_decode($response->getBody(), true);

        if (empty($user['status']) || $user['status'] == 0) {
            Log::error(' Failed to create user', $user);
            throw new Exception('Failed to create user: '.($user['errors'][0] ?? 'Unknown error'));
        }
    }

    private function setPrivileges(string $databaseName, string $userName): array
    {
        $response = $this->getClient()->get('Mysql/set_privileges_on_database', [
            'query' => [
                'user' => $userName,
                'database' => $databaseName,
                'privileges' => 'ALL PRIVILEGES',
            ],
        ]);

        $privileges = json_decode($response->getBody(), true);

        if (empty($privileges['status']) || $privileges['status'] == 0) {
            Log::error(' Failed to set privileges', $privileges);
            throw new Exception('Failed to set privileges: '.($privileges['errors'][0] ?? 'Unknown error'));
        }

        return $privileges;
    }
}
