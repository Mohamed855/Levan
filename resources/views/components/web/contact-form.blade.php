@props([
    'action',
    'validationUrl',
    'fields' => 'contact', // 'contact' or 'trial'
    'title' => null,
    'subtitle' => null,
    'desc' => null,
])

<div class="contact-form-container scroll-revealed">
    @if ($title || $subtitle || $desc)
        <div class="contact-form-header">
            @if ($subtitle)
                <span class="section-eyebrow">{{ $subtitle }}</span>
            @endif
            @if ($title)
                <h3 class="form-title">{{ $title }}</h3>
            @endif
            @if ($desc)
                <p class="form-subtitle">{{ $desc }}</p>
            @endif
        </div>
    @endif

    <form id="form" method="POST" action="{{ $action }}" autocomplete="off" data-form-validate
        data-validation-url="{{ $validationUrl }}" novalidate>
        @csrf

        <div class="row g-3">
            @if ($fields === 'contact')
                {{-- Contact Fields: Name + Email + Message --}}
                <x-form.controls.input id="contact_customer_name" name="customer_name" label="الاسم"
                    placeholder="أدخل اسمك الكامل" minlength="3" required col="col-12 col-md-6" />

                <x-form.controls.input id="contact_customer_email" name="customer_email" type="email"
                    label="البريد الإلكتروني" placeholder="example@domain.com" col="col-12 col-md-6" />

                <x-form.controls.textarea id="contact_message" name="message" label="الرسالة أو الاستفسار"
                    placeholder="اكتب استفسارك أو رسالتك بالتفصيل هنا..." rows="5" minlength="10" required
                    col="col-12" />
            @else
                {{-- Trial Fields: Name + Email + Phone --}}
                <x-form.controls.input id="trial_customer_name" name="customer_name" label="الاسم الكامل"
                    placeholder="أدخل اسمك الكامل أو اسم جهة العمل" required col="col-12" />

                <x-form.controls.input id="trial_customer_email" name="customer_email" type="email"
                    label="البريد الإلكتروني" placeholder="name@company.com" required col="col-12 col-md-6" />

                <x-form.controls.input id="trial_customer_phone" name="customer_phone" type="tel" label="رقم الهاتف"
                    placeholder="05XXXXXXXX" dir="ltr" data-phone-format="mobile" required col="col-12 col-md-6"
                    class="text-end" />
            @endif

            {{-- reCAPTCHA v3 input --}}
            <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response" data-validation-skip>

            @error('recaptcha')
                <div class="col-12">
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                </div>
            @enderror

            <div class="col-12 text-center pt-2">
                <button type="submit" id="submit-form" class="site-btn-submit w-100">
                    <span class="spinner-border hidden" role="status" aria-hidden="true"></span>
                    <span class="btn-text">
                        {{ $fields === 'trial' ? 'طلب تجربتك المجانية' : 'إرسال الرسالة' }}
                    </span>
                    <i class="ti ti-arrow-left"></i>
                </button>
            </div>
        </div>
    </form>
</div>
