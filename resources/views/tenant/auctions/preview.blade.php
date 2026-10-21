@extends('layouts.dashboard')
@section('title', 'معاينة مخرج المزاد')
@section('dashboard-scope', 'tenant')
@section('content')
<div class="d-flex gap-2 mb-4"><a class="btn btn-label-secondary" href="{{ route('tenant.auctions.show',$auction) }}">العودة لتفاصيل المزاد</a></div>
<section class="card"><div class="card-header"><h5 class="mb-0">{{ $output['template']['template_name'] }} — {{ $type }}</h5></div><div class="card-body d-grid gap-4">
@foreach($output['template']['schema']['pages'] as $page)
<div class="mx-auto position-relative overflow-hidden border rounded shadow-sm" style="width:min(100%, {{ $output['template']['schema']['canvas']['width'] }}px); aspect-ratio:{{ $output['template']['schema']['canvas']['width'] }}/{{ $output['template']['schema']['canvas']['height'] }}; background:{{ $page['background']['color'] ?? '#fff' }}; @if(!empty($page['background']['image'])) background-image:url('{{ Storage::disk('public')->url($page['background']['image']) }}'); background-size:cover; @endif">
@foreach($page['elements'] as $element)
@php($style=$element['style'] ?? [])
@php($value=$element['type']==='text' ? ($element['content'] ?? '') : ($output['bindings'][$element['binding'] ?? ''] ?? ''))
<div class="position-absolute overflow-hidden" style="left:{{ $element['x'] }}px;top:{{ $element['y'] }}px;width:{{ $element['width'] }}px;height:{{ $element['height'] }}px;color:{{ $style['color'] ?? '#173f35' }};background:{{ $style['background_color'] ?? 'transparent' }};font-size:{{ $style['font_size'] ?? 18 }}px;font-weight:{{ $style['font_weight'] ?? 'normal' }};text-align:{{ $style['text_align'] ?? 'right' }};border-radius:{{ $style['border_radius'] ?? 0 }}px;">
@if($element['type']==='image' && filled($value))<img src="{{ Storage::disk('public')->url($value) }}" alt="" class="w-100 h-100 object-fit-cover">@elseif($element['type']==='image')<span class="d-flex h-100 align-items-center justify-content-center bg-light text-muted">صورة غير متاحة</span>@else{{ $value }}@endif
</div>
@endforeach
</div>
@endforeach
</div></section>
@endsection