document.addEventListener('DOMContentLoaded', function () {
    // Password visibility toggle handler
    document.addEventListener('click', function (event) {
        const toggle = event.target.closest('[data-password-toggle]');
        if (!toggle) return;

        const targetId = toggle.dataset.passwordToggle;
        const input = document.getElementById(targetId);
        if (!input) return;

        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        toggle.setAttribute('aria-label', isPassword ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور');

        const icon = toggle.querySelector('i');
        if (icon) {
            icon.classList.toggle('ti-eye', isPassword);
            icon.classList.toggle('ti-eye-off', !isPassword);
        }
    });
});