@extends('tenants.layouts.layoutMaster')
@section('title', 'حجز دومين جديد')

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/tenants/domains/register/search.css'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/tenants/domains/register/search.js'])
@endsection

@section('content')

<div class="ds-wrapper" dir="rtl">

    {{-- ═══════════════════════════════════
         Hero Section
    ═══════════════════════════════════ --}}
    <div class="ds-hero">
        <div class="ds-hero__grid-lines"></div>
        <div class="ds-hero__content">
            <span class="ds-hero__badge">
                <span class="ds-hero__badge-dot"></span>
                حجز دومين جديد
            </span>
            <h1 class="ds-hero__title">
                ابدأ رحلتك الرقمية<br>
                <span class="ds-hero__title-accent">بدومين مميز</span>
            </h1>
            <p class="ds-hero__subtitle">احجز دومينك الآن واحصل على هوية احترافية لموقعك أو متجرك الإلكتروني</p>
        </div>
    </div>

    {{-- ═══════════════════════════════════
         Search Box
    ═══════════════════════════════════ --}}
    <div class="ds-search-section">
        <div class="ds-search-card">
            <div class="ds-search-card__inner">

                <form id="domainSearchForm" class="ds-form" autocomplete="off">
                    @csrf
                    <div class="ds-form__group">
                        <div class="ds-form__input-wrap">

                            {{-- Search Icon --}}
                            <span class="ds-form__icon">
                                <svg style="color:white" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                                </svg>
                            </span>

                            {{-- Domain Name Input --}}
                            <input
                                type="text"
                                id="domainInput"
                                name="domain"
                                class="ds-form__input"
                                placeholder="اكتب اسم الدومين... مثال: mysite"
                                dir="ltr"
                                spellcheck="false"
                            >

                            {{-- Extension Select (مدمج في الشريط) --}}
                            <div class="ds-ext-select-wrapper">
                                <button type="button" class="ds-ext-select-header" id="dsExtHeader">
                                    <span id="dsExtSelectedText">الامتداد</span>
                                    <svg class="ds-ext-select-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <polyline points="6 9 12 15 18 9"/>
                                    </svg>
                                </button>

                                <div class="ds-ext-dropdown" id="dsExtDropdown">
                                    <div class="ds-ext-search">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                                        </svg>
                                        <input type="text" id="dsExtSearch" placeholder="بحث عن امتداد...">
                                    </div>
                                    <div class="ds-ext-options" id="dsExtOptions">
                                        @php
                                            $extensions = [];
                                            tenancy()->central(function () use (&$extensions) {
                                                $extensions = App\Models\Admin\ControlDomain\ControlDomain::orderBy('price', 'asc')->get();
                                            });
                                        @endphp
                                        @foreach ($extensions as $ext)
                                            <div class="ds-ext-option"
                                                 data-value="{{ $ext->domain_extension }}"
                                                 data-price="{{ $ext->price }}">
                                                <span class="ds-ext-option__name">{{ $ext->domain_extension }}</span>
                                                <span class="ds-ext-option__price">{{ number_format($ext->price, 0) }} ر.س</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <input type="hidden" id="dsExtHidden" name="extension" value="">
                            </div>

                            {{-- Search Button --}}
                            <button type="submit" class="ds-form__btn" id="searchBtn">
                                <span class="ds-form__btn-text">بحث</span>
                                <span class="ds-form__btn-loader" hidden>
                                    <svg class="ds-spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                                    </svg>
                                </span>
                            </button>
                        </div>

                        {{-- Input Error --}}
                        <div class="ds-form__error" id="inputError" hidden>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                            <span id="inputErrorMsg"></span>
                        </div>
                    </div>
                </form>

                {{-- Domain Preview --}}
                <div class="ds-domain-preview" id="dsDomainPreview">
                    <span class="ds-domain-preview__label">الدومين الكامل:</span>
                    <span class="ds-domain-preview__value" id="dsPreviewValue"></span>
                </div>

                {{-- TLD Quick Chips --}}
                <div class="ds-tlds">
                    <span class="ds-tlds__label">الامتدادات الشائعة:</span>
                    <div class="ds-tlds__chips">
                        <button type="button" class="ds-tld-chip" data-tld=".com">.com</button>
                        <button type="button" class="ds-tld-chip" data-tld=".net">.net</button>
                        <button type="button" class="ds-tld-chip" data-tld=".org">.org</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════
         Result Section
    ═══════════════════════════════════ --}}
    <div class="ds-result-section" id="resultSection" hidden>

        {{-- Available State --}}
        <div class="ds-result ds-result--available" id="resultAvailable" hidden>
            <div class="ds-result__icon-wrap ds-result__icon-wrap--available">
                <svg class="ds-result__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <div class="ds-result__icon-ring"></div>
            </div>
            <div class="ds-result__body" style="width:100%">
                <div class="ds-result__status ds-result__status--available">متاح للتسجيل 🎉</div>
                <div class="ds-result__domain" id="availableDomainName"></div>
                <p class="ds-result__desc">هذا الدومين متاح ويمكنك تسجيله الآن قبل أن يسبقك أحد!</p>

                {{-- Pricing Summary --}}
                <div class="ds-result__pricing">
                    <div class="ds-result__pricing-row">
                        <span class="ds-result__pricing-label">اسم الدومين</span>
                        <span class="ds-result__pricing-value" id="dsPricingDomain" style="direction:ltr"></span>
                    </div>
                    <div class="ds-result__pricing-row">
                        <span class="ds-result__pricing-label">السعر شامل الضريبة</span>
                        <span class="ds-result__pricing-value" id="dsPricingPrice"></span>
                    </div>
                    <div class="ds-result__pricing-row ds-result__pricing-total">
                        <span class="ds-result__pricing-label">الإجمالي</span>
                        <span class="ds-result__pricing-value" id="dsPricingTotal"></span>
                    </div>
                </div>
            </div>
            <div class="ds-result__action">
                <button type="button" class="ds-result__cta" id="registerBtn">
                    <span style="color:white">متابعة إلى الدفع</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </button>
                <span class="ds-result__tag">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    تسجيل آمن ومضمون
                </span>
            </div>
        </div>

        {{-- Taken State --}}
        <div class="ds-result ds-result--taken" id="resultTaken" hidden>
            <div class="ds-result__icon-wrap ds-result__icon-wrap--taken">
                <svg class="ds-result__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
                <div class="ds-result__icon-ring"></div>
            </div>
            <div class="ds-result__body">
                <div class="ds-result__status ds-result__status--taken">مسجل بالفعل</div>
                <div class="ds-result__domain" id="takenDomainName"></div>
                <p class="ds-result__desc">هذا الدومين محجوز. جرّب اسمًا مختلفًا أو امتدادًا آخر.</p>
            </div>
            <div class="ds-result__action">
                <button type="button" class="ds-result__cta ds-result__cta--secondary" id="tryAgainBtn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="1 4 1 10 7 10"/>
                        <path d="M3.51 15a9 9 0 1 0 .49-3.6"/>
                    </svg>
                    <span>جرّب اسمًا آخر</span>
                </button>
            </div>
        </div>

        {{-- Error State --}}
        <div class="ds-result ds-result--error" id="resultError" hidden>
            <div class="ds-result__icon-wrap ds-result__icon-wrap--error">
                <svg class="ds-result__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <div class="ds-result__body">
                <div class="ds-result__status ds-result__status--error">حدث خطأ</div>
                <div class="ds-result__domain" id="errorDomainName"></div>
                <p class="ds-result__desc" id="errorMessage">تعذّر التحقق من الدومين، يرجى المحاولة مرة أخرى.</p>
            </div>
        </div>

    </div>

    {{-- ═══════════════════════════════════
         Suggestions Section
    ═══════════════════════════════════ --}}
    <div class="ds-suggestions-section" id="suggestionsSection" hidden>
        <h3 class="ds-suggestions__title">اقتراحات مشابهة</h3>
        <div class="ds-suggestions__grid" id="suggestionsGrid"></div>
    </div>

</div>

{{-- URLs for JS --}}
<script>
    const CHECK_URL   = "{{ tenant_route_name('domains.check', ['domain' => '__DOMAIN__']) }}";
    const SUGGEST_URL = "{{ tenant_route_name('domains.suggest-alternatives') }}";
    const PAYMENT_URL = "{{ tenant_route_name('domains.payment.new') }}";
</script>

{{-- Hidden Payment Form --}}
<form id="paymentForm"
      method="POST"
      action="{{ tenant_route_name('domains.payment.new') }}"
      hidden>
    @csrf
    <input type="hidden" name="domainname"  id="paymentDomainInput">
    <input type="hidden" name="price"       id="paymentPrice">
    <input type="hidden" name="base_price"  id="paymentBasePrice">
    <input type="hidden" name="total"       id="paymentTotal">
</form>

@endsection