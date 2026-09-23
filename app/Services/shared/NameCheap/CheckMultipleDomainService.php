<?php

namespace App\Services\shared\NameCheap;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CheckMultipleDomainService
{
    private $apiUser;
    private $apiKey;
    private $username;
    private $clientIp;
    private $baseUrl;
    private $cacheEnabled = true;

    public function __construct()
    {
        $this->apiUser = 'nhjsa';
        $this->apiKey = 'f38b308ef2fb45dcac4145a4f216b27b';
        $this->username = 'nhjsa';
        $this->clientIp = '154.180.133.192';
        $this->baseUrl = 'https://api.namecheap.com/xml.response';
        
        // التحقق من إمكانية استخدام Cache
        $this->checkCacheAvailability();
    }

    /**
     * التحقق من إمكانية استخدام Cache
     */
    private function checkCacheAvailability()
    {
        try {
            // محاولة إنشاء المجلدات إذا لم تكن موجودة
            $cachePath = storage_path('framework/cache/data');
            
            if (!is_dir($cachePath)) {
                // محاولة إنشاء المجلد
                if (!@mkdir($cachePath, 0755, true)) {
                    Log::warning('Unable to create cache directory: ' . $cachePath);
                    $this->cacheEnabled = false;
                    return;
                }
            }
            
            // التحقق من إمكانية الكتابة
            if (!is_writable($cachePath)) {
                Log::warning('Cache directory is not writable: ' . $cachePath);
                $this->cacheEnabled = false;
                return;
            }
            
            // اختبار الكتابة
            $testKey = 'cache_test_' . time();
            Cache::put($testKey, 'test', 1);
            Cache::forget($testKey);
            
        } catch (\Exception $e) {
            Log::warning('Cache is not available: ' . $e->getMessage());
            $this->cacheEnabled = false;
        }
    }

    /**
     * حفظ في Cache بشكل آمن
     */
    private function safeCache($key, $value, $ttl)
    {
        if (!$this->cacheEnabled) {
            return false;
        }
        
        try {
            Cache::put($key, $value, $ttl);
            return true;
        } catch (\Exception $e) {
            Log::warning('Cache put failed: ' . $e->getMessage());
            $this->cacheEnabled = false;
            return false;
        }
    }

    /**
     * جلب من Cache بشكل آمن
     */
    private function safeGetCache($key)
    {
        if (!$this->cacheEnabled) {
            return null;
        }
        
        try {
            return Cache::get($key);
        } catch (\Exception $e) {
            Log::warning('Cache get failed: ' . $e->getMessage());
            $this->cacheEnabled = false;
            return null;
        }
    }

    /**
     * التحقق من وجود Cache بشكل آمن
     */
    private function safeCacheHas($key)
    {
        if (!$this->cacheEnabled) {
            return false;
        }
        
        try {
            return Cache::has($key);
        } catch (\Exception $e) {
            Log::warning('Cache has check failed: ' . $e->getMessage());
            $this->cacheEnabled = false;
            return false;
        }
    }

