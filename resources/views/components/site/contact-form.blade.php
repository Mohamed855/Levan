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
                <div class="col-12 col-md-6" data-form-field data-field-name="customer_name">
                    <div class="site-form-group">
                        <label class="site-label" for="contact_customer_name">
                            الاسم <span class="required-star">*</span>
                        </label>
                        <input type="text" id="contact_customer_name" name="customer_name" minlength="3"
                            data-form-control class="site-input" placeholder="أدخل اسمك الكامل" required />
                        <p class="invalid-feedback" data-form-error aria-live="polite" hidden></p>
                    </div>
                </div>

                <div class="col-12 col-md-6" data-form-field data-field-name="customer_email">
                    <div class="site-form-group">
                        <label class="site-label" for="contact_customer_email">
                            البريد الإلكتروني
                        </label>
                        <input type="email" id="contact_customer_email" name="customer_email" data-form-control
                            class="site-input" placeholder="example@domain.com" />
                        <p class="invalid-feedback" data-form-error aria-live="polite" hidden></p>
                    </div>
                </div>

                <div class="col-12" data-form-field data-field-name="message">
                    <div class="site-form-group">
                        <label class="site-label" for="contact_message">
                            الرسالة أو الاستفسار <span class="required-star">*</span>
                        </label>
                        <textarea id="contact_message" name="message" rows="5" minlength="10" data-form-control class="site-textarea"
                            placeholder="اكتب استفسارك أو رسالتك بالتفصيل هنا..." required></textarea>
                        <p class="invalid-feedback" data-form-error aria-live="polite" hidden></p>
                    </div>
                </div>
            @else
                {{-- Trial Fields: Name + Email + Phone --}}
                <div class="col-12" data-form-field data-field-name="customer_name">
                    <div class="site-form-group">
                        <label class="site-label" for="trial_customer_name">
                            الاسم الكامل <span class="required-star">*</span>
                        </label>
                        <input type="text" id="trial_customer_name" name="customer_name" data-form-control
                            class="site-input" placeholder="أدخل اسمك الكامل أو اسم جهة العمل" required />
                        <p class="invalid-feedback" data-form-error aria-live="polite" hidden></p>
                    </div>
                </div>

                <div class="col-12 col-md-6" data-form-field data-field-name="customer_email">
                    <div class="site-form-group">
                        <label class="site-label" for="trial_customer_email">
                            البريد الإلكتروني <span class="required-star">*</span>
                        </label>
                        <input type="email" id="trial_customer_email" name="customer_email" data-form-control
                            class="site-input" placeholder="name@company.com" required />
                        <p class="invalid-feedback" data-form-error aria-live="polite" hidden></p>
                    </div>
                </div>

                <div class="col-12 col-md-6" data-form-field data-field-name="customer_phone">
                    <div class="site-form-group">
                        <label class="site-label" for="trial_customer_phone">
                            رقم الهاتف <span class="required-star">*</span>
                        </label>
                        <input type="tel" id="trial_customer_phone" name="customer_phone" data-form-control
                            dir="ltr" data-phone-format="international" class="site-input text-end"
                            placeholder="05XXXXXXXX" required />
                        <p class="invalid-feedback" data-form-error aria-live="polite" hidden></p>
                    </div>
                </div>
            @endif

            {{-- reCAPTCHA v3 input --}}
            <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response" data-validation-skip>

            @error('recaptcha')
                <div class="col-12">
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                </div>
            @enderror

            {{-- Action Submit Button --}}
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
