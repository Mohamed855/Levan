@props([
    'name',
    'label' => null,
    'options' => [],
    'enum' => null,
    'id' => null,
    'value' => null,
    'placeholder' => 'اختر خيارًا',
    'required' => false,
    'multiple' => false,
    'select2' => true,
    'help' => null,
    'col' => 'col-md-6',
    'append' => null,
    'controlName' => null,
    'wrapperId' => null,
    'inputGroup' => false,
    'selectAll' => null,
])

@php
    if ($enum) {
        $options = \App\Support\SelectOptions::fromEnum($enum);
    } elseif (! is_iterable($options)) {
        $options = [];
    }
@endphp

@php
    $errorsBag = $errors ?? view()->shared('errors') ?? new \Illuminate\Support\ViewErrorBag;
    $fieldId = $id ?: str_replace(['[', ']'], '-', $name);
    $fieldName = $multiple && ! str_ends_with($name, '[]') ? $name . '[]' : $name;
    $selectedValue = old($name, $value);
    $selectedValues = collect(is_array($selectedValue) ? $selectedValue : [$selectedValue])
        ->map(static fn ($item) => (string) $item);
    $errorId = str_replace(['.', '[', ']'], '-', $fieldId) . '-error';
    $hasError = $errorsBag->has($name);
    $isRequired = filter_var($required, FILTER_VALIDATE_BOOLEAN);
    $attributes = $attributes->merge(['aria-required' => $isRequired ? 'true' : 'false']);

    $hasSelectAll = $multiple && $select2 && ($selectAll !== false);
    $useInputGroup = $inputGroup || $hasSelectAll;

    $slotContent = isset($slot) ? $slot->toHtml() : '';
    $slotHasOptions = str_contains($slotContent, '<option');
    $slotHasSelectAll = str_contains($slotContent, 'btn-select-all') || str_contains($slotContent, 'js-select-all-trigger');

    $isAllSelected = false;
    if ($hasSelectAll && ! empty($options) && is_iterable($options)) {
        $optionKeys = collect($options)->map(function ($opt, $k) use ($enum, $options) {
            if ($enum) return (string) $k;
            if (is_object($opt)) return (string) ($opt->id ?? $opt->value ?? $k);
            if (is_array($opt)) return (string) ($opt['value'] ?? $opt['id'] ?? $k);
            return (string) (is_array($options) && array_is_list($options) ? $opt : $k);
        });
        $isAllSelected = $optionKeys->isNotEmpty() && $optionKeys->diff($selectedValues)->isEmpty();
    }

    $isCustomRegions = ($fieldId === 'operational_regions');
    $btnId = $isCustomRegions ? 'btn-select-all-regions' : 'btn-select-all-' . $fieldId;
    $spanId = $isCustomRegions ? 'select-all-regions-text' : 'select-all-text-' . $fieldId;
    $btnClasses = 'input-group-text cursor-pointer js-select-all-trigger btn-select-all' . ($isCustomRegions ? ' btn-select-all-regions' : '');
@endphp

