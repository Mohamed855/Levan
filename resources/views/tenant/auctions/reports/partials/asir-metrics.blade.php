@if (!empty($content['channels'][$channel]['metrics']))
    <section class="asir-metrics-section" data-asir-section data-asir-channel="{{ $channel }}">
        <dl class="asir-metrics">
            @foreach ($content['channels'][$channel]['metrics'] as $key => $metric)
                <div><dt>{{ $metric['label'] }}</dt><dd data-report-field="{{ $channel }}_{{ $key }}"><bdi>{{ $metric['value'] }}</bdi></dd></div>
            @endforeach
        </dl>
    </section>
@endif
