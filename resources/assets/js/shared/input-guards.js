const phoneNamePattern = /(?:^|[_\[])(?:phone|phone_number)(?:$|\])/i;
const allowedNavigationKeys = new Set([
    'Backspace', 'Delete', 'Tab', 'Enter', 'ArrowLeft', 'ArrowRight', 'Home', 'End',
]);

function isPhoneInput(input) {
    return input instanceof HTMLInputElement
        && input.type !== 'hidden'
        && (input.dataset.phoneFormat || input.type === 'tel' || phoneNamePattern.test(input.name));
}

function isDigitsOnlyInput(input) {
    return input instanceof HTMLInputElement && input.dataset.digitsOnly !== undefined;
}

function getPhoneFormat(input) {
    if (input.dataset.phoneFormat) {
        return input.dataset.phoneFormat;
    }

    const match = input.name.match(/^(phones\[\d+\])\[phone\]$/);
    if (match) {
        const typeInput = input.form?.querySelector(`[name="${match[1]}[type]"]`);

        return typeInput?.value === 'home' ? 'landline' : 'mobile';
    }

    return 'mobile';
}

function allowsDecimal(input) {
    const step = input.getAttribute('step');

    return step && step !== '1' && step !== 'any' && Number.parseFloat(step) % 1 !== 0;
}

function convertArabicNumerals(value) {
    if (typeof value !== 'string') return '';
    return value.replace(/[٠-٩]/g, (d) => String(d.charCodeAt(0) - 1632));
}

function sanitizeNumber(value, canUseDecimal) {
    const converted = convertArabicNumerals(value);
    const digitsAndDots = converted.replace(/[^0-9.]/g, '');

    if (!canUseDecimal) {
        return digitsAndDots.replace(/\./g, '');
    }

    const [integerPart, ...decimalParts] = digitsAndDots.split('.');

    return decimalParts.length ? `${integerPart}.${decimalParts.join('')}` : integerPart;
}

function configurePhoneInput(input) {
    if (!isPhoneInput(input)) {
        return;
    }

    const format = getPhoneFormat(input);

    input.inputMode = 'numeric';
    input.autocomplete = format === 'mobile' ? 'tel-national' : 'tel';

    if (format === 'mobile') {
        input.maxLength = 10;
        input.pattern = '05[0-9]{8}';
        input.placeholder ||= '05xxxxxxxx';
    } else {
        input.removeAttribute('maxlength');
        input.removeAttribute('pattern');
    }
}

function sanitizeInput(input) {
    if (isPhoneInput(input)) {
        configurePhoneInput(input);
        const maxLength = getPhoneFormat(input) === 'mobile' ? 10 : Number.POSITIVE_INFINITY;
        const converted = convertArabicNumerals(input.value);
        const sanitized = converted.replace(/[^0-9]/g, '').slice(0, maxLength);

        if (input.value !== sanitized) {
            input.value = sanitized;
        }

        return;
    }

    if (isDigitsOnlyInput(input)) {
        const converted = convertArabicNumerals(input.value);
        const maxLength = input.maxLength > 0 ? input.maxLength : Number.POSITIVE_INFINITY;
        const sanitized = converted.replace(/[^0-9]/g, '').slice(0, maxLength);

        if (input.value !== sanitized) {
            input.value = sanitized;
        }

        return;
    }

    if (input.type === 'number') {
        const sanitized = sanitizeNumber(input.value, allowsDecimal(input));

        if (input.value !== sanitized) {
            input.value = sanitized;
        }
    }
}

function configureExistingInputs() {
    document.querySelectorAll('input').forEach((input) => {
        if (isPhoneInput(input)) {
            configurePhoneInput(input);
            sanitizeInput(input);
        } else if (isDigitsOnlyInput(input)) {
            sanitizeInput(input);
        }
    });
}

document.addEventListener('keydown', (event) => {
    const input = event.target;

    if (!(input instanceof HTMLInputElement) || event.ctrlKey || event.metaKey || event.altKey) {
        return;
    }

    if (!isPhoneInput(input) && !isDigitsOnlyInput(input) && input.type !== 'number') {
        return;
    }

    if (allowedNavigationKeys.has(event.key)) {
        return;
    }

    const canUseDecimal = !isPhoneInput(input) && !isDigitsOnlyInput(input) && allowsDecimal(input);
    const canTypeDecimal = event.key === '.' && canUseDecimal && !input.value.includes('.');

    if (!/^[0-9٠-٩]$/.test(event.key) && !canTypeDecimal) {
        event.preventDefault();
    }
});

document.addEventListener('paste', (event) => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement)) return;

    if (isDigitsOnlyInput(input) || isPhoneInput(input)) {
        event.preventDefault();
        const text = (event.clipboardData || window.clipboardData)?.getData('text') || '';
        const converted = convertArabicNumerals(text);
        const cleaned = converted.replace(/[^0-9]/g, '');

        const start = input.selectionStart ?? input.value.length;
        const end = input.selectionEnd ?? input.value.length;
        const current = input.value;
        const maxLen = input.maxLength > 0 ? input.maxLength : Number.POSITIVE_INFINITY;

        const nextValue = (current.slice(0, start) + cleaned + current.slice(end)).slice(0, maxLen);
        input.value = nextValue;
        const newCursorPos = Math.min(start + cleaned.length, maxLen);
        input.setSelectionRange(newCursorPos, newCursorPos);
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }
});

document.addEventListener('input', (event) => {
    if (event.target instanceof HTMLInputElement) {
        sanitizeInput(event.target);
    }
});

document.addEventListener('change', (event) => {
    const target = event.target;

    if (target instanceof HTMLSelectElement && /^phones\[\d+\]\[type\]$/.test(target.name)) {
        const phoneInput = target.form?.querySelector(`[name="${target.name.replace('[type]', '[phone]')}"]`);

        if (phoneInput) {
            configurePhoneInput(phoneInput);
            sanitizeInput(phoneInput);
        }
    }
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', configureExistingInputs, { once: true });
} else {
    configureExistingInputs();
}