    /**
     * التحقق من توفر دومين واحد
     * 
     * @param string $domain
     * @return array
     */
    public function checkSingleDomain(string $domain): array
    {
        $cacheKey = 'domain_check_' . md5($domain);
        
        // محاولة جلب من Cache
        if ($this->safeCacheHas($cacheKey)) {
            $cached = $this->safeGetCache($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $query = http_build_query([
            'ApiUser' => $this->apiUser,
            'ApiKey' => $this->apiKey,
            'UserName' => $this->username,
            'Command' => 'namecheap.domains.check',
            'ClientIp' => $this->clientIp,
            'DomainList' => $domain
        ]);

        $url = $this->baseUrl . '?' . $query;

        try {
            $response = $this->makeApiRequest($url);
            
            if (!$response) {
                throw new \Exception('فشل الاتصال بالخادم');
            }

            $xml = simplexml_load_string($response);
            
            if ($xml === false) {
                throw new \Exception('خطأ في معالجة البيانات');
            }

            $json = json_decode(json_encode($xml), true);

            if (isset($json['Errors']['Error'])) {
                $error = is_array($json['Errors']['Error']) 
                    ? $json['Errors']['Error'][0] 
                    : $json['Errors']['Error'];
                
                Log::error('Namecheap API Error: ' . ($error['#text'] ?? 'Unknown error'));
                
                return [
                    'success' => false,
                    'domain' => $domain,
                    'available' => null,
                    'error' => 'خطأ من الخادم: ' . ($error['#text'] ?? 'غير معروف')
                ];
            }

            $isAvailable = null;
            if (isset($json['CommandResponse']['DomainCheckResult'])) {
                $checkResult = $json['CommandResponse']['DomainCheckResult'];
                $isAvailable = isset($checkResult['@attributes']['Available']) && 
                              $checkResult['@attributes']['Available'] === 'true';
            }

            $result = [
                'success' => true,
                'domain' => $domain,
                'available' => $isAvailable
            ];

            // محاولة حفظ في Cache
            $this->safeCache($cacheKey, $result, now()->addHour());

            return $result;

        } catch (\Exception $e) {
            Log::error('Namecheap Domain Check Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'domain' => $domain,
                'available' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * التحقق من عدة دومينات (حتى 10 دومينات في طلب واحد)
     * 
     * @param array $domains
     * @return array
     */
    public function checkMultipleDomains(array $domains): array
    {
        if (count($domains) > 10) {
            $domains = array_slice($domains, 0, 10);
        }

        $domainList = implode(',', $domains);
        
        $query = http_build_query([
            'ApiUser' => $this->apiUser,
            'ApiKey' => $this->apiKey,
            'UserName' => $this->username,
            'Command' => 'namecheap.domains.check',
            'ClientIp' => $this->clientIp,
            'DomainList' => $domainList
        ]);

        $url = $this->baseUrl . '?' . $query;

        try {
            $response = $this->makeApiRequest($url);
            
            if (!$response) {
                throw new \Exception('فشل الاتصال بالخادم');
            }

            $xml = simplexml_load_string($response);
            
            if ($xml === false) {
                throw new \Exception('خطأ في معالجة البيانات');
            }

            $json = json_decode(json_encode($xml), true);

            if (isset($json['Errors']['Error'])) {
                throw new \Exception('خطأ من API: ' . ($json['Errors']['Error']['#text'] ?? 'Unknown'));
            }

            $results = [];
            
            if (isset($json['CommandResponse']['DomainCheckResult'])) {
                $checkResults = $json['CommandResponse']['DomainCheckResult'];
                
                if (isset($checkResults['@attributes'])) {
                    $checkResults = [$checkResults];
                }
                
                foreach ($checkResults as $result) {
                    if (isset($result['@attributes'])) {
                        $domain = $result['@attributes']['Domain'];
                        $available = $result['@attributes']['Available'] === 'true';
                        
                        $results[$domain] = [
                            'domain' => $domain,
                            'available' => $available,
                            'premium' => isset($result['@attributes']['IsPremiumName']) && 
                                       $result['@attributes']['IsPremiumName'] === 'true'
                        ];

                        // حفظ في Cache بشكل آمن
                        $cacheKey = 'domain_check_' . md5($domain);
                        $this->safeCache($cacheKey, [
                            'success' => true,
                            'domain' => $domain,
                            'available' => $available
                        ], now()->addHour());
                    }
                }
            }

            return [
                'success' => true,
                'results' => $results,
                'total' => count($results)
            ];

        } catch (\Exception $e) {
            Log::error('Namecheap Multiple Check Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'results' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * اقتراح دومينات بديلة بامتدادات مختلفة من قاعدة البيانات
     * 
     * @param string $domainName
     * @param int $limit
     * @return array
     */
    public function suggestAlternativeExtensions(string $domainName, int $limit = 8): array
    {
        $domainName = strtolower(trim($domainName));
        $domainName = preg_replace('/[^a-z0-9-]/', '', $domainName);

        if (empty($domainName)) {
            return [
                'success' => false,
                'error' => 'اسم الدومين غير صالح'
            ];
        }

        $extensions = [];
        
        try {
            tenancy()->central(function () use (&$extensions, $limit) {
                $extensions = \App\Models\Admin\ControlDomain\ControlDomain::select('domain_extension', 'price')
                    ->orderBy('price', 'asc')
                    ->limit($limit * 2)
                    ->get();
            });
        } catch (\Exception $e) {
            Log::error('Database Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'خطأ في جلب البيانات'
            ];
        }

        if ($extensions->isEmpty()) {
            return [
                'success' => false,
                'error' => 'لا توجد امتدادات متاحة'
            ];
        }

        $domainsToCheck = [];
        $priceMap = [];
        
        foreach ($extensions as $extension) {
            $fullDomain = $domainName . $extension->domain_extension;
            $domainsToCheck[] = $fullDomain;
            $priceMap[$fullDomain] = [
                'extension' => $extension->domain_extension,
                'price' => $extension->price
            ];
        }

        $suggestions = [];
        $chunks = array_chunk($domainsToCheck, 10);
        
        foreach ($chunks as $index => $chunk) {
            $results = $this->checkMultipleDomains($chunk);
            
            if ($results['success'] && !empty($results['results'])) {
                foreach ($results['results'] as $domain => $result) {
                    if ($result['available'] === true && isset($priceMap[$domain])) {
                        $suggestions[] = [
                            'domain' => $domain,
                            'extension' => $priceMap[$domain]['extension'],
                            'price' => $priceMap[$domain]['price'],
                            'available' => true,
                            'premium' => $result['premium'] ?? false
                        ];
                        
                        if (count($suggestions) >= $limit) {
                            break 2;
                        }
                    }
                }
            }
            
            if ($index < count($chunks) - 1 && count($chunks) > 1) {
                usleep(500000);
            }
        }

        return [
            'success' => true,
            'suggestions' => $suggestions,
            'total' => count($suggestions),
            'domain_name' => $domainName
        ];
    }

    /**
     * اقتراح دومينات بديلة بتعديلات على الاسم
     * 
     * @param string $domainName
     * @param string $extension
     * @return array
     */
    public function suggestAlternativeNames(string $domainName, string $extension = '.com'): array
    {
        $domainName = strtolower(trim($domainName));
        $domainName = preg_replace('/[^a-z0-9-]/', '', $domainName);

        $variations = [
            $domainName . 'online',
            $domainName . 'shop',
            $domainName . 'store',
            $domainName . 'hub',
            $domainName . 'pro',
            'get' . $domainName,
            'my' . $domainName,
            $domainName . '24',
            $domainName . 'app',
            'the' . $domainName,
        ];

        $domainsToCheck = [];
        foreach ($variations as $variation) {
            $domainsToCheck[] = $variation . $extension;
        }

        $results = $this->checkMultipleDomains($domainsToCheck);
        
        $suggestions = [];
        if ($results['success'] && !empty($results['results'])) {
            foreach ($results['results'] as $domain => $result) {
                if ($result['available'] === true) {
                    $suggestions[] = [
                        'domain' => $domain,
                        'available' => true
                    ];
                }
            }
        }

        return [
            'success' => true,
            'suggestions' => $suggestions,
            'total' => count($suggestions)
        ];
    }

    /**
     * إرسال طلب للـ API باستخدام cURL
     * 
     * @param string $url
     * @return string|false
     */
    private function makeApiRequest(string $url)
    {
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        curl_close($curl);

        if ($error) {
            Log::error('cURL Error: ' . $error);
            throw new \Exception('فشل الاتصال: ' . $error);
        }

        if ($httpCode !== 200) {
            Log::error('HTTP Error: ' . $httpCode);
            throw new \Exception('خطأ في الخادم: ' . $httpCode);
        }

        return $response;
    }
}
