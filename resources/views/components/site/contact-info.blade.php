<div {{ $attributes->merge(['class' => 'contact-info-list scroll-revealed']) }}>
    {{-- الهاتف والبريد --}}
    <div class="contact-info-card">
        <div class="info-icon-wrap">
            <i class="ti ti-phone"></i>
        </div>
        <div class="info-body">
            <h4 class="info-label">تواصل معنا</h4>
            <p class="info-value">
                <a href="tel:0500181100" dir="ltr">0500181100</a>
            </p>
            <p class="info-value">
                <a href="mailto:info@e-tec.sa">info@e-tec.sa</a>
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
                الرياض، طريق الأمير محمد بن عبدالعزيز<br>
                حي السليمانية، 12242
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
