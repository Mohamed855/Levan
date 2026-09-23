<?php

namespace App\Http\Middleware;

use App\Services\Admin\Microsoft\Mail\MicrosoftMailService;
use Closure;
use Illuminate\Http\Request;
use App\Services\shared\NameCheap\CheckAccountInfoService;

class CheckDomainBalance
{
    protected $namecheapService;

    public function __construct(CheckAccountInfoService $namecheapService , private MicrosoftMailService $mailService)
    {
        $this->namecheapService = $namecheapService;
    }

 
    public function handle(Request $request, Closure $next)
    {

         $domain = $request->input('domainname');

        if (!$domain) {
             return redirect('/')->with('error', 'لم يتم تحديد الدومين.');
        }

        $domainParts = explode('.', $domain);
        $tld = $domainParts[1] ?? null;

        // if (!$tld) {
        //     return redirect('/')->with('error', 'الدومين غير صالح.');
        // }

        $balance = $this->namecheapService->getBalance();
        $domainPrice = $this->namecheapService->getDomainPrice($tld);

        if ($balance['balance'] < $domainPrice['total_price']) {
             $tenantId = tenant('id'); 
            $this->mailService->sendEmail(
                'is606ay@gmail.com',
                'تنبيه - محاولة تسجيل دومين',
                "تنبيه:\n\nالعميل {$tenantId} حاول تسجيل الدومين .{$tld}، ولكن العملية لم تتم بسبب عدم كفاية الرصيد.\nالرصيد الحالي للعميل: {$balance['balance']} {$domainPrice['currency']}.\nالسعر المطلوب لتسجيل الدومين: {$domainPrice['total_price']} {$domainPrice['currency']}.\n\nيرجى متابعة الأمر مع العميل."
            );
 
            return redirect()->back()->with('info', 'شكرًا لتواصلك معنا. سيقوم فريق الدعم بالتواصل معك في أقرب وقت لمراجعة طلبك وتقديم الدعم المناسب.');
        }


        return $next($request);
    }
}