<x-form.layout.field :name="$name" :label="$label" :id="$fieldId" :required="$required" :help="$help" :col="$col" :wrapper-id="$wrapperId">
    @if ($useInputGroup)
        <div @class([
            'input-group input-group-merge',
            'has-select2' => $select2,
            'is-invalid' => $hasError,
        ])>
            <select
                id="{{ $fieldId }}"
                @if ($controlName !== false) name="{{ $controlName ?? $fieldName }}" @endif
                @if ($multiple) multiple @endif
                @required($isRequired)
                data-form-control
                data-placeholder="{{ $placeholder }}"
                aria-describedby="{{ $errorId }}"
                aria-invalid="{{ $hasError ? 'true' : 'false' }}"
                {{ $attributes->class(['form-select', 'select2' => $select2, 'is-invalid' => $hasError]) }}
            >
                @if (! $multiple && empty($slotContent))
                    <option value=""></option>
                @endif
                @foreach ($options as $key => $option)
                    @php
                        if ($enum) {
                            $optionValue = $key;
                            $optionLabel = $option;
                            $optionDisabled = false;
                        } elseif (is_object($option)) {
                            $optionValue = $option->id ?? $option->value ?? $key;
                            $optionLabel = $option instanceof \Spatie\Permission\Models\Role
                                ? \App\Support\AccessLabels::role($option->name)
                                : ($option->name ?? $option->label ?? $option->title ?? (string) $optionValue);
                            $optionDisabled = (bool) ($option->disabled ?? false);
                        } elseif (is_array($option)) {
                            $optionValue = $option['value'] ?? $option['id'] ?? $key;
                            $optionLabel = $option['label'] ?? $option['name'] ?? $option['title'] ?? (string) $optionValue;
                            $optionDisabled = (bool) ($option['disabled'] ?? false);
                        } elseif (is_array($options) && array_is_list($options)) {
                            $optionValue = $option;
                            $optionLabel = $option;
                            $optionDisabled = false;
                        } else {
                            $optionValue = $key;
                            $optionLabel = $option;
                            $optionDisabled = false;
                        }
                    @endphp
                    <option value="{{ $optionValue }}" @selected($selectedValues->contains((string) $optionValue)) @disabled($optionDisabled)>
                        {{ $optionLabel }}
                    </option>
                @endforeach
                @if ($slotHasOptions)
                    {!! $slotContent !!}
                @endif
            </select>
            @if ($hasSelectAll && ! $slotHasSelectAll)
                <button type="button"
                    class="{{ $btnClasses }}"
                    id="{{ $btnId }}"
                    data-select-all-for="{{ $fieldId }}"
                    title="{{ $isAllSelected ? 'إلغاء تحديد الكل' : 'تحديد الكل' }}">
                    <i class="ti ti-check-double me-1 text-primary"></i>
                    <span id="{{ $spanId }}" class="js-select-all-label">{{ $isAllSelected ? 'إلغاء التحديد' : 'تحديد الكل' }}</span>
                </button>
            @endif
            @if (! $slotHasOptions)
                {!! $slotContent !!}
            @endif
        </div>
        {!! $append ?? '' !!}
    @else
        <select
            id="{{ $fieldId }}"
            @if ($controlName !== false) name="{{ $controlName ?? $fieldName }}" @endif
            @if ($multiple) multiple @endif
            @required($isRequired)
            data-form-control
            data-placeholder="{{ $placeholder }}"
            aria-describedby="{{ $errorId }}"
            aria-invalid="{{ $hasError ? 'true' : 'false' }}"
            {{ $attributes->class(['form-select', 'select2' => $select2, 'is-invalid' => $hasError]) }}
        >
            @if (! $multiple && empty($slotContent))
                <option value=""></option>
            @endif
            @foreach ($options as $key => $option)
                @php
                    if ($enum) {
                        $optionValue = $key;
                        $optionLabel = $option;
                        $optionDisabled = false;
                    } elseif (is_object($option)) {
                        $optionValue = $option->id ?? $option->value ?? $key;
                        $optionLabel = $option instanceof \Spatie\Permission\Models\Role
                            ? \App\Support\AccessLabels::role($option->name)
                            : ($option->name ?? $option->label ?? $option->title ?? (string) $optionValue);
                        $optionDisabled = (bool) ($option->disabled ?? false);
                    } elseif (is_array($option)) {
                        $optionValue = $option['value'] ?? $option['id'] ?? $key;
                        $optionLabel = $option['label'] ?? $option['name'] ?? $option['title'] ?? (string) $optionValue;
                        $optionDisabled = (bool) ($option['disabled'] ?? false);
                    } elseif (is_array($options) && array_is_list($options)) {
                        $optionValue = $option;
                        $optionLabel = $option;
                        $optionDisabled = false;
                    } else {
                        $optionValue = $key;
                        $optionLabel = $option;
                        $optionDisabled = false;
                    }
                @endphp
                <option value="{{ $optionValue }}" @selected($selectedValues->contains((string) $optionValue)) @disabled($optionDisabled)>
                    {{ $optionLabel }}
                </option>
            @endforeach
            {!! $slotContent !!}
        </select>
        {!! $append ?? '' !!}
    @endif
</x-form.layout.field>
