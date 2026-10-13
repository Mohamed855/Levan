@props([
    'supportLink' => null,
])

@php
    $configData = App\Helpers\Helpers::appClasses();
    $containerFooter = isset($configData['contentLayout']) && $configData['contentLayout'] === 'wide'
        ? 'container-fluid'
        : 'container-xxl';
@endphp

<!-- Footer-->
<footer class="content-footer footer bg-footer-theme no-print">
    <div class="{{ $containerFooter }}">
        <div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
            <div class="text-body text-center">
                جميع الحقوق محفوظة {{ date('Y') }} © . <a href="https://e-tec.sa" target="_blank" rel="noopener noreferrer" class="footer-link">إتمام لتقنية نظم المعلومات ❤️</a>
            </div>
            @if ($supportLink)
                <div class="d-none d-lg-inline-block">
                    <a href="{{ $supportLink }}" id="footer-support" class="footer-link d-none d-sm-inline-block">الدعم الفني</a>
                </div>
            @endif
        </div>
    </div>
</footer>
<!--/ Footer-->
