@if (!empty($url))
    <a class="asir-link" href="{{ $url }}" data-link-role="{{ $role }}" @if (in_array($role, ['brochure', 'platform', 'video'], true)) data-asir-reference-link @endif><span>{{ $label }}</span></a>
@endif
