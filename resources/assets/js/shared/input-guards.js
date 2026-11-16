const phoneNamePattern = /(?:^|[_\[])(?:phone|phone_number|mobile)(?:$|\])/i;
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

function isPriceInput(input) {
    return input instanceof HTMLInputElement
        && input.type !== 'hidden'
        && (input.dataset.priceInput === 'true' || input.dataset.priceInput !== undefined);
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

function formatPriceValue(value) {
    if (value === null || value === undefined || value === '') return '';
    const converted = convertArabicNumerals(String(value));
    const clean = converted.replace(/[^0-9.]/g, '');
    if (!clean) return '';

    const parts = clean.split('.');
    const integerRaw = parts[0];
    const decimalRaw = parts.length > 1 ? parts.slice(1).join('') : null;

    let integerFormatted = integerRaw.replace(/^0+(?=\d)/, '');
    if (integerFormatted === '' && integerRaw.length > 0) {
        integerFormatted = '0';
    }
    integerFormatted = integerFormatted.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

    if (decimalRaw !== null) {
        return integerFormatted + '.' + decimalRaw;
    }
    return integerFormatted;
}

function handlePriceInput(input) {
    const raw = input.value;
    const cursorPos = input.selectionStart ?? raw.length;

    const beforeCursor = raw.slice(0, cursorPos);
    const nonCommasBefore = convertArabicNumerals(beforeCursor).replace(/,/g, '').length;

    const formatted = formatPriceValue(raw);
    input.value = formatted;

    let newPos = 0;
    let nonCommaCount = 0;
    for (let i = 0; i < formatted.length; i++) {
        if (formatted[i] !== ',') {
            nonCommaCount++;
        }
        if (nonCommaCount === nonCommasBefore) {
            newPos = i + 1;
            break;
        }
    }

    if (nonCommasBefore === 0) {
        newPos = 0;
    }

    input.setSelectionRange(newPos, newPos);
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
        if (isPriceInput(input)) {
            if (input.value && !input.value.includes(',')) {
                input.value = formatPriceValue(input.value);
            }
        } else if (isPhoneInput(input)) {
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

    if (isPriceInput(input)) {
        if (allowedNavigationKeys.has(event.key)) {
            if (event.key === 'Backspace' && input.selectionStart === input.selectionEnd) {
                const pos = input.selectionStart;
                if (pos > 0 && input.value[pos - 1] === ',') {
                    event.preventDefault();
                    const before = input.value.slice(0, pos - 2);
                    const after = input.value.slice(pos);
                    input.value = before + after;
                    handlePriceInput(input);
                }
            }
            return;
        }

        const canTypeDecimal = event.key === '.' && !input.value.includes('.');
        if (!/^[0-9٠-٩]$/.test(event.key) && !canTypeDecimal) {
            event.preventDefault();
        }
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

    if (isPriceInput(input)) {
        event.preventDefault();
        const text = (event.clipboardData || window.clipboardData)?.getData('text') || '';
        const clean = convertArabicNumerals(text).replace(/[^0-9.]/g, '');
        const start = input.selectionStart ?? input.value.length;
        const end = input.selectionEnd ?? input.value.length;
        const current = input.value;
        const nextValue = current.slice(0, start) + clean + current.slice(end);
        input.value = nextValue;
        handlePriceInput(input);
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        return;
    }

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
        if (isPriceInput(event.target)) {
            handlePriceInput(event.target);
            return;
        }
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

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;

    form.querySelectorAll('input[data-price-input="true"]').forEach((input) => {
        if (input.value) {
            input.value = input.value.replace(/,/g, '');
        }
    });

    setTimeout(() => {
        form.querySelectorAll('input[data-price-input="true"]').forEach((input) => {
            if (input.value && !input.value.includes(',')) {
                input.value = formatPriceValue(input.value);
            }
        });
    }, 50);
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', configureExistingInputs, { once: true });
} else {
    configureExistingInputs();
}
