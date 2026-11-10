@if (!$hasValue)
    <span class="text-muted">-</span>
@elseif ($value['type'] === 'sensitive')
    <span class="badge bg-danger text-truncate">{{ $value['display'] }}</span>
@elseif ($value['type'] === 'file')
    <div class="d-flex justify-content-center">
        @if ($value['is_image'] && $value['url'])
            <x-ui.file-card :url="$value['url']" :name="$value['display']" :isImage="true" />
        @else
            <x-ui.file-card :url="$value['url']" :name="$value['display']" />
        @endif
    </div>
@elseif ($value['type'] === 'bool')
    <span class="badge {{ $value['badge'] }}">{{ $value['display'] }}</span>
@elseif ($value['type'] === 'array')
    <pre class="mb-0 text-start bg-light p-2 rounded small font-monospace"
        style="white-space: pre-wrap; font-size: 0.8rem; direction: ltr;">{{ $value['display'] }}</pre>
@elseif ($value['type'] === 'null')
    <span class="text-muted">-</span>
@else
    <span class="text-break">{{ $value['display'] }}</span>
@endif
