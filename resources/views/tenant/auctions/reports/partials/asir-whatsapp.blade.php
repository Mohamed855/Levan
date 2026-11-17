@include('tenant.auctions.reports.partials.asir-header')
<main class="asir-marketing-main asir-whatsapp-main" data-asir-flow>
    @foreach (['whatsapp_summary', 'whatsapp_views_summary'] as $role)
        @if (($content['values'][$role] ?? '') !== '')
            <section data-asir-section><p class="asir-whatsapp-summary" data-asir-paragraph data-report-field="{{ $role }}">{{ $content['values'][$role] }}</p></section>
        @endif
    @endforeach
    @if (!empty($content['channels']['whatsapp']['metrics']) || $content['legacy'])
        <section class="asir-whatsapp-statistics" data-asir-section data-asir-channel="whatsapp">
            @if (($content['values']['whatsapp_date'] ?? '') !== '')<p class="asir-whatsapp-date">{{ $content['values']['whatsapp_date'] }}</p>@endif
            <div class="asir-whatsapp-data">
                @if ($content['whatsapp_chart'])
                    @php($stops = implode(',', array_map(static fn ($segment) => $segment['color'].' '.$segment['start'].'% '.$segment['end'].'%', $content['whatsapp_chart'])))
                    <div class="asir-whatsapp-chart" role="img" aria-label="إحصاءات تسليم رسائل واتساب" data-report-chart="whatsapp" style="background:conic-gradient({{ $stops }})">
                        @foreach ($content['whatsapp_chart'] as $state => $segment)<span class="asir-chart-value" data-chart-state="{{ $state }}" data-percent="{{ $segment['percent'] }}">{{ $state }}: {{ $segment['percent'] }}%</span>@endforeach
                    </div>
                @else
                    <p class="asir-empty-statistics" data-report-empty-statistics>لا تتوفر إحصاءات</p>
                @endif
            <table class="asir-table asir-whatsapp-table" data-asir-table="metrics"><tbody>
                @foreach ($content['channels']['whatsapp']['metrics'] ?? [] as $key => $metric)
                    <tr><th>{{ $metric['label'] }}</th><td data-report-field="whatsapp_{{ $key }}"><bdi>{{ $metric['value'] }}</bdi></td></tr>
                @endforeach
            </tbody></table>
            </div>
        </section>
    @endif
    @if ($content['image_items'] !== [])
        <section data-asir-section><div class="asir-whatsapp-grid" data-asir-grid>
            @foreach ($content['image_items'] as $image)
                @include('tenant.auctions.reports.partials.asir-image', ['image' => $image])
            @endforeach
        </div></section>
    @endif
    @if (!empty($content['destinations']['whatsapp']))
        <section class="asir-whatsapp-link" data-asir-section><a href="{{ $content['destinations']['whatsapp'] }}" data-link-role="whatsapp" aria-label="رابط حملة الواتساب"><span style="mask-image:url('{{ $content['whatsapp_icon'] }}')"></span></a></section>
    @endif
</main>
