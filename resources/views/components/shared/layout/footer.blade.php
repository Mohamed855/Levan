@php
    $configData = App\Helpers\Helpers::appClasses();
    $containerFooter =
        isset($configData['contentLayout']) && $configData['contentLayout'] === 'wide'
            ? 'container-fluid'
            : 'container-xxl';
@endphp

<!-- Footer-->
<footer class="content-footer footer bg-footer-theme no-print">
    <div class="{{ $containerFooter }}">
        <div class="footer-container d-flex align-items-center justify-content-end py-3 flex-md-row flex-column">
            <div class="text-body text-center d-flex align-items-center justify-content-center gap-1 flex-wrap">
                <span>جميع الحقوق محفوظة {{ date('Y') }} © .</span>
                <a href="https://e-tec.sa" target="_blank" rel="noopener noreferrer"
                    class="footer-link d-inline-flex align-items-center gap-1">
                    <span>إتمام لتقنية نظم المعلومات</span>
                    <img src="{{ asset('static/brand/logos/dark-logo-footer.png') }}" alt="إتمام لتقنية نظم المعلومات"
                        class="footer-logo"
                        style="height: 18px; width: auto; object-fit: contain; vertical-align: middle;">
                </a>
            </div>
        </div>
    </div>
</footer>
<!--/ Footer-->
