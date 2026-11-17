@if ($content['company_logo'])
    <div class="asir-cover-logo asir-cover-logo--company {{ $content['cover_legacy_branding'] ? 'asir-cover-logo--legacy-company' : '' }}" data-asir-logo="company" data-logo-background="dark"><img src="{{ $content['company_logo'] }}" alt="شعار الشركة"></div>
@endif
<div class="asir-cover-contacts">
    <div class="asir-cover-phones">
        @foreach (['phone', 'unified_phone'] as $role)
            @if (isset($content['cover_contacts'][$role]))
                @include('tenant.auctions.reports.partials.asir-cover-contact', ['role' => $role, 'contact' => $content['cover_contacts'][$role]])
            @endif
        @endforeach
    </div>
    @if (isset($content['cover_contacts']['social']))
        @include('tenant.auctions.reports.partials.asir-cover-contact', ['role' => 'social', 'contact' => $content['cover_contacts']['social']])
    @endif
</div>
