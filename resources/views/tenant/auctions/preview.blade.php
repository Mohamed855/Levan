@extends('tenant.layouts.layoutMaster')

@section('title', 'معاينة مخرج المزاد — ' . ($output['template']['template_name'] ?? ''))

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <a class="btn btn-label-secondary" href="{{ tenant_route_name('auctions.show', $auction->id) }}">
        <i class="ti ti-arrow-right me-1"></i> العودة لتفاصيل المزاد
    </a>
    <span class="badge bg-label-primary fs-6 p-2">
        نوع المخرج: {{ match($type) { 'brochure' => 'الكتيب التسويقي', 'billboard' => 'اللوحة الإعلانية', 'social' => 'منشورات التواصل', default => $type } }}
    </span>
</div>

<div class="card shadow-sm">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-0">{{ $output['template']['template_name'] ?? 'القالب المعتمد' }}</h5>
            <small class="text-muted font-monospace">إصدار {{ $output['template']['version'] ?? 1 }}</small>
        </div>
    </div>
    <div class="card-body p-4 d-grid gap-4 bg-light">
        @if(isset($output['template']['schema']['pages']))
            @foreach($output['template']['schema']['pages'] as $page)
                <div class="mx-auto position-relative overflow-hidden border rounded shadow-sm bg-white"
                     style="width:min(100%, {{ $output['template']['schema']['canvas']['width'] ?? 794 }}px);
                            aspect-ratio:{{ ($output['template']['schema']['canvas']['width'] ?? 794) }}/{{ ($output['template']['schema']['canvas']['height'] ?? 1123) }};
                            background:{{ $page['background']['color'] ?? '#ffffff' }};
                            @if(!empty($page['background']['image'])) background-image:url('{{ Storage::disk('tenant_public')->url($page['background']['image']) }}'); background-size:cover; @endif">
                    @foreach($page['elements'] as $element)
                        @php
                            $style = $element['style'] ?? [];
                            $value = $element['type'] === 'text'
                                ? ($element['content'] ?? '')
                                : ($output['bindings'][$element['binding'] ?? ''] ?? '');
                        @endphp
                        <div class="position-absolute overflow-hidden"
                             style="left:{{ $element['x'] }}px;
                                    top:{{ $element['y'] }}px;
                                    width:{{ $element['width'] }}px;
                                    height:{{ $element['height'] }}px;
                                    color:{{ $style['color'] ?? '#173f35' }};
                                    background:{{ $style['background_color'] ?? 'transparent' }};
                                    font-size:{{ $style['font_size'] ?? 18 }}px;
                                    font-weight:{{ $style['font_weight'] ?? 'normal' }};
                                    text-align:{{ $style['text_align'] ?? 'right' }};
                                    border-radius:{{ $style['border_radius'] ?? 0 }}px;
                                    padding: 4px;">
                            @if($element['type'] === 'image' && filled($value))
                                <img src="{{ Storage::disk('tenant_public')->url($value) }}" alt="" class="w-100 h-100 object-fit-cover">
                            @elseif($element['type'] === 'image')
                                <span class="d-flex h-100 align-items-center justify-content-center bg-light text-muted small border rounded">صورة</span>
                            @else
                                {{ $value }}
                            @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        @else
            <div class="alert alert-warning mb-0" role="alert">
                لا يتوفر مخطط تصميم محفوظ لهذا القالب.
            </div>
        @endif
    </div>
</div>
@endsection
