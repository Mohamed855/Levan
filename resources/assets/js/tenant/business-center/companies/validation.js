'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('#wizard-validation-form');
    const list = document.querySelector('#authorized-people-list');
    const addButton = document.querySelector('#btn-add-person');

    if (!form || !list || !addButton) return;

    const updateDefaultPeople = () => {
        const rows = Array.from(list.querySelectorAll('.authorized-person-item'));
        const selected = list.querySelector('.default-person-radio:checked');

        if (rows.length && !selected) {
            rows[0].querySelector('.default-person-radio').checked = true;
        }

        rows.forEach((row) => {
            const radio = row.querySelector('.default-person-radio');
            const hidden = row.querySelector('.is-default-input');
            radio.disabled = rows.length === 1;
            hidden.value = radio.checked ? '1' : '0';
        });
    };

    const rowIndex = () => list.querySelectorAll('.authorized-person-item').length;

    const buildRow = (index) => {
        const row = document.createElement('div');
        row.className = 'authorized-person-item mb-3 p-3 border rounded';
        row.innerHTML = `
            <div class="row g-4">
                <div class="col-md-3"><label class="form-label small">اسم المفوّض</label><input class="form-control" name="authorized_people[${index}][name]" type="text"></div>
                <div class="col-md-2"><label class="form-label small">رقم الهاتف</label><input class="form-control phone-input" name="authorized_people[${index}][phone]" type="text" inputmode="numeric" maxlength="10"></div>
                <div class="col-md-3"><label class="form-label small">البريد الإلكتروني</label><input class="form-control" name="authorized_people[${index}][email]" type="email"></div>
                <div class="col-md-2"><label class="form-label small">المنصب</label><input class="form-control" name="authorized_people[${index}][position]" type="text"></div>
                <div class="col-md-1 d-flex align-items-center justify-content-center"><div class="form-check mt-4"><input class="form-check-input default-person-radio" type="radio" name="default_person" value="${index}"><label class="form-check-label small text-muted">افتراضي</label></div></div>
                <div class="col-md-1 d-flex align-items-end"><button class="btn btn-outline-danger btn-sm btn-remove-person w-100" type="button">حذف</button></div>
                <input class="is-default-input" name="authorized_people[${index}][is_default]" type="hidden" value="0">
            </div>`;

        return row;
    };

    addButton.addEventListener('click', () => {
        list.append(buildRow(rowIndex()));
        updateDefaultPeople();
    });

    list.addEventListener('click', (event) => {
        const removeButton = event.target.closest('.btn-remove-person');
        if (!removeButton) return;

        removeButton.closest('.authorized-person-item')?.remove();
        updateDefaultPeople();
    });

    list.addEventListener('change', updateDefaultPeople);
    list.addEventListener('input', (event) => {
        if (event.target.matches('.phone-input')) {
            event.target.value = event.target.value.replace(/\D/g, '').slice(0, 10);
        }
    });
    form.addEventListener('submit', updateDefaultPeople);

    updateDefaultPeople();
});
