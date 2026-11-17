<div class="asir-cover-contact {{ $role === 'social' ? 'asir-cover-contact--social' : '' }}" data-asir-contact="{{ $role }}">
    <img src="{{ $content['cover_icons'][$role === 'social' ? 'social' : 'phone'] }}" data-asir-contact-icon alt="">
    @if ($contact['url'])<a href="{{ $contact['url'] }}" data-link-role="{{ $role }}">@endif
    <span data-report-field="{{ $role }}" dir="ltr">{{ $contact['value'] }}</span>
    @if ($contact['url'])</a>@endif
</div>
