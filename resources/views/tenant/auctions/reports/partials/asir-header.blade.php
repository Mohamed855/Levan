<header class="asir-header">
    <h1>{{ $content['page_title'] }}</h1>
</header>
@if ($content['section_title'] !== '')
    <div class="asir-section-heading">
        @if ($content['section_number'] !== '')
            <span class="asir-section-number" aria-hidden="true">{{ $content['section_number'] }}</span>
        @endif
        <h2>{{ $content['section_title'] }}</h2>
    </div>
@endif
