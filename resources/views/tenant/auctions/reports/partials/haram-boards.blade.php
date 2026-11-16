@include('tenant.auctions.reports.partials.haram-header')
<main class="haram-native-main haram-boards-main" data-native-flow>
    @foreach (['board_before' => 'نماذج تركيب اللوحات', 'board_after' => 'فك اللوحات'] as $section => $label)
        @if (! empty($content['images'][$section]) || ($section === 'board_after' && filled($content['values']['removal_date'])))
            <section class="haram-index-section haram-native-board-section" data-native-section data-haram-board-section="{{ $section }}">
                <h2>{{ $section === 'board_after' && filled($content['values']['removal_date']) ? $content['values']['removal_date'] : $label }}</h2>
                @if (! empty($content['images'][$section]))
                    <div class="haram-native-board-grid" data-native-grid>
                        @foreach ($content['images'][$section] as $slot => $image)
                            <figure class="haram-native-board-frame {{ $section === 'board_before' && $slot >= 4 ? 'haram-native-board-frame--small' : '' }}">
                                @include('tenant.auctions.reports.partials.haram-image', ['image' => $image, 'label' => $label, 'imageClass' => ''])
                            </figure>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
    @endforeach
</main>
@include('tenant.auctions.reports.partials.haram-footer')
