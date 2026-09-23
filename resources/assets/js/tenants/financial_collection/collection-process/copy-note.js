/**
 * نسخ الملاحظة إلى الحافظة
 * @param {HTMLElement} button 
 * @param {string} noteText 
 */
async function copyNoteToClipboard(button, noteText) {
    try {
        await navigator.clipboard.writeText(noteText);

        const icon = button.querySelector('i');
        const originalClass = icon.className;

        icon.className = 'ti ti-check';
        button.style.backgroundColor = '#198754';
        button.style.borderColor = '#198754';
        icon.style.color = 'white';

        setTimeout(() => {
            icon.className = originalClass;
            button.style.backgroundColor = '#f8f9fa';
            button.style.borderColor = '#dee2e6';
            icon.style.color = '#6c757d';
        }, 2000);

        if (typeof toastr !== 'undefined') {
            toastr.success('تم نسخ الملاحظة بنجاح');
        }

    } catch (err) {
        console.warn('Clipboard API فشل، استخدام الطريقة القديمة:', err);

        try {
            const tempInput = document.createElement('textarea');
            tempInput.value = noteText;
            tempInput.style.position = 'absolute';
            tempInput.style.left = '-9999px';
            tempInput.style.top = '0';
            document.body.appendChild(tempInput);

            tempInput.select();
            tempInput.setSelectionRange(0, 99999);

            const successful = document.execCommand('copy');
            document.body.removeChild(tempInput);

            if (successful) {
                if (typeof toastr !== 'undefined') {
                    toastr.success('تم نسخ الملاحظة');
                }
            } else {
                throw new Error('فشل النسخ باستخدام execCommand');
            }

        } catch (fallbackErr) {
            console.error('فشل النسخ:', fallbackErr);

            if (typeof toastr !== 'undefined') {
                toastr.error('فشل نسخ الملاحظة، يرجى النسخ يدوياً');
            } else {
                alert('فشل نسخ الملاحظة، يرجى النسخ يدوياً');
            }
        }
    }
}

window.copyNoteToClipboard = copyNoteToClipboard;