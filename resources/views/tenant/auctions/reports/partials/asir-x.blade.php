@include('tenant.auctions.reports.partials.asir-header')
<div class="asir-x-badge" aria-hidden="true"><span style="mask-image:url('{{ $content['x_icon_mask'] }}')"></span></div>
<main class="asir-marketing-main asir-x-main" data-asir-flow>
    @if (($content['values']['x_context'] ?? '') !== '')
        <section data-asir-section><p class="asir-context" data-asir-paragraph data-report-field="x_context">{{ $content['values']['x_context'] }}</p></section>
    @endif
    @if (!empty($content['channels']['x']['metrics']))
        <section class="asir-x-summary" data-asir-section data-asir-channel="x">
            @if (!empty($content['destinations']['x']))<a href="{{ $content['destinations']['x'] }}" data-link-role="x">@endif
            @if (isset($content['channels']['x']['metrics']['posts']))<bdi data-report-field="x_posts">{{ $content['channels']['x']['metrics']['posts']['value'] }}</bdi> تغريدات دعائية@endif
            @foreach ($content['channels']['x']['metrics'] as $key => $metric)
                @if ($key !== 'posts')<span>{{ in_array($key, ['views', 'impressions'], true) ? 'بمجمل مشاهدات' : $metric['label'] }}</span> <bdi data-report-field="x_{{ $key }}">{{ $metric['value'] }}</bdi>@endif
            @endforeach
            @if (!empty($content['destinations']['x']))</a>@endif
        </section>
    @endif
    @if ($content['image_items'] !== [])
        <section data-asir-section><div class="asir-x-grid" data-asir-grid>
            @foreach ($content['image_items'] as $slot => $image)
                @include('tenant.auctions.reports.partials.asir-image', ['image' => $image, 'frameClass' => 'asir-evidence-frame'.($slot === 6 ? ' asir-x-last' : '')])
            @endforeach
        </div></section>
    @endif
    @if (!empty($content['destinations']['x']) && empty($content['channels']['x']['metrics']))
        <section class="asir-link-section" data-asir-section>@include('tenant.auctions.reports.partials.asir-link', ['url' => $content['destinations']['x'], 'role' => 'x', 'label' => 'رابط الحملة على منصة X'])</section>
    @endif
</main>
