<?php

namespace App\Http\Controllers\Tenants\Domains;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Domains\CheckMultipleDomainsRequest;
use App\Http\Requests\Tenants\Domains\ClearAllDomainCacheRequest;
use App\Http\Requests\Tenants\Domains\ClearDomainCacheRequest;
use App\Http\Requests\Tenants\Domains\SuggestDomainAlternativesRequest;
use App\Http\Requests\Tenants\Domains\SuggestDomainNamesRequest;
use App\Services\shared\NameCheap\CheckMultipleDomainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckMultipleDomainController extends Controller
{
    protected $domainService;

    public function __construct(CheckMultipleDomainService $domainService)
    {
        $this->middleware('can:domain.view');
        $this->domainService = $domainService;
    }

    /**
     * التحقق من توفر دومين واحد
     */
    public function checkSingle(string $domain): JsonResponse
    {
        // تنظيف اسم الدومين
        $domain = strtolower(trim($domain));

        // التحقق من صحة اسم الدومين
        if (! $this->isValidDomain($domain)) {
            return response()->json([
                'success' => false,
                'error' => 'اسم الدومين غير صحيح',
            ], 400);
        }

        $result = $this->domainService->checkSingleDomain($domain);

        if (! $result['success']) {
            return response()->json($result, 500);
        }

        return response()->json($result);
    }

    /**
     * التحقق من عدة دومينات
     *
     * @param  Request  $request
     */
    public function checkMultiple(CheckMultipleDomainsRequest $request): JsonResponse
    {
        $domains = $request->validated('domains');

        $result = $this->domainService->checkMultipleDomains($domains);

        if (! $result['success']) {
            return response()->json($result, 500);
        }

        return response()->json($result);
    }

    /**
     * اقتراح دومينات بديلة بامتدادات مختلفة
     */
    public function suggestAlternatives(SuggestDomainAlternativesRequest $request): JsonResponse
    {
        $domainName = $request->validated('domain_name');
        $limit = $request->integer('limit', 8);

        $result = $this->domainService->suggestAlternativeExtensions($domainName, $limit);

        if (! $result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * اقتراح دومينات بديلة بتعديلات على الاسم
     */
    public function suggestNames(SuggestDomainNamesRequest $request): JsonResponse
    {
        $domainName = $request->validated('domain_name');
        $extension = $request->validated('extension', '.com');

        $result = $this->domainService->suggestAlternativeNames($domainName, $extension);

        return response()->json($result);
    }

    public function clearCache(ClearDomainCacheRequest $request): JsonResponse
    {
        $this->domainService->clearDomainCache($request->validated('domain'));

        return response()->json([
            'success' => true,
            'message' => 'تم مسح ذاكرة النطاق المؤقتة.',
        ]);
    }

    public function clearAllCache(ClearAllDomainCacheRequest $request): JsonResponse
    {
        $this->domainService->clearCurrentTenantDomainCache();

        return response()->json([
            'success' => true,
            'message' => 'تم مسح ذاكرة النطاقات المؤقتة.',
        ]);
    }

    /**
     * التحقق من صحة اسم الدومين
     */
    private function isValidDomain(string $domain): bool
    {
        // Pattern للدومين الكامل (مع الامتداد)
        $pattern = '/^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i';

        return preg_match($pattern, $domain) === 1;
    }
}
