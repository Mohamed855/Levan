document.addEventListener('DOMContentLoaded', function () {
    // 1. 2FA OTP Code Inputs Handling
    const otpInputs = document.querySelectorAll('#codeInputsWrapper .auth-otp-input, #codeInputsWrapper .auth-input');
    const hiddenField = document.getElementById('otpHidden');

    if (otpInputs.length > 0 && hiddenField) {
        function updateHiddenField() {
            let value = '';
            otpInputs.forEach((input) => {
                value += input.value;
            });
            hiddenField.value = value;
            hiddenField.dispatchEvent(new Event('input', { bubbles: true }));
            hiddenField.dispatchEvent(new Event('change', { bubbles: true }));
        }

        otpInputs.forEach((input, index) => {
            input.addEventListener('input', function (e) {
                // Keep only first digit if multiple entered
                if (e.target.value.length > 1) {
                    e.target.value = e.target.value.slice(-1);
                }
                if (e.target.value.length === 1 && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
                updateHiddenField();
            });

            input.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });

            input.addEventListener('paste', function (e) {
                e.preventDefault();
                const pasted = (e.clipboardData || window.clipboardData).getData('text').trim();
                const digits = pasted.replace(/\D/g, '').split('');
                digits.forEach((digit, i) => {
                    if (otpInputs[i]) {
                        otpInputs[i].value = digit;
                    }
                });
                updateHiddenField();
                const nextIndex = Math.min(digits.length, otpInputs.length - 1);
                otpInputs[nextIndex]?.focus();
            });
        });
    }

    // 3. Toggle between OTP and Recovery Code in 2FA Challenge
    const toggleRecoveryLink = document.getElementById('toggleRecoveryLink');
    const codeWrapper = document.getElementById('codeInputsWrapper');
    const recoveryWrapper = document.getElementById('recoveryWrapper');
    const codeLabel = document.getElementById('codeLabel');

    if (toggleRecoveryLink && codeWrapper && recoveryWrapper) {
        let isRecoveryMode = !recoveryWrapper.classList.contains('d-none');
        toggleRecoveryLink.addEventListener('click', function (e) {
            e.preventDefault();
            isRecoveryMode = !isRecoveryMode;

            if (isRecoveryMode) {
                if (hiddenField) {
                    hiddenField.value = '';
                    hiddenField.dispatchEvent(new Event('input', { bubbles: true }));
                }
                codeWrapper.classList.add('d-none');
                recoveryWrapper.classList.remove('d-none');
                if (codeLabel) codeLabel.classList.add('d-none');
                toggleRecoveryLink.textContent = 'استخدام رمز المصادقة بدلاً من ذلك';
                document.getElementById('recovery_code')?.focus();
            } else {
                const recoveryInput = document.getElementById('recovery_code');
                if (recoveryInput) {
                    recoveryInput.value = '';
                    recoveryInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
                codeWrapper.classList.remove('d-none');
                recoveryWrapper.classList.add('d-none');
                if (codeLabel) codeLabel.classList.remove('d-none');
                toggleRecoveryLink.textContent = 'استخدام كود استرجاع بدلاً من ذلك';
                otpInputs[0]?.focus();
            }
        });
    }
});
