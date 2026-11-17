@if ($content['issuing_logo'])
    <div class="asir-cover-logo asir-cover-logo--issuing {{ $content['cover_legacy_branding'] ? 'asir-cover-logo--legacy-issuing' : '' }}" data-asir-logo="issuing" data-logo-background="light"><img src="{{ $content['issuing_logo'] }}" alt="شعار جهة الإسناد"></div>
@endif
<div class="asir-cover-titles">
    <div class="asir-cover-name-box"><h1 data-report-field="name">{{ $content['values']['name'] }}</h1></div>
    <div class="asir-cover-subtitle-box"><p data-report-field="subtitle">{{ $content['values']['subtitle'] }}</p></div>
</div>
