'use strict';

const switchSelector = '.js-table-status-toggle';

function notify(type, message) {
    if (typeof toastr !== 'undefined') {
        toastr[type](message);
    }
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || window.csrfToken;
}

function responseStatusValue(response) {
    const value = response?.is_active ?? response?.status ?? response?.new_status;

    if (typeof value === 'boolean') {
        return value;
    }

    if (typeof value === 'number') {
        return value === 1;
    }

    if (typeof value === 'string') {
        if (['active', '1', 'true'].includes(value.toLowerCase())) {
            return true;
        }

        if (['inactive', '0', 'false'].includes(value.toLowerCase())) {
            return false;
        }
    }

    return null;
}

function handleStatusToggle(event) {
    const toggle = event.target.closest(switchSelector);

    if (!toggle || toggle.disabled || toggle.dataset.loading === 'true') {
        return;
    }

    const url = toggle.dataset.url;
    const token = csrfToken();

    if (!url || !token) {
        toggle.checked = !toggle.checked;
        notify('error', !url ? 'رابط تحديث الحالة غير متوفر' : 'رمز الأمان مفقود');
        return;
    }

    toggle.dataset.loading = 'true';
    toggle.disabled = true;

    const status = toggle.checked
        ? (toggle.dataset.activeValue ?? 1)
        : (toggle.dataset.inactiveValue ?? 0);

    $.ajax({
        url,
        type: 'PATCH',
        headers: { 'X-CSRF-TOKEN': token },
        data: {
            _token: token,
            status,
            is_active: toggle.checked ? 1 : 0,
        },
        success(response) {
            if (response.success) {
                const canonicalStatus = responseStatusValue(response);
                if (canonicalStatus !== null) {
                    toggle.checked = canonicalStatus;
                }

                notify('success', response.message || 'تم تحديث الحالة بنجاح');
                toggle.dispatchEvent(new CustomEvent('table-status-toggled', {
                    bubbles: true,
                    detail: response,
                }));
                return;
            }

            toggle.checked = !toggle.checked;
            notify('error', response.message || 'تعذر تحديث الحالة');
        },
        error(xhr) {
            toggle.checked = !toggle.checked;
            notify('error', xhr.responseJSON?.message || 'حدث خطأ أثناء تحديث الحالة');
        },
        complete() {
            delete toggle.dataset.loading;
            toggle.disabled = false;
        },
    });
}

export function initializeTableStatusToggles() {
    if (document.documentElement.dataset.tableStatusTogglesInitialized === 'true') {
        return;
    }

    document.documentElement.dataset.tableStatusTogglesInitialized = 'true';
    document.addEventListener('change', handleStatusToggle);
}

initializeTableStatusToggles();
