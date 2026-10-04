const phoneNamePattern = /(?:^|[_\[])(?:phone|phone_number)(?:$|\])/i;
const allowedNavigationKeys = new Set([
    'Backspace', 'Delete', 'Tab', 'Enter', 'ArrowLeft', 'ArrowRight', 'Home', 'End',
]);

function isPhoneInput(input) {
    return input instanceof HTMLInputElement
        && input.type !== 'hidden'
        && (input.dataset.phoneFormat || input.type === 'tel' || phoneNamePattern.test(input.name));
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

function sanitizeNumber(value, canUseDecimal) {
    const digitsAndDots = value.replace(/[^0-9.]/g, '');

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
        const sanitized = input.value.replace(/[^0-9]/g, '').slice(0, maxLength);

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
        }
    });
}

document.addEventListener('keydown', (event) => {
    const input = event.target;

    if (!(input instanceof HTMLInputElement) || event.ctrlKey || event.metaKey || event.altKey) {
        return;
    }

    if (!isPhoneInput(input) && input.type !== 'number') {
        return;
    }

    if (allowedNavigationKeys.has(event.key)) {
        return;
    }

    const canUseDecimal = !isPhoneInput(input) && allowsDecimal(input);
    const canTypeDecimal = event.key === '.' && canUseDecimal && !input.value.includes('.');

    if (!/^[0-9]$/.test(event.key) && !canTypeDecimal) {
        event.preventDefault();
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
