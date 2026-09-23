<?php

namespace App\Services\shared\Domain;

use App\Models\Admin\Tenants\Domain;
use App\Services\shared\NameCheap\NameCheapService;
use Illuminate\Support\Facades\Log;

class DomainService
{
    protected $nameCheapService;
    public function __construct(NameCheapService $nameCheapService)
    {
        $this->nameCheapService = $nameCheapService;
    }

    // Get coupon statistics
    public function getDomainStatistics(): array
    {
        $tenantId = tenant('id');
        $baseQuery = Domain::where('tenant_id', $tenantId)->get();

        return [
            'total'    => (clone $baseQuery)->count(),
            'active'   => (clone $baseQuery)->where('is_active', 1)->count(),
            'inactive' => (clone $baseQuery)->where('is_active', 0)->count(),
        ];
    }


    public function switchActiveDomain($request)
    {
        $tenantId = tenant('id');
        $selectedDomain = $request->domain;

        try {
            Log::info("🔄 Switching active domain", [
                'tenant_id' => $tenantId,
                'selected_domain' => $selectedDomain
            ]);

            // جلب الدومين الرئيسي
            $mainDomain = Domain::where('tenant_id', $tenantId)->first();

            if (!$mainDomain) {
                return redirect()->back()->with('error', 'لم يتم العثور على دومينات مسجلة');
            }

            // جلب الدومينات المسجلة
            $registeredDomains = is_array($mainDomain->registered_domains)
                ? $mainDomain->registered_domains
                : json_decode($mainDomain->registered_domains ?? '[]', true);

            // التحقق من وجود الدومين المطلوب
            if (!array_key_exists($selectedDomain, $registeredDomains)) {
                return redirect()->back()->with('error', 'الدومين المطلوب غير موجود');
            }

            // جعل كل الدومينات status = 0
            foreach ($registeredDomains as $domainName => &$domainData) {
                if (is_array($domainData)) {
                    $domainData['status'] = 0;
                }
            }
            unset($domainData); // تنظيف المرجع

            // تفعيل الدومين المختار status = 1
            if (isset($registeredDomains[$selectedDomain]) && is_array($registeredDomains[$selectedDomain])) {
                $registeredDomains[$selectedDomain]['status'] = 1;
            }

            // جعل كل الدومينات inactive في الـ database
            // Domain::where('tenant_id', $tenantId)->update(['is_active' => false]);

            // تحديث أو إنشاء الدومين المختار كـ active
            Domain::updateOrCreate(
                [
                    // 'domain' => $selectedDomain,
                    'tenant_id' => $tenantId,
                ],
                [
                    'domain' => $selectedDomain,
                    'registered_domains' => [
                        $domainName => [
                            'status' => 1,          // الحالة (1 = نشط)
                            'type' => 'register',   // النوع: register أو transfer
                        ]
                    ],
                    'registered_at' => now(),
                ]
            );

            // تحديث registered_domains في جميع الدومينات
            Domain::where('tenant_id', $tenantId)
                ->update(['registered_domains' => json_encode($registeredDomains)]);
            return  $this->nameCheapService->modifyAccount($request);
            return redirect()->back()->with('success', 'تم تبديل الدومين النشط بنجاح');
        } catch (\Exception $e) {
            Log::error("❌ Failed to switch domain", [
                'tenant_id' => $tenantId,
                'selected_domain' => $selectedDomain,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'حدث خطأ أثناء تبديل الدومين: ' . $e->getMessage());
        }
    }
}
