<?php

namespace App\Http\Controllers\Admin\Payments\Moyaser;

use App\DataTables\Admin\Setting\SettingManagePaymentDataTable;
use App\Http\Controllers\Controller;
use App\Jobs\Domain\CreateDomainProceesJob;
use App\Jobs\Domain\CreateloginAndTokenJob;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Domain;
use App\Models\Admin\Tenants\Tenant;
use App\Services\shared\Billing\BillingService;
use App\Services\shared\NameCheap\NameCheapService;
use App\Services\shared\NameCheap\RenewalDomainService;
use App\Services\shared\Payment\Moyasar\MoyasarService;
use App\Services\shared\Subscription\SubscriptionManager;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoyaserController extends Controller
{
    public $baseUrl;
    public $whmUrl;
    public $whmToken;
    protected $nameCheapService;
    public function __construct(
        NameCheapService $nameCheapService,
        private BillingService $billService,
        private MoyasarService $moyasar_service,
        private RenewalDomainService $renewalDomainService
    ) {
        $this->nameCheapService = $nameCheapService;
        $this->baseUrl = 'https://api.moyasar.com/v1/';
        $this->whmUrl = env('WHM_URL');
        $this->whmToken = env('WHM_TOKEN');
    }

    public function createPayment($request)
    {

        // $request->validate([
        //     'amount' => 'required|integer|min:100',
        //     'description' => 'nullable|string|max:255',
        //     'card_number' => 'required|string',
        //     'exp_month' => 'required|integer|min:1|max:2',
        //     'exp_year' => 'required|integer|min:4|max:4',
        //     'cvc' => 'required|string|min:3|max:4',
        // ]);
        $exp_date_display = explode('/', $request->exp_date_display);

        $response = Http::withBasicAuth(config('services.moyasar.secret'), '')
            ->post('https://api.moyasar.com/v1/payments', [
                'amount'      => $request->amount,
                'currency'    => 'SAR',
                'description' => $request->description,

                'callback_url'  => route('site.payment.callback', [
                    //
                    'plan_id'           => $request->plan_id,
                    'price'             => $request->amount,
                    'billing'           => $request->billing_cycle,
                    'user_count'        => $request->user_count ?? 0,
                    'range_id'          => $request->range_id,
                    'price_per_license'    => $request->price_per_license,
                ]),

                'source' => [
                    'type'   => $request->cardType,
                    'name'   => $request->billing_fullname ?? "TEST",
                    'number' => trim(str_replace(' ', '', $request->card_number)),
                    'cvc'    => $request->cvc,
                    'month'  => $exp_date_display[0],
                    'year'   => $exp_date_display[1],
                ],
            ]);

        $payment = $response->json();

        if (isset($payment['source']['transaction_url'])) {
            return redirect($payment['source']['transaction_url']);
        }

        return $payment;
    }

    public function callback(Request $request, SubscriptionManager $subscriptionService)
    {
        $paymentId = $request->get('id');

        if ($paymentId) {
            $response = Http::withBasicAuth(config('services.moyasar.secret'), '')
                ->get("https://api.moyasar.com/v1/payments/{$paymentId}");

            $payment = $response->json();

            if ($payment['status'] === 'paid') {
                //      $user = Auth::user();
                // $user->subscriptions()->create([
                //     'plan_id'     => $plan->id,
                //     'started_at'  => now(),
                //     'expires_at'  => now()->addMonth(), // لو اخترنا اشتراك شهري مثلاً
                // ]);
                //  $tenantCount = Tenant::count();

                $tenant =  Tenant::create([
                    'id' => 'tenant' . (Tenant::count() + 30),
                    'folderName' => 'tenant' . (Tenant::count() + 30),
                    'created_at' => now(),
                ]);
                //                 $tenantCount = Tenant::count();

                //                 $tenant =  Tenant::create([
                //                     'id' => 'tenant' . $tenantCount + 1,
                //                     'created_at' => now(),
                //                 ]);
                //                 $dashboard = new CpanelController();
                //                 $dashboard->createfolder($tenant->id);

                //                 $domain = new NameCheapController();
                //                 $domain->createDomainWithNameservers($tenant->id . '.com', $tenant->id, 'com');
                //  $tenantCount = Tenant::count();

                //   $tenant =  Tenant::create([
                //         'id' => 'tenant'. (Tenant::count() + 1) ,
                //         'created_at' =>now(),
                //      ]);

                // $cpanel = new CpanelController();

                //                 $tenantCount = Tenant::count();

                //                 $tenant =  Tenant::create([
                //                     'id' => 'tenant' . $tenantCount + 1,
                //                     'created_at' => now(),
                //                 ]);
                //                 $dashboard = new CpanelController();
                //                 $dashboard->createfolder($tenant->id);

                //                 $domain = new NameCheapController();
                //                 $domain->createDomainWithNameservers($tenant->id . '.com', $tenant->id, 'com');
                //  $tenantCount = Tenant::count();


                // ]);



                // $cpanel = new CpanelController();
                // $folderName =   $cpanel->createfolder('tenant' . (Tenant::count()));
                // $cpanel->createDatabase('tenant' . (Tenant::count() + 1), 'myuser' . (Tenant::count() + 1));
                $host = request()->getHost(); // هيجيب الهوست الحالي (localhost أو corecode.tech مثلاً)
                // $scheme = request()->getScheme(); // هيجيب http أو https

                // $domain = new NameCheapController();
                // $domain->registerDomainAndSetupHosting2($tenant->id . 'levan.com');
                $ten_domain = $tenant->domains()->create([
                    'domain' => $tenant->id . '.' . $host,
                    'tenant_id' => $tenant->id,
                ]);
                // tenancy()->initialize($tenant);


                if ($request->price_per_license) {
                    $subscriptionService->createSubscription(
                        [
                            'tenant_id'         => $tenant->id,
                            'plan_id'           => $request->plan_id,
                            'price'             => $request->price,
                            'currency'          => 'SAR',
                            'billing_cycle'     => $request->billing,
                            'licenses_quota'  => $request->user_count ?? 0,
                            'range_id'          => $request->range_id,
                            'price_per_license'    => $request->price_per_license,
                        ]
                    );
                }


                // Artisan::call('db:seed', [
                //     '--class' => \Database\Seeders\tenant\TenantDatabaseSeeder::class, // غيّر الاسم حسب السيدر بتاعك
                //     '--force' => true,
                // ]);

                // tenancy()->end();

                return redirect()->to('/login')
                    ->with('success', 'تم الدفع بنجاح!');
            } else {
                return "فشل الدفع أو لم يكتمل. الحالة: {$payment['status']}";
            }
        }

        return "لا يوجد معرف دفع (id) في الطلب.";
    }

    public function newDomain(Request $request)
    {
        $price  = (int) $request->price;
        $domainname = $request->domainname;
        return view('tenants.domain_setting.payment', compact('domainname', 'price'));
    }
    public function newDomainProccess(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
            'cardType' => 'required|string',
            'card_number' => 'required|string',
            'exp_date_display' => 'required|string',
            'cvc' => 'required|string|min:3|max:4',
        ]);
        return $this->newDomainPayment($request);
    }

    public function newDomainPayment(Request $request)
    {
        $savedCardId = $request->input('saved_card_id');

        // إذا تم اختيار بطاقة محفوظة
        if ($savedCardId && $savedCardId != '') {
            return $this->payWithSavedCard($request, $savedCardId);
        }

        // استخدام بطاقة جديدة
        return $this->payWithNewCard($request);
    }

    private function payWithNewCard(Request $request)
    {
        // return $request;
        try {
            // التحقق من البيانات
            // $request->validate([
            //     'card_number' => 'required|string|min:13|max:19',
            //     'card_holder_name' => 'required|string|min:3',
            //     'exp_date_display' => 'required|string|regex:/^\d{2}\/\d{2}$/',
            //     'cvc' => 'required|string|min:3|max:4',
            // ], [
            //     'card_number.required' => 'رقم البطاقة مطلوب',
            //     'card_number.min' => 'رقم البطاقة غير صحيح',
            //     'card_holder_name.required' => 'اسم حامل البطاقة مطلوب',
            //     'card_holder_name.min' => 'اسم حامل البطاقة يجب أن يكون 3 أحرف على الأقل',
            //     'exp_date_display.required' => 'تاريخ الانتهاء مطلوب',
            //     'exp_date_display.regex' => 'تاريخ الانتهاء يجب أن يكون بصيغة MM/YY',
            //     'cvc.required' => 'رمز الأمان مطلوب',
            //     'cvc.min' => 'رمز الأمان يجب أن يكون 3 أو 4 أرقام',
            // ]);

            $exp_date_display = explode('/', $request->exp_date_display);
            $exp_month = $exp_date_display[0];
            $exp_year = '20' . $exp_date_display[1]; // تحويل 27 إلى 2027

            // تنظيف رقم البطاقة
            $cardNumber = preg_replace('/\s+/', '', $request->card_number);

            // نوع البطاقة (Moyasar يقبل creditcard فقط)
            $cardType = 'creditcard';
            $amount = (int) round($request->amount * 100);
            // إرسال الدفع إلى Moyasar
            $response = Http::withBasicAuth(config('services.moyasar.secret'), '')
                ->post('https://api.moyasar.com/v1/payments', [
                    'amount'      => $amount,
                    'currency'    => 'SAR',
                    'description' => 'شراء دومين',
                    'callback_url' => tenant_route_name('domains.payment.callbacknewdomain', [
                        'domain' => $request->domainname,
                        'amount' => $amount
                    ]),
                    'source' => [
                        'type'   => $cardType,
                        'name'   => strtoupper($request->card_holder_name),
                        'number' => $cardNumber,
                        'cvc'    => $request->cvc,
                        'month'  => $exp_month,
                        'year'   => $exp_year,
                    ],
                ]);

            $payment = $response->json();


            if (isset($payment['source']['transaction_url'])) {


                return redirect($payment['source']['transaction_url']);
            }

            // في حالة وجود خطأ
            if (isset($payment['message'])) {
                return redirect()->back()->with('error', 'خطأ في الدفع: ' . $payment['message'])->withInput();
            }

            if (isset($payment['errors'])) {
                $errors = collect($payment['errors'])->flatten()->implode(', ');
                return redirect()->back()->with('error', 'أخطاء في البيانات: ' . $errors)->withInput();
            }

            return redirect()->back()->with('error', 'حدث خطأ أثناء معالجة الدفع')->withInput();
        } catch (\Exception $e) {
            Log::error('Payment Error (New Card): ' . $e->getMessage());
            return redirect()->back()->with('error', 'حدث خطأ: ' . $e->getMessage())->withInput();
        }
    }

    private function detectCardBrand($number)
    {
        $cleanNumber = preg_replace('/\s+/', '', $number);

        // مدى
        if (preg_match('/^(4(0(0861|1757|7(197|395)|9201)|1(0685|7633|9593)|2(281(7|8|9)|8(331|67(1|2|3)))|3(1361|2328|4107|9954)|4(0(533|647|795)|5564|6(393|404|672))|5(5(036|708)|7865|9(182|751))|6(2220|854(0|1|2|3))|8(301(0|1|2)|4783|609(4|5|6)|931(7|8|9))|93428)|(588845|636120|588848|588850|968203|968204|968205|968206|968209))/', $cleanNumber)) {
            return 'mada';
        }

        // Visa
        if (preg_match('/^4/', $cleanNumber)) {
            return 'visa';
        }

        // Mastercard
        if (preg_match('/^5[1-5]/', $cleanNumber) || preg_match('/^2(2[2-9][0-9]|[3-6][0-9]{2}|7[0-1][0-9]|720)/', $cleanNumber)) {
            return 'mastercard';
        }

        return 'visa'; // default
    }

    public function CallbacknewDomainPayment(Request $request)
    {
        $domainname = $request->get('domain');
        $paymentId = $request->get('id');
        $saveCard = $request->get('save_card');

        if (!$paymentId) {
            return redirect()->to('/' . tenant('id') . '/dashboard/domain')
                ->with('error', 'لا يوجد معرف دفع (id) في الطلب.');
        }


        // التحقق من حالة الدفع من Moyasar
        $response = Http::withBasicAuth(config('services.moyasar.secret'), '')
            ->get("https://api.moyasar.com/v1/payments/{$paymentId}");

        $payment = $response->json();

        Log::info('Moyasar Callback Response:', $payment);

        // ✅ تخزين بيانات المعاملة في قاعدة البيانات المركزية


        if ($payment['status'] === 'paid') {

            Log::info('before proccess');
            $tenantId = tenant('id');

            $tenant = tenant('id');

            $subscription = Subscription::where('tenant_id', $tenant)->latest('id')->first();
            $period = $subscription?->currentPeriod;
            $this->billService->createNewDomainBill($subscription, $period, $payment, $request->amount, $tenant);
            // tenant()->update([
            //     'folderName' => $domainname,
            // ]);

            // // حفظ البطاقة إذا طلب المستخدم
            // if ($saveCard == '1' && session()->has('pending_card_save')) {
            //     $this->saveCardFromSession();
            // }

            // // حفظ الدومين
            // $this->saveDomainToDatabase($domainname, $tenantId);


            // $cpanelUsername = $this->generateAndSaveCpanelUsername($domainname, $tenantId);

            // إرسال الـ Job لمعالجة الدومين

            // $cpanelUser = explode('.', $domainname)[0];
            $account = tenancy()->central(function () use ($tenantId) {
                return   DB::table('cpanel_accounts')->where('tenant_id', $tenantId)->first();
            });
            if ($account) {
                Bus::chain([
                    new CreateDomainProceesJob($domainname, 'levan_default', $tenantId),
                ])->dispatch();
            } else {
                Bus::chain([
                    new CreateDomainProceesJob($domainname, 'levan_default', $tenantId),
                    new CreateloginAndTokenJob($domainname, $tenantId),
                ])->dispatch();
            }



            // $this->createUserTokenViaLogin($domainname);
            Log::info('✅ Domain purchase initiated', [
                'domain' => $domainname,
                'tenant_id' => $tenantId,
                // 'cpanel_username' => $cpanelUsername
            ]);
            // return redirect()->route('domains.setting')
            $request->merge([
                'domain' => $domainname
            ]);

            $this->nameCheapService->createDomainRowWithActivation($domainname, 'registered', $tenantId);
            return  $this->nameCheapService->modifyAccount($domainname, $tenantId);
            return redirect()->back()
                ->with('success', 'تم شراء الدومين بنجاح! جاري معالجة الطلب...');
        }
    }


    protected function saveDomainToDatabase($domainName, $tenantId)
    {
        try {
            Log::info('💾 Saving domain to database', [
                'domain' => $domainName,
                'tenant_id' => $tenantId
            ]);

            // جلب الدومين الرئيسي للـ tenant
            $existingDomain = Domain::where('tenant_id', $tenantId)->first();

            if (!$existingDomain) {
                $newDomain = Domain::create([
                    'domain' => $domainName,
                    'tenant_id' => $tenantId,
                    'registered_domains' => [
                        $domainName => [
                            'status' => 1,          // الحالة (1 = نشط)
                            'type' => 'register',   // النوع: register أو transfer
                        ]
                    ],
                    // 'is_active' => true,
                    'registered_at' => now(),
                ]);

                Log::info('✅ First domain saved with value 1', [
                    'domain_id' => $newDomain->id,
                    'domain' => $domainName,
                    'registered_domains' => $newDomain->registered_domains
                ]);

                return;
            }

            // جلب الدومينات المسجلة
            $registeredDomains = is_array($existingDomain->registered_domains)
                ? $existingDomain->registered_domains
                : json_decode($existingDomain->registered_domains ?? '[]', true);

            // جعل كل الدومينات القديمة status = 0
            foreach ($registeredDomains as $domainKey => &$domainData) {
                if (is_array($domainData)) {
                    $domainData['status'] = 0;
                }
            }
            unset($domainData); // تنظيف المرجع

            // إضافة الدومين الجديد بـ status = 1 (نشط)
            $registeredDomains[$domainName] = [
                'status' => 1,
                'type' => 'register',
            ];

            Log::info('📋 Updated domains list', [
                'domains' => $registeredDomains
            ]);



            // إنشاء أو تحديث الدومين الجديد كـ active
            // $domain = Domain::updateOrCreate(
            //     [
            //         'domain' => $domainName,
            //         'tenant_id' => $tenantId,
            //     ],
            //     [
            //         'registered_domains' => [
            //                 $domainName => [
            //                     'status' => 1,          // الحالة (1 = نشط)
            //                     'type' => 'register',   // النوع: register أو transfer
            //                 ]
            //             ],
            //          'registered_at' => now(),
            //     ]
            // );

            Log::info('✅ New domain saved with value 1', [
                // 'domain_id' => $domain->id,
                'domain' => $domainName,
                'is_active' => true
            ]);

            // تحديث registered_domains في جميع الدومينات
            Domain::where('tenant_id', $tenantId)
                ->update(['registered_domains' => $registeredDomains]);

            Log::info('✅ All domains synchronized', [
                'tenant_id' => $tenantId,
                'total_domains' => count($registeredDomains),
                'active_domain' => $domainName
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Failed to save domain', [
                'domain' => $domainName,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
    }


    protected function generateAndSaveCpanelUsername($domainName, $tenantId)
    {
        try {
            $tenant = Tenant::find($tenantId);

            if (!$tenant) {
                throw new \Exception('Tenant not found.');
            }

            // ==================================================
            // 1️⃣ التحقق من وجود username محفوظ مسبقاً في الداتا بيز
            // ==================================================
            try {
                // استعلام عن وجود الحساب في WHM
                $response = Http::withHeaders([
                    'Authorization' => 'whm levan:' . env('WHM_TOKEN'),
                ])->timeout(30)->get(env('WHM_URL', 'https://server7.e-tec.sa:2087/json-api') . '/accountsummary', [
                    'api.version' => 1,
                    'user' => $tenant->cpanel_username,
                ]);

                $result = $response->json();

                // لو الحساب موجود فعلاً في WHM -> نوقف هنا
                if (isset($result['metadata']['result']) && $result['metadata']['result'] == 1) {
                    Log::info('✅ cPanel account already exists on WHM', [
                        'tenant_id' => $tenantId,
                        'cpanel_username' => $tenant->cpanel_username,
                    ]);
                    return false; // مش محتاج نعمل أي حاجة
                } else {
                    Log::warning('⚠️ cPanel username not found on WHM, creating new one...', [
                        'tenant_id' => $tenantId,
                        'cpanel_username' => $tenant->cpanel_username,
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('⚠️ Error checking cPanel account existence', [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                ]);
            }

            // ==================================================
            // 2️⃣ إنشاء username جديد وحساب WHM جديد
            // ==================================================
            // $username = 'tn' . substr(md5($domainName . microtime()), 0, 6);
            // $password = $this->generateStrongPassword();
            // $response = Http::withHeaders([
            //     'Authorization' => 'whm levan:' . env('WHM_TOKEN'),
            // ])->timeout(60)->get(env('WHM_URL', 'https://server7.e-tec.sa:2087/json-api') . '/createacct', [
            //     'api.version' => 1,
            //     'username' => $username,
            //     'domain' => $tenant->folderName,
            //     'plan' => 'levan_default',
            //     'password' => $password,
            //     'contactemail' => 'admin@levan.com',
            //     'ip' => '135.181.215.105',
            //     'skipdomaincheck' => 1,
            // ]);

            // $result = $response->json();

            // if (!isset($result['metadata']['result']) || $result['metadata']['result'] != 1) {
            //     $errorMsg = $result['metadata']['reason'] ?? 'Unknown error';
            //     throw new \Exception('فشل إنشاء حساب cPanel: ' . $errorMsg);
            // }

            // ==================================================
            // 3️⃣ حفظ بيانات الحساب الجديد في الداتابيز
            // ==================================================
            // $tenant->update([
            //     'cpanel_username' => $username,
            //     // 'cpanel_password' => $password,
            // ]);

            // Log::info('✅ cPanel account created successfully', [
            //     'tenant_id' => $tenantId,
            //     'cpanel_username' => $username,
            // ]);

            // return true;
        } catch (\Exception $e) {
            Log::error('❌ Failed to generate or create cPanel account', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }





    public $page = 'admin.paymenttransaction';

    public function indexPayment(SettingManagePaymentDataTable $dataTable)
    {
        try {
            // render() بيولد الجدول Ajax جاهز
            return $dataTable->render($this->page . '.index');
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }



    public function fetchPaymentsFromMoyasar($from, $to)
    {
        $curl = curl_init();

        $url = 'https://api.moyasar.com/v1/payments?created[gt]=' . $from . '&created[lt]=' . $to;

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode(env('MOYASER_SECRET_KEY') . ':')
            ),
        ));

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl)) {
            curl_close($curl);
            return [];
        }

        curl_close($curl);

        if ($httpCode !== 200) {
            return [];
        }

        $data = json_decode($response, true);

        return $data['payments'] ?? $data ?? [];
    }


    public function processTransferPayment(Request $request)
    {
        $exp_date_display = explode('/', $request->expiry_date);
        $exp_month = $exp_date_display[0];
        $exp_year = '20' . $exp_date_display[1];


        $cardNumber = preg_replace('/\s+/', '', $request->card_number);


        $cardType = 'creditcard';

        $response = Http::withBasicAuth(config('services.moyasar.secret'), '')
            ->post('https://api.moyasar.com/v1/payments', [
                'amount'      => (int)($request->amount),
                'currency'    => 'SAR',
                'description' => 'نقل دومين',
                'callback_url' => tenant_route_name('domains.payment.callback.transfer', [
                    'eppCode' => $request->eppCode,
                    'domain' => $request->domain
                ]),
                'source' => [
                    'type'   => $cardType,
                    'name'   => strtoupper($request->card_holder),
                    'number' => $cardNumber,
                    'cvc'    => $request->cvc,
                    'month'  => $exp_month,
                    'year'   => $exp_year,
                ],
            ]);

        $payment = $response->json();

        // Log للتحقق
        Log::info('Moyasar Payment Response (New Card):', $payment);

        if (isset($payment['source']['transaction_url'])) {


            return redirect($payment['source']['transaction_url']);
        }
    }

    public function processTransferCallback(Request $request)
    {

        $domainname = $request->get('domain');
        $paymentId = $request->get('id');

        if (!$paymentId) {
            return redirect()->to('/' . tenant('id') . '/dashboard/domain')
                ->with('error', 'لا يوجد معرف دفع (id) في الطلب.');
        }



        $response = Http::withBasicAuth(config('services.moyasar.secret'), '')
            ->get("https://api.moyasar.com/v1/payments/{$paymentId}");

        $payment = $response->json();

        Log::info('Moyasar Callback Response:', $payment);



        if ($payment['status'] === 'paid') {
            $request->merge([
                'domain' => $domainname
            ]);
            $this->nameCheapService->transferProcess($request);
            return redirect()->to(tenant('id') . '/domains/transfer-dom-blade?step=4');
        }
    }
    private function splitExpiry(string $exp): array
    {
        if (preg_match('/^(\d{2})\/?(\d{2})$/', $exp, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }
        return [null, null];
    }
    public function moyasarPaymentRenewal(Request $request)
    {
        // return $request;
        $cleanAmount = preg_replace('/[^0-9.]/', '', (string) $request->amount);

        // تحويل إلى float (يدعم 1.5 / 10.25 / 0.99)
        $amountFloat = (float) $cleanAmount;

        if ($amountFloat <= 0) {
            throw new \InvalidArgumentException('المبلغ غير صالح.');
        }

        // تحويل الريال إلى هللة (1.5 → 150)
        // (يجب أن يكون عدد صحيح)
        $amountInHalala = (int) round($amountFloat * 100);

        // --- معالجة تاريخ الانتهاء ---
        $exp = preg_replace('/\s+/', '', (string) $request->exp_date_display);

        [$month, $year] = $this->splitExpiry($exp);

        if (!$month || !$year) {
            throw new \InvalidArgumentException('تاريخ انتهاء البطاقة غير صالح.');
        }
        try {
            $response = Http::withBasicAuth(config('services.moyasar.secret'), '')
                ->post('https://api.moyasar.com/v1/payments', [
                    'amount'      => $amountInHalala,
                    'currency'    => 'SAR',
                    'description' => $request->description ?? 'عملية دفع',

                    'callback_url' => tenant_route_name(
                        'domains.moyasar.renewal.callback',
                        [
                            'years' => $request->years,
                            'domain_name' => $request->domain_name
                        ]
                    ),

                    'source' => [
                        'type'   => $request->cardType ?? 'creditcard',
                        'name'   => $request->card_name_display,
                        'number' => preg_replace('/\s+/', '', (string) $request->card_number),
                        'cvc'    => $request->cvc,
                        'month'  => $month,
                        'year'   => $year,
                    ],
                ]);

            if ($response->json()['source']['transaction_url']) {
                return redirect($response->json()['source']['transaction_url']);
            }
        } catch (Exception $e) {
            return redirect()->to(tenant('id') . '/domains/renewal/payment')
                ->with('error', 'هناك خطأ في بيانات الدفع')
                ->withInput();
        }
    }
    public function moyasarPaymentRenewalCallback(Request $request)
    {
        // $domainname = $request->get('domain');
        $paymentId = $request->get('id');
        // return $request->years;
        //   if (!$paymentId) {
        //     return redirect()->to('/' . tenant('id') . '/dashboard/domain')
        //         ->with('error', 'لا يوجد معرف دفع (id) في الطلب.');
        // }

        $response = Http::withBasicAuth(config('services.moyasar.secret'), '')
            ->get("https://api.moyasar.com/v1/payments/{$paymentId}");

        $payment = $response->json();

        Log::info('Moyasar Callback Response:', $payment);



        if ($payment['status'] === 'paid') {
            $tenant = tenant('id');

            $subscription = Subscription::where('tenant_id', $tenant)->latest('id')->first();
            $period = $subscription?->currentPeriod;
            $this->billService->createRenewalDomainBill($subscription, $period, $payment, $payment['amount'], $tenant);
            return $this->renewalDomainService->renewDomain($request->domain_name, $request->years);
        }
    }
}
