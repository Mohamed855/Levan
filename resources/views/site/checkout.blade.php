@extends('site.layouts.main')

@section('title', 'لوت LOT - إتمام الطلب')

@section('page-style')
    @vite(['resources/assets/vendor/fonts/tabler-icons.scss', 'resources/assets/css/components/icons.css', 'resources/assets/css/site/checkout.css'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    <script>
        window.paymentData = {
            total: {{ $plan->price_after_discount }},
        };
    </script>
    @vite(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/site/checkout.js', 'resources/assets/js/shared/payment/apply-coupon.js'])
@endsection

@section('content')
    <main class="main relative">

        <!-- Hero section -->
        <section id="home"
            class="relative overflow-hidden bg-primary text-primary-color pt-[120px] md:pt-[130px] lg:pt-[160px]">
            <div class="container">
                <div class="-mx-5 flex flex-wrap items-center">
                    <div class="w-full px-5">
                        <div class="scroll-revealed mx-auto max-w-[780px] text-center">
                            <h1
                                class="mb-12 text-3xl font-bold leading-snug text-primary-color sm:text-4xl sm:leading-snug lg:text-5xl lg:leading-tight">
                                {{ $plan->name }}
                            </h1>

                            <p class="mx-auto mb-9 max-w-[600px] text-base text-primary-color sm:text-lg sm:leading-normal">
                                {{ $plan->description }}
                            </p>

                            <p class="mx-auto mb-1 max-w-[600px] text-2xl font-bold text-primary-color sm:leading-normal">
                                {{ number_format($plan->price_after_discount) }}
                            </p>
                            <span class="icon-saudi_riyal"></span>

                            <p class="mx-auto mb-9 max-w-[600px] text-base text-primary-color sm:text-lg sm:leading-normal">
                                المبلغ يُدفع مرة واحدة فقط
                            </p>
                        </div>

                    </div>

                </div>
            </div>
        </section>

        <section class="pb-8 bg-gray-50">
            <div class="container mx-auto px-4">

                @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mt-2 mb-0">
                        @foreach ($errors->all() as $error)
                            <p class="text-sm text-red-600 font-semibold">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif
                <div class="mt-2">
                    <form id="payment-form" method="POST" action="{{ route('site.plans.checkout.store', $plan->id) }}"
                        autocomplete="off" enctype="multipart/form-data" data-form-validate
                        data-validation-url="{{ route('site.form-validation') }}" novalidate>
                        @csrf
                        <input type="hidden" name="coupon_code" id="coupon_code_hidden">
                        <input type="hidden" name="bank_id" id="bank_id">

                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                            {{-- العمود الرئيسي --}}
                            <div class="lg:col-span-8 flex flex-col gap-4">

                                {{-- بيانات الشركة --}}
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                                    <div class="px-6 py-4 border-b border-dashed border-gray-200">
                                        <h6 class="text-sm font-semibold text-gray-800 flex items-center gap-2 mb-0">
                                            <i class="ti ti-components text-green-500 text-base"></i>
                                            معلومات الشركة
                                        </h6>
                                    </div>
                                    <div class="p-6">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="md:col-span-2">
                                                <label class="block text-sm font-medium text-gray-700 mb-1.5"
                                                    for="billing_company_name">
                                                    اسم الشركة <span class="text-red-500">*</span>
                                                </label>
                                                <input type="text" id="billing_company_name" name="billing_company_name"
                                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                                    value="{{ old('billing_company_name') }}"
                                                    placeholder="مثال: أحمد محمد للتحصيل" required />
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1.5"
                                                    for="billing_commercial_number">
                                                    رقم السجل التجاري <span class="text-red-500">*</span>
                                                </label>
                                                <input type="text" id="billing_commercial_number"
                                                    name="billing_commercial_number"
                                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                                    value="{{ old('billing_commercial_number') }}"
                                                    placeholder="مثال: 1010123456" required />
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1.5"
                                                    for="billing_tax_number">
                                                    الرقم الضريبي
                                                </label>
                                                <input type="text" id="billing_tax_number" name="billing_tax_number"
                                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                                    value="{{ old('billing_tax_number') }}"
                                                    placeholder="الرقم الضريبي (اختياري)" />
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1.5"
                                                    for="billing_city_id">
                                                    المدينة
                                                </label>
                                                <select id="billing_city_id" name="billing_city_id"
                                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                                    <option value="">اختر المدينة (اختياري)</option>
                                                    @if (isset($cities))
                                                        @foreach ($cities as $city)
                                                            <option value="{{ $city->id }}"
                                                                @selected(old('billing_city_id') == $city->id)>{{ $city->name }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1.5"
                                                    for="billing_address">
                                                    العنوان
                                                </label>
                                                <input type="text" id="billing_address" name="billing_address"
                                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                                    value="{{ old('billing_address') }}"
                                                    placeholder="العنوان أو الحي (اختياري)" />
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1.5"
                                                    for="billing_name">
                                                    اسم المستخدم <span class="text-red-500">*</span>
                                                </label>
                                                <input type="text" id="billing_name" name="billing_name"
                                                    value="{{ old('billing_name') }}"
                                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                                    placeholder="اسم المستخدم" required />
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1.5"
                                                    for="billing_email">
                                                    البريد الإلكتروني <span class="text-red-500">*</span>
                                                </label>
                                                <input type="email" id="billing_email" name="billing_email"
                                                    value="{{ old('billing_email') }}" autocomplete="off"
                                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                                    placeholder="example@domain.com" required />
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1.5"
                                                    for="billing_phone">
                                                    رقم الجوال <span class="text-red-500">*</span>
                                                </label>
                                                <input type="tel" id="billing_phone" name="billing_phone" data-phone-format="international"
                                                    value="{{ old('billing_phone') }}" dir="rtl"
                                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                                    placeholder="05xxxxxxxx" required />
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1.5"
                                                    for="billing_password">
                                                    كلمة المرور <span class="text-red-500">*</span>
                                                </label>
                                                <input type="password" id="billing_password" name="billing_password"
                                                    autocomplete="new-password"
                                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                                    placeholder="••••••••" required />
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1.5"
                                                    for="billing_password_confirmation">
                                                    تأكيد كلمة المرور <span class="text-red-500">*</span>
                                                </label>
                                                <input type="password" id="billing_password_confirmation"
                                                    name="billing_password_confirmation" autocomplete="new-password"
                                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                                    placeholder="••••••••" required />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- اختيار طريقة الدفع --}}
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                                    <div class="px-6 py-4 border-b border-dashed border-gray-200">
                                        <h6 class="text-sm font-semibold text-gray-800 flex items-center gap-2 mb-0">
                                            <i class="ti ti-wallet text-green-500 text-base"></i>
                                            طريقة الدفع
                                        </h6>
                                    </div>
                                    <div class="p-6">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                                            {{-- بطاقة ائتمان --}}
                                            <label
                                                class="payment-option group flex items-center gap-4 p-4 rounded-xl border-2 border-gray-200 cursor-pointer transition-all duration-200 hover:border-blue-400 hover:bg-blue-50"
                                                data-method="card" for="payment_method_card">
                                                <input type="radio" name="payment_method" id="payment_method_card"
                                                    value="card" class="sr-only">
                                                <div
                                                    class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0 transition group-[.active]:bg-blue-100">
                                                    <i class="ti ti-credit-card text-xl text-green-500"></i>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-semibold text-gray-800 mb-0.5">بطاقة ائتمان</p>
                                                    <p class="text-xs text-gray-400 mb-0">Visa · Mastercard · Amex</p>
                                                </div>
                                                <div
                                                    class="payment-check w-5 h-5 rounded-full border-2 border-gray-300 flex items-center justify-center flex-shrink-0 transition-all duration-200">
                                                    <div
                                                        class="w-2.5 h-2.5 rounded-full bg-white scale-0 transition-transform duration-200 payment-dot">
                                                    </div>
                                                </div>
                                            </label>

                                            {{-- تحويل بنكي --}}
                                            <label
                                                class="payment-option group flex items-center gap-4 p-4 rounded-xl border-2 border-gray-200 cursor-pointer transition-all duration-200 hover:border-green-400 hover:bg-green-50"
                                                data-method="bank_transfer" for="payment_method_bank_transfer">
                                                <input type="radio" name="payment_method"
                                                    id="payment_method_bank_transfer" value="bank_transfer"
                                                    class="sr-only">
                                                <div
                                                    class="w-11 h-11 rounded-xl bg-green-50 flex items-center justify-center flex-shrink-0 transition group-[.active]:bg-green-100">
                                                    <i class="ti ti-building-bank text-xl text-green-500"></i>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-semibold text-gray-800 mb-0.5">تحويل بنكي</p>
                                                    <p class="text-xs text-gray-400 mb-0">تحويل مباشر للحساب</p>
                                                </div>
                                                <div
                                                    class="payment-check w-5 h-5 rounded-full border-2 border-gray-300 flex items-center justify-center flex-shrink-0 transition-all duration-200">
                                                    <div
                                                        class="w-2.5 h-2.5 rounded-full bg-white scale-0 transition-transform duration-200 payment-dot">
                                                    </div>
                                                </div>
                                            </label>

                                        </div>
                                    </div>
                                </div>

                                {{-- تُدخل البطاقة في نموذج Moyasar الآمن بعد حفظ الطلب. --}}
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hidden"
                                    id="card-payment-section">
                                    <div class="px-6 py-4 border-b border-dashed border-gray-200">
                                        <h6 class="text-sm font-semibold text-gray-800 flex items-center gap-2 mb-0">
                                            <i class="ti ti-credit-card text-green-500 text-base"></i>
                                            الدفع الآمن بالبطاقة
                                        </h6>
                                    </div>
                                    <div class="p-6">
                                        <div class="flex items-start gap-3 rounded-xl bg-blue-50 border border-blue-100 p-4 text-sm text-blue-900">
                                            <i class="ti ti-shield-lock text-xl text-blue-600 flex-shrink-0"></i>
                                            <p class="mb-0 leading-6">
                                                بعد مراجعة الطلب ستنتقل إلى نموذج دفع Moyasar الآمن لإدخال بيانات البطاقة مباشرة. لا تُرسل بيانات البطاقة إلى موقعنا.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {{-- قسم التحويل البنكي --}}
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hidden"
                                    id="bank-transfer-section">
                                    <div class="px-6 py-4 border-b border-dashed border-gray-200">
                                        <h6 class="text-sm font-semibold text-gray-800 flex items-center gap-2 mb-0">
                                            <i class="ti ti-building-bank text-green-500 text-base"></i>
                                            تفاصيل التحويل البنكي
                                        </h6>
                                    </div>

                                    <div class="p-6">
                                        @if ($banks->isEmpty())
                                            <div
                                                class="flex items-center gap-3 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-xl px-4 py-3 text-sm">
                                                <i class="ti ti-alert-triangle text-lg flex-shrink-0"></i>
                                                لا توجد بنوك متاحة حالياً. يرجى اختيار طريقة دفع أخرى.
                                            </div>
                                        @else
                                            {{-- اختيار البنك --}}
                                            <div class="mb-5">
                                                <label
                                                    class="flex items-center gap-2 text-xs font-medium text-gray-500 mb-3">
                                                    <i class="ti ti-building-bank text-sm"></i>
                                                    اختر البنك للتحويل <span class="text-red-500">*</span>
                                                </label>
                                                <div class="flex flex-col gap-2.5">
                                                    @foreach ($banks as $bank)
                                                        <div class="bank-card group flex items-center gap-4 p-3.5 rounded-xl border-2 border-gray-200 cursor-pointer transition-all duration-200 hover:border-green-400 hover:bg-green-50"
                                                            data-bank-id="{{ $bank->id }}">

                                                            {{-- أيقونة / لوجو البنك --}}
                                                            @if ($bank->icon)
                                                                <img src="{{ asset('storage/' . $bank->icon) }}"
                                                                    alt="{{ $bank->bank_name }}"
                                                                    class="w-28 h-28 rounded-xl object-contain border border-gray-100 flex-shrink-0 bg-white p-1">
                                                            @else
                                                                <div
                                                                    class="w-28 h-28 rounded-xl bg-gray-100 border border-gray-200 flex items-center justify-center flex-shrink-0">
                                                                    <i
                                                                        class="ti ti-building-bank text-xl text-gray-400"></i>
                                                                </div>
                                                            @endif

                                                            {{-- معلومات البنك --}}
                                                            <div class="flex-1 min-w-0">
                                                                <p class="text-sm font-semibold text-gray-800 mb-1">
                                                                    {{ $bank->bank_name }}</p>
                                                                <div class="">
                                                                    <p
                                                                        class="flex items-center text-sm text-gray-500 py-1 my-0 font-light">
                                                                        <i class="ti ti-user text-sm font-bold mx-1"></i>
                                                                        {{ $bank->account_name }}
                                                                    </p>
                                                                    <p
                                                                        class="flex items-center text-sm text-gray-500 py-1 my-0 font-light">
                                                                        <i
                                                                            class="ti ti-credit-card text-sm font-bold mx-1"></i>
                                                                        {{ $bank->account_number }}
                                                                    </p>
                                                                    @if ($bank->iban)
                                                                        <p
                                                                            class="flex items-center text-sm text-gray-500 py-1 my-0 font-light">
                                                                            <i class="ti ti-id text-sm font-bold mx-1"></i>
                                                                            {{ $bank->iban }}
                                                                        </p>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            {{-- دائرة الاختيار --}}
                                                            <div
                                                                class="bank-radio w-5 h-5 rounded-full border-2 border-gray-300 flex items-center justify-center flex-shrink-0 transition-all duration-200">
                                                                <div
                                                                    class="bank-dot w-2.5 h-2.5 rounded-full bg-white scale-0 transition-transform duration-200">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>

                                            {{-- رفع الإيصال --}}
                                            <div class="mb-5 hidden" id="receipt-upload-section">
                                                <label
                                                    class="flex items-center gap-2 text-xs font-medium text-gray-500 mb-3">
                                                    <i class="ti ti-file-upload text-sm"></i>
                                                    إيصالات التحويل البنكي <span class="text-red-500">*</span>
                                                </label>

                                                <div class="upload-area border-2 border-dashed border-gray-300 rounded-xl p-8 text-center cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-all duration-200"
                                                    id="upload-area">
                                                    <input type="file" name="transfer_receipt" id="transfer_receipt"
                                                        accept="image/jpeg,image/jpg,image/png,application/pdf"
                                                        class="hidden">
                                                    <div
                                                        class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center mx-auto mb-3">
                                                        <i class="ti ti-cloud-upload text-xl text-green-500"></i>
                                                    </div>
                                                    <p class="text-sm font-semibold text-gray-700 mb-1">انقر لرفع إيصال
                                                        التحويل</p>
                                                    <p class="text-xs text-gray-400">JPG · PNG · PDF — حد أقصى 5MB لكل ملف
                                                    </p>
                                                </div>

                                                <div class="mt-3" id="files-preview"></div>
                                            </div>

                                            {{-- تعليمات --}}
                                            <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                                                <h6
                                                    class="flex items-center gap-2 text-xs font-semibold text-green-800 mb-2.5">
                                                    <i class="ti ti-info-circle text-sm"></i>
                                                    تعليمات التحويل البنكي
                                                </h6>
                                                <ul class="space-y-1.5">
                                                    @foreach (['قم بتحويل المبلغ إلى الحساب البنكي المحدد', 'ارفع صورة أو ملف PDF لإيصال التحويل', 'سيتم تفعيل اشتراكك خلال 24 ساعة بعد التحقق من التحويل', 'يمكنك التواصل معنا في حالة وجود أي استفسار'] as $instruction)
                                                        <li class="flex items-start gap-2 text-xs text-green-700">
                                                            <span
                                                                class="w-1.5 h-1.5 rounded-full bg-green-500 flex-shrink-0 mt-1.5"></span>
                                                            {{ $instruction }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>

                                        @endif
                                    </div>

                                </div>

                            </div>

                            {{-- ملخص الطلب --}}
                            <div class="lg:col-span-4">
                                <div
                                    class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden sticky top-6">
                                    <div class="px-6 py-4 border-b border-dashed border-gray-200">
                                        <h6 class="text-sm font-semibold text-gray-800 flex items-center gap-2 mb-0">
                                            <i class="ti ti-invoice text-green-500 text-base"></i>
                                            ملخص عملية الدفع
                                        </h6>
                                    </div>
                                    <div class="p-6">
                                        <div class="bg-blue-50 rounded-xl p-4 mb-6">
                                            <div class="flex items-center justify-between">
                                                <span
                                                    class="font-semibold text-gray-800 text-sm">{{ $plan->name }}</span>
                                                <h6 class="mb-0 text-blue-600 font-bold" id="total-display">
                                                    {{ number_format($plan->price_after_discount, 2) }}
                                                    <span class="icon-saudi_riyal mx-1 text-xs"></span>
                                                </h6>
                                            </div>
                                        </div>

                                        {{-- قسم الكوبون --}}
                                        <div class="">
                                            <div class="flex gap-2">
                                                <input type="text" id="coupon_code_input" name="coupon_code_display"
                                                    class="flex-1 px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                                    placeholder="أدخل كود الخصم">
                                                <button type="button" id="apply-coupon-btn"
                                                    class="px-4 py-2.5 rounded-lg border border-blue-500 text-blue-600 text-sm font-medium hover:bg-blue-50 transition flex items-center gap-1 whitespace-nowrap">
                                                    <i class="ti ti-discount-2 text-base"></i>
                                                    تطبيق
                                                </button>
                                            </div>
                                            <div id="coupon-message" class="mt-2 text-sm"></div>
                                        </div>

                                        {{-- تفاصيل الأسعار --}}
                                        <div>
                                            <hr class="border-gray-200 my-3">

                                            <div class="flex items-center justify-between mb-3">
                                                <p class="text-sm font-semibold text-gray-600 mb-0">الاجمالي قبل الضريبة
                                                </p>
                                                <span class="text-sm font-semibold text-gray-800" id="subtotal-display">
                                                    {{ number_format($priceWithoutTax, 2) }}
                                                    <span class="icon-saudi_riyal mx-1 text-xs"></span>
                                                </span>
                                            </div>

                                            <div class="flex items-center justify-between mb-3">
                                                <p class="text-sm font-semibold text-gray-600 mb-0">ضريبة القيمة المضافة
                                                    (15%)</p>
                                                <span class="text-sm font-semibold text-gray-800" id="vat-display">
                                                    {{ number_format($taxAmount, 2) }}
                                                    <span class="icon-saudi_riyal mx-1 text-xs"></span>
                                                </span>
                                            </div>

                                            <hr class="border-gray-200 my-3">

                                            {{-- سطر الخصم --}}
                                            <div class="hidden flex items-center justify-between mb-3" id="discount-row">
                                                <p class="text-sm text-green-600 mb-0 flex items-center gap-1">
                                                    <i class="ti ti-discount-2 text-base"></i>
                                                    الخصم
                                                    <button type="button"
                                                        class="text-red-500 hover:text-red-700 transition text-xs p-0.5 rounded ms-1"
                                                        id="remove-coupon">
                                                        <i class="ti ti-x text-xs"></i>
                                                    </button>
                                                </p>
                                                <span class="text-sm font-semibold text-red-500">
                                                    -<span id="discount-display">0.00</span>
                                                    <span class="icon-saudi_riyal mx-1 text-xs"></span>
                                                </span>
                                            </div>

                                            <div class="flex items-center justify-between mb-2">
                                                <h5 class="font-bold text-gray-800 text-base mb-0">المبلغ الإجمالي</h5>
                                                <h5 class="font-bold text-blue-600 text-base mb-0 ml-" id="total-final">
                                                    {{ number_format($plan->price_after_discount, 2) }}
                                                    <span class="icon-saudi_riyal mx-1 text-xs"></span>
                                                </h5>
                                            </div>

                                            <div class="mt-6">
                                                <button type="submit"
                                                    class="w-full py-3 px-6 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold rounded-xl transition flex items-center justify-center gap-2 text-base">
                                                    <span>إتمام الدفع</span>
                                                    <i class="ti ti-arrow-left text-lg"></i>
                                                </button>
                                            </div>

                                            <div class="mt-6 bg-gray-50 rounded-xl p-4 space-y-2">
                                                <p
                                                    class="text-xs font-semibold text-gray-500 mb-0 flex items-center gap-2">
                                                    <i class="ti ti-lock text-green-500 text-sm"></i>
                                                    حماية وأمان البيانات والخصوصية
                                                </p>
                                                <p
                                                    class="text-xs font-semibold text-gray-500 mb-0 flex items-center gap-2">
                                                    <i class="ti ti-shield-check text-green-500 text-sm"></i>
                                                    معالجة آمنة عبر بوابات دفع موثوقة ومعتمدة
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </section>

    </main>

@endsection
