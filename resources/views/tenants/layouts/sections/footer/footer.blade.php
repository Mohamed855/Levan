@php
    $containerFooter =
        isset($configData['contentLayout']) && $configData['contentLayout'] === 'compact'
            ? 'container-xxl'
            : 'container-fluid';
@endphp

<!-- Footer-->
<footer class="content-footer footer bg-footer-theme no-print">
    <div class="{{ $containerFooter }}">
        <div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column ">
            <div class="text-body text-center">
                جميع
                الحقوق محفوظة 2026 @ . <a href="https://e-tec.sa" target="_blank" class="footer-link"> إتمام لتقنية نظم
                    المعلومات ❤️ </a>
            </div>

        </div>
    </div>
</footer>
<!--/ Footer-->
