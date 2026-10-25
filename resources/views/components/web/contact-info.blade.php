@php
    $officeAddress = \App\Helpers\Admin\Settings\AdminSettingsHelper::get('office_address');
    $phoneNumber = \App\Helpers\Admin\Settings\AdminSettingsHelper::get('phone_number');
    $email = \App\Helpers\Admin\Settings\AdminSettingsHelper::get('email');
    $phoneLink = preg_replace('/[^0-9+]/', '', (string) $phoneNumber);
@endphp
<div {{ $attributes->merge(['class' => 'contact-info-list scroll-revealed']) }}>
    {{-- الهاتف والبريد --}}
    <div class="contact-info-card">
        <div class="info-icon-wrap">
            <i class="ti ti-phone"></i>
        </div>
        <div class="info-body">
            <h4 class="info-label">تواصل معنا</h4>
            <p class="info-value">
                @if($phoneNumber)<a href="tel:{{ $phoneLink }}" dir="ltr">{{ $phoneNumber }}</a>@else — @endif
            </p>
            <p class="info-value">
                @if($email)<a href="mailto:{{ $email }}">{{ $email }}</a>@else — @endif
            </p>
        </div>
    </div>

    {{-- العنوان --}}
    <div class="contact-info-card">
        <div class="info-icon-wrap">
            <i class="ti ti-map-pin"></i>
        </div>
        <div class="info-body">
            <h4 class="info-label">العنوان</h4>
            <p class="info-value">
                {{ $officeAddress ?: '—' }}
            </p>
        </div>
    </div>

    {{-- ساعات العمل --}}
    <div class="contact-info-card">
        <div class="info-icon-wrap">
            <i class="ti ti-alarm"></i>
        </div>
        <div class="info-body">
            <h4 class="info-label">ساعات العمل</h4>
            <p class="info-value">متاح 24 ساعة / 7 أيام أسبوعياً</p>
            <p class="info-value">ساعات المكتب: 10:00 ص - 6:00 م</p>
        </div>
    </div>
</div>
