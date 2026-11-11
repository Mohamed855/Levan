@props([
    'groups' => [],
    'emptyText' => 'لا توجد مرفقات.',
    'scrollHeight' => null,
    'layout' => 'card',
])

@php
    $nonEmptyGroups = collect($groups)
        ->filter(fn ($group) => !empty($group['items']) && count($group['items']) > 0)
        ->values();
@endphp

@if ($nonEmptyGroups->isEmpty())
    <p class="text-muted text-center mb-0 small">{{ $emptyText }}</p>
@else
    <div class="app-file-scroll-box" @if ($scrollHeight) style="max-height: {{ $scrollHeight }};" @endif>
        @foreach ($nonEmptyGroups as $group)
            <div class="app-file-group {{ !$loop->last ? 'mb-3 pb-3 border-bottom' : '' }}">
                <div class="app-file-group__header d-flex align-items-center justify-content-between mb-2">
                    <span class="app-file-group__title fw-semibold small text-body d-flex align-items-center gap-1">
                        <i class="ti {{ $group['icon'] ?? 'ti-folder' }} text-primary"></i>
                        <span>{{ $group['label'] }}</span>
                    </span>
                    <span class="badge bg-label-secondary">{{ count($group['items']) }}</span>
                </div>
                <div class="assignment-file-list">
                    @foreach ($group['items'] as $item)
                        @if (is_array($item) && !isset($item['file']))
                            <x-ui.file-card
                                :name="$item['name'] ?? null"
                                :url="$item['url'] ?? null"
                                :downloadUrl="$item['downloadUrl'] ?? ($item['download_url'] ?? null)"
                                :size="$item['size'] ?? null"
                                :meta="$item['meta'] ?? null"
                                :extension="$item['extension'] ?? null"
                                :isImage="$item['isImage'] ?? null"
                                :layout="$layout"
                            />
                        @elseif (is_array($item) && isset($item['file']))
                            <x-ui.file-card
                                :file="$item['file']"
                                :name="$item['name'] ?? null"
                                :url="$item['url'] ?? null"
                                :downloadUrl="$item['downloadUrl'] ?? ($item['download_url'] ?? null)"
                                :meta="$item['meta'] ?? null"
                                :layout="$layout"
                            />
                        @else
                            <x-ui.file-card :file="$item" :layout="$layout" />
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif
