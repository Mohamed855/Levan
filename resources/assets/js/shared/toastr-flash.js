import './toastr.js';

function displayFlashes() {
    const body = document.body;
    if (!body) return;
    const messages = [];
    for (const type of ['success', 'error', 'info', 'warning']) {
        const attribute = 'data-flash-' + type;
        const message = body.getAttribute(attribute);
        body.removeAttribute(attribute);
        if (message) messages.push({ type, message });
    }

    // A delete from a detail/static page needs navigation; carry its toast once.
    try {
        const key = 'delete-feedback:' + window.location.pathname;
        const message = window.sessionStorage.getItem(key);
        window.sessionStorage.removeItem(key);
        if (message && !messages.some((item) => item.type === 'success' && item.message === message)) {
            messages.push({ type: 'success', message });
        }
    } catch {
        // Session flashes still work when browser storage is disabled.
    }

    for (const { type, message } of messages) window.toastr[type](message);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', displayFlashes, { once: true });
} else {
    displayFlashes();
}
