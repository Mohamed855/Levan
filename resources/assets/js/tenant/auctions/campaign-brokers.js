import { initAllFileUploadZones } from '../../shared/file-upload.js';

document.querySelectorAll('[data-broker-repeater]').forEach((repeater) => {
    const rows = repeater.querySelector('[data-broker-rows]');
    const template = repeater.querySelector('[data-broker-template]');
    const addButton = repeater.querySelector('[data-add-broker]');
    const updateButtons = () => {
        const currentRows = rows.querySelectorAll('[data-broker-row]');
        addButton.disabled = currentRows.length >= 50;
        currentRows.forEach((row) => {
            row.querySelector('[data-remove-broker]').disabled = currentRows.length === 1;
        });
    };

    addButton.addEventListener('click', () => {
        if (rows.querySelectorAll('[data-broker-row]').length >= 50) return;
        const index = Number(repeater.dataset.nextIndex);
        repeater.dataset.nextIndex = String(index + 1);
        const key = `broker-${Date.now()}-${index}`;
        const fragment = template.content.cloneNode(true);
        fragment.querySelectorAll('*').forEach((element) => {
            Array.from(element.attributes).forEach((attribute) => {
                element.setAttribute(attribute.name, attribute.value.replaceAll('__INDEX__', String(index)).replaceAll('__KEY__', key));
            });
        });
        rows.append(fragment);
        initAllFileUploadZones(rows.lastElementChild);
        updateButtons();
        rows.lastElementChild.querySelector('input[type="text"]')?.focus();
    });

    rows.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-broker]');
        if (!button || rows.querySelectorAll('[data-broker-row]').length === 1) return;
        button.closest('[data-broker-row]').remove();
        updateButtons();
    });
    updateButtons();
});
