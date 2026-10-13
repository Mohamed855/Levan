@extends('site.layouts.main')

@section('title', 'إتمام الدفع | لوت LOT')

@section('page-style')
    @vite(['resources/assets/vendor/fonts/tabler-icons.scss', 'resources/assets/css/components/icons.css'])
@endsection

@section('content')
    <main class="main relative bg-gray-50 py-24 md:py-32">
        <section class="container mx-auto px-4">
            <div class="mx-auto max-w-2xl">
                <a href="{{ route('site.plans.checkout', $plan) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-primary hover:text-primary-dark mb-6">
                    <i class="ti ti-arrow-right"></i>
                    العودة إلى الطلب
                </a>

                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm md:p-8">
                    <div class="mb-6 flex items-start justify-between gap-4 border-b border-gray-100 pb-5">
                        <div>
                            <p class="mb-1 text-sm text-gray-500">طلب {{ $plan->name }}</p>
                            <h1 class="mb-0 text-xl font-bold text-gray-900">الدفع الآمن</h1>
                        </div>
                        <span class="inline-flex items-center gap-1 rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700">
                            <i class="ti ti-lock"></i>
                            Moyasar
                        </span>
                    </div>

                    <div class="mb-6 flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3">
                        <span class="text-sm font-medium text-gray-600">إجمالي الفاتورة</span>
                        <span class="text-lg font-bold text-gray-900">{{ number_format($bill->amount, 2) }} <span class="icon-saudi_riyal text-sm"></span></span>
                    </div>

                    <x-payment.moyasar-form
                        :amount-in-halala="$amountInHalala"
                        :callback-url="$callbackUrl"
                        :description="$description"
                        :publishable-key="$publishableKey"
                    />

                    @if ($mode === 'test')
                        <p class="mt-5 mb-0 text-center text-xs text-gray-500">
                            أنت تستخدم بيئة الاختبار؛ استخدم بيانات البطاقة التجريبية المعتمدة من Moyasar فقط.
                        </p>
                    @endif
                </div>
            </div>
        </section>
    </main>
@endsection
