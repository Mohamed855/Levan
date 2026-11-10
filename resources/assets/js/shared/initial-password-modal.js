import { Modal } from 'bootstrap';

function showInitialPasswordModal() {
    const element = document.getElementById('initial-password-modal');
    if (!element) return;

    element.addEventListener('hide.bs.modal', (event) => event.preventDefault());
    Modal.getOrCreateInstance(element, { backdrop: 'static', keyboard: false }).show();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', showInitialPasswordModal);
} else {
    showInitialPasswordModal();
}
