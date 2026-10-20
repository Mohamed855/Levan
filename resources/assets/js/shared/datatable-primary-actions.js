const primaryAddButtonSelector = '.dt-buttons .btn-add.btn-primary.btn-secondary';

function normalizePrimaryAddButtons(root = document) {
    const buttons = root.matches?.(primaryAddButtonSelector)
        ? [root]
        : root.querySelectorAll?.(primaryAddButtonSelector) ?? [];

    buttons.forEach((button) => {
        button.classList.remove('btn-secondary');
    });
}

function observePrimaryAddButtons() {
    normalizePrimaryAddButtons();

    new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    normalizePrimaryAddButtons(node);
                }
            });
        });
    }).observe(document.body, { childList: true, subtree: true });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', observePrimaryAddButtons, { once: true });
} else {
    observePrimaryAddButtons();
}
