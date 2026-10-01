@props([
    'name' => 'image',
    'label' => 'الصورة',
    'id' => null,
    'value' => null,
    'accept' => 'image/png,image/jpeg,image/jpg,image/gif',
    'hint' => 'JPG، PNG أو GIF.',
    'col' => 'col-12',
])

@php($fieldId = $id ?: $name)

<div class="{{ $col }}">
    <label class="form-label" for="{{ $fieldId }}">{{ $label }}</label>
    <div class="d-flex align-items-start align-items-sm-center gap-4 pb-4 border-bottom">
        <img src="{{ $value ?: asset('img/logo.svg') }}" alt="{{ $label }}" class="d-block w-px-100 h-px-100 rounded object-fit-cover"
            data-image-preview="{{ $fieldId }}">
        <div class="button-wrapper">
            <label for="{{ $fieldId }}" class="btn btn-primary mb-2" tabindex="0">
                <span class="d-none d-sm-block">تحديد صورة</span>
                <i class="ti ti-upload d-block d-sm-none" aria-hidden="true"></i>
            </label>
            <input id="{{ $fieldId }}" name="{{ $name }}" type="file" class="d-none" accept="{{ $accept }}"
                data-image-input="{{ $fieldId }}" {{ $attributes }}>
            @if ($hint)
                <div class="text-muted small">{{ $hint }}</div>
            @endif
        </div>
    </div>
    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

@once
    <script>
        document.addEventListener('change', function (event) {
            const input = event.target.closest('[data-image-input]');
            const file = input?.files?.[0];
            const preview = input ? document.querySelector(`[data-image-preview="${input.dataset.imageInput}"]`) : null;
            if (!file || !preview || !file.type.startsWith('image/')) return;
            const reader = new FileReader();
            reader.addEventListener('load', function () { preview.src = reader.result; });
            reader.readAsDataURL(file);
        });
    </script>
@endonce
