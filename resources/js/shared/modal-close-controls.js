function ensureModalCloseControl(modal) {
    modal.querySelectorAll('.btn-close').forEach((button) => button.remove());
    modal.querySelectorAll('.modal-body').forEach((body) => body.classList.add('my-3'));

    if (modal.querySelector('[data-bs-dismiss="modal"]')) {
        return;
    }

    const modalContent = modal.querySelector('.modal-content');
    if (!modalContent) {
        return;
    }

    let modalFooter = modalContent.querySelector('.modal-footer');
    if (!modalFooter) {
        modalFooter = document.createElement('div');
        modalFooter.className = 'modal-footer';
        modalContent.append(modalFooter);
    }

    const closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'btn btn-secondary';
    closeButton.dataset.bsDismiss = 'modal';
    closeButton.textContent = 'إغلاق';
    modalFooter.append(closeButton);
}

function normalizeModalCloseControls(root) {
    if (root instanceof Element && root.matches('.modal')) {
        ensureModalCloseControl(root);
    }

    if (root instanceof Element || root instanceof Document) {
        root.querySelectorAll('.modal').forEach(ensureModalCloseControl);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    normalizeModalCloseControls(document);

    new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                normalizeModalCloseControls(node);

                if (node instanceof Element) {
                    const parentModal = node.closest('.modal');
                    if (parentModal) {
                        ensureModalCloseControl(parentModal);
                    }
                }
            });
        });
    }).observe(document.body, { childList: true, subtree: true });
});
