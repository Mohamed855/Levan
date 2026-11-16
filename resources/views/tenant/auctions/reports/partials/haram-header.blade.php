<header class="haram-index-header">
    <div class="haram-index-branding">
        @foreach (['issuing_entity_logo', 'company_logo'] as $role)
            <div class="haram-index-logo-frame haram-index-logo-frame--{{ $role }}">
            @if (str_starts_with($data['branding'][$role] ?? '', 'data:image/'))
                <img class="haram-index-logo" src="{{ $data['branding'][$role] }}" alt="" data-report-branding="{{ $role }}">
            @endif
            </div>
        @endforeach
    </div>
    <h1>{{ $content['page_title'] ?? 'مخرجات المزاد' }}</h1>
</header>
