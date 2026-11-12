<?php

declare(strict_types=1);

namespace App\Services\Tenants\Properties;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Http\Client\Factory;
use InvalidArgumentException;

final class GoogleMapsUrlResolver
{
    private const MAP_HOSTS = ['google.com', 'www.google.com', 'maps.google.com', 'google.com.sa', 'www.google.com.sa', 'google.com.eg', 'www.google.com.eg', 'google.co.uk', 'www.google.co.uk'];

    public function __construct(private readonly Factory $http) {}

    public function resolve(string $url): string
    {
        for ($redirect = 0; $redirect < 5; $redirect++) {
            $parts = parse_url($url);
            $host = strtolower($parts['host'] ?? '');
            $path = $parts['path'] ?? '';
            $isShort = $host === 'maps.app.goo.gl' || ($host === 'goo.gl' && str_starts_with($path, '/maps/'));
            $isMap = in_array($host, self::MAP_HOSTS, true) && ($host === 'maps.google.com' || preg_match('~^/maps(?:/|$)~', $path));

            if (($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass']) || isset($parts['port']) || (! $isShort && ! $isMap)) {
                throw new InvalidArgumentException('رابط خرائط جوجل غير صالح.');
            }

            if ($isMap) {
                return $url;
            }

            $options = ['allow_redirects' => false];
            $caBundle = $this->resolveCaBundle();
            if ($caBundle !== null) {
                $options['verify'] = $caBundle;
            }

            $response = $this->http->withOptions($options)->connectTimeout(3)->timeout(5)->head($url);
            $location = $response->header('Location');
            if (! $response->redirect() || ! $location) {
                throw new InvalidArgumentException('تعذر قراءة رابط الموقع المختصر.');
            }

            $url = (string) UriResolver::resolve(new Uri($url), new Uri($location));
        }

        throw new InvalidArgumentException('تعذر قراءة رابط الموقع المختصر.');
    }

    private function resolveCaBundle(): string|bool|null
    {
        if (ini_get('curl.cainfo') || ini_get('openssl.cafile')) {
            return null;
        }

        $commonPaths = [
            'C:\\Program Files\\Git\\usr\\ssl\\certs\\ca-bundle.crt',
            'C:\\Program Files (x86)\\Git\\usr\\ssl\\certs\\ca-bundle.crt',
        ];

        foreach ($commonPaths as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return app()->isLocal() ? false : null;
    }
}
