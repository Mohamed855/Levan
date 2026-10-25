@php
    $schema = $schema ?? ($output['template']['schema'] ?? []);
    $bindings = $bindings ?? ($output['bindings'] ?? []);
    $canvas = $schema['canvas'] ?? ['width'=>794,'height'=>1123];
@endphp
@once
<style>{!! app(\App\Support\Auctions\AuctionFontRegistry::class)->cssFor(array_keys(config('auction-template-fonts.families', []))) !!}</style>
@endonce
<div class="auction-design-document d-grid gap-4">
@foreach(($schema['pages'] ?? []) as $page)
    @php($bg = $page['background'] ?? [])
    <div class="auction-design-page position-relative overflow-hidden border rounded shadow-sm"
         style="width:min(100%, {{ (int) $canvas['width'] }}px);aspect-ratio:{{ (int)$canvas['width'] }}/{{ (int)$canvas['height'] }};background-color:{{ $bg['color'] ?? '#ffffff' }};@if(!empty($bg['image']))background-image:url('{{ str_starts_with($bg['image'], 'auction_') ? Storage::disk('central_public')->url($bg['image']) : Storage::disk('tenant_public')->url($bg['image']) }}');background-size:{{ $bg['fit'] ?? 'cover' }};background-position:center;background-repeat:no-repeat;@endif">
        @foreach(($page['elements'] ?? []) as $element)
            @php
                $style = $element['style'] ?? [];
                $value = $element['type'] === 'text' ? ($element['content'] ?? '') : ($bindings[$element['binding'] ?? ''] ?? ($element['content'] ?? ''));
            @endphp
            <div class="position-absolute overflow-hidden" style="left:{{ $element['x'] }}px;top:{{ $element['y'] }}px;width:{{ $element['width'] }}px;height:{{ $element['height'] }}px;color:{{ $style['color'] ?? '#173f35' }};background:{{ $style['background_color'] ?? 'transparent' }};font-size:{{ $style['font_size'] ?? 18 }}px;font-weight:{{ $style['font_weight'] ?? 400 }};font-family:'{{ $style['font_family'] ?? 'Almarai' }}',sans-serif;line-height:{{ $style['line_height'] ?? 1.4 }};text-align:{{ $style['text_align'] ?? 'right' }};border-radius:{{ $style['border_radius'] ?? 0 }}px;padding:4px;">
                @if($element['type'] === 'image' && filled($value))
                    <img src="{{ str_starts_with((string)$value, 'auction_') ? Storage::disk('central_public')->url($value) : Storage::disk('tenant_public')->url($value) }}" alt="" class="w-100 h-100 object-fit-cover">
                @elseif($element['type'] === 'image')
                    <span class="d-flex h-100 align-items-center justify-content-center bg-light text-muted small border rounded">صورة</span>
                @else
                    {{ $value }}
                @endif
            </div>
        @endforeach
    </div>
@endforeach
</div>
