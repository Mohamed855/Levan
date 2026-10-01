@props([
    'name' => 'password',
    'label' => 'كلمة المرور',
    'id' => null,
    'placeholder' => null,
    'required' => false,
    'col' => 'col-sm-6',
])

@php($fieldId = $id ?: $name)

<div class="{{ $col }}">
    <label class="form-label" for="{{ $fieldId }}">
        {{ $label }}@if ($required)<span class="text-danger"> *</span>@endif
    </label>
    <div class="input-group input-group-merge">
        <input id="{{ $fieldId }}" name="{{ $name }}" type="password" autocomplete="new-password"
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif @required($required)
            {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>
        <button type="button" class="input-group-text cursor-pointer" data-password-toggle="{{ $fieldId }}" aria-label="إظهار كلمة المرور">
            <i class="ti ti-eye-off" aria-hidden="true"></i>
        </button>
    </div>
    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

@once
    <script>
        document.addEventListener('click', function (event) {
            const toggle = event.target.closest('[data-password-toggle]');
            if (!toggle) return;
            const input = document.getElementById(toggle.dataset.passwordToggle);
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            toggle.setAttribute('aria-label', isPassword ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور');
            toggle.querySelector('i')?.classList.toggle('ti-eye', isPassword);
            toggle.querySelector('i')?.classList.toggle('ti-eye-off', !isPassword);
        });
    </script>
@endonce
