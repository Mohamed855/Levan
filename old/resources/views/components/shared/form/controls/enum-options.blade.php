@props([
    'enum',
    'selected' => null,
])

@php
    $selectedValues = collect(is_array($selected) ? $selected : [$selected])
        ->map(static fn ($value) => (string) $value);
@endphp

@foreach (\App\Support\SelectOptions::fromEnum($enum) as $optionValue => $optionLabel)
    @php($enumCase = $enum::from($optionValue))
    <option value="{{ $optionValue }}"
        @selected($selectedValues->contains((string) $optionValue))
        @disabled(method_exists($enumCase, 'isDisabled') && $enumCase->isDisabled())>
        {{ $optionLabel }}
    </option>
@endforeach
