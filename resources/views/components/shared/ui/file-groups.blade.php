@props([
    'groups' => [],
    'emptyText' => 'لا توجد مرفقات.',
    'scrollHeight' => null,
    'layout' => 'card',
])

@php
    $nonEmptyGroups = collect($groups)
        ->filter(fn ($group) => !empty($group['items']) && count($group['items']) > 0)
        ->map(function (array $group): array {
            $group['items'] = collect($group['items'])->values()->all();
            return $group;
        })
        ->values();

    $singleFileGroups = $nonEmptyGroups->filter(fn ($group) => count($group['items']) === 1)->values();
    $multiFileGroups = $nonEmptyGroups->filter(fn ($group) => count($group['items']) > 1)->values();

    $mapFileCardProps = static function ($item): array {
        if (!is_array($item)) {
            return [
                'file' => $item,
                'name' => null,
                'url' => null,
                'downloadUrl' => null,
                'size' => null,
                'meta' => null,
                'extension' => null,
                'isImage' => null,
            ];
        }

        return [
            'file' => $item['file'] ?? null,
            'name' => $item['name'] ?? null,
            'url' => $item['url'] ?? null,
            'downloadUrl' => $item['downloadUrl'] ?? ($item['download_url'] ?? null),
            'size' => $item['size'] ?? null,
            'meta' => $item['meta'] ?? null,
            'extension' => $item['extension'] ?? null,
            'isImage' => $item['isImage'] ?? null,
        ];
    };
@endphp

@if ($nonEmptyGroups->isEmpty())
    <div class="card-body">
        <p class="text-muted text-center mb-0 small">{{ $emptyText }}</p>
    </div>
@else
    <div class="card-body app-file-scroll-box" @if ($scrollHeight) style="max-height: {{ $scrollHeight }};" @endif>
        @if ($singleFileGroups->isNotEmpty())
            <div class="row g-3 app-file-single-grid {{ $multiFileGroups->isNotEmpty() ? 'mb-4' : '' }}">
                @foreach ($singleFileGroups as $group)
                    @php($card = $mapFileCardProps($group['items'][0]))
                    <div class="col-md-6">
                        <div class="app-file-group app-file-group--single">
                            <div class="app-file-group__header bg-label-primary text-primary d-flex align-items-center">
                                <span class="app-file-group__title">{{ $group['label'] }}</span>
                            </div>
                            <x-ui.file-card
                                :file="$card['file']"
                                :name="$card['name']"
                                :url="$card['url']"
                                :downloadUrl="$card['downloadUrl']"
                                :size="$card['size']"
                                :meta="$card['meta']"
                                :extension="$card['extension']"
                                :isImage="$card['isImage']"
                                :layout="$layout"
                                class="app-file-card--wide"
                            />
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @foreach ($multiFileGroups as $group)
            <div class="app-file-group">
                <div class="app-file-group__header bg-label-primary text-primary d-flex align-items-center justify-content-between">
                    <span class="app-file-group__title">{{ $group['label'] }}</span>
                    <span class="badge rounded-pill app-file-group__count">{{ count($group['items']) }}</span>
                </div>
                <div class="assignment-file-list">
                    @foreach ($group['items'] as $item)
                        @php($card = $mapFileCardProps($item))
                        <x-ui.file-card
                            :file="$card['file']"
                            :name="$card['name']"
                            :url="$card['url']"
                            :downloadUrl="$card['downloadUrl']"
                            :size="$card['size']"
                            :meta="$card['meta']"
                            :extension="$card['extension']"
                            :isImage="$card['isImage']"
                            :layout="$layout"
                        />
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif
