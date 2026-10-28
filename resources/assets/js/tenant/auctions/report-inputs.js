export function fieldEntries(data, prefix = '') {
    return Object.entries(data || {}).flatMap(([key, value]) => {
        const name = prefix ? `${prefix}[${key}]` : key;
        return value !== null && typeof value === 'object'
            ? fieldEntries(value, name)
            : [[name, value]];
    });
}

export function nextRowIndex(indices) {
    return Math.max(-1, ...indices.map(Number).filter(Number.isInteger)) + 1;
}

export function fileIdentity(file) {
    return `${file.name}:${file.size}:${file.lastModified}`;
}

export function mountReportInputs(form) {
    let restoredResults = {};
    let previousFiles = [];
    const evidenceContainer = form.querySelector('#new-evidence-metadata');

    function cloneRow(templateId, index) {
        if (!/^\d+$/.test(String(index))) return null;
        const template = form.querySelector(`#${templateId}`);
        if (!template) return null;
        const holder = document.createElement('template');
        // Only an integer is substituted into trusted server-rendered markup.
        holder.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(index));
        return holder.content.firstElementChild;
    }

    function fill(data) {
        fieldEntries(data).forEach(([name, value]) => {
            const control = form.elements.namedItem(name);
            if (control && control.type !== 'file' && 'value' in control) {
                control.value = value ?? '';
            }
        });
    }

    function emptyStates() {
        ['campaigns', 'links'].forEach((name) => {
            const container = form.querySelector(`#${name}-container`);
            const selector = name === 'campaigns' ? '.campaign-row' : '.link-row';
            form.querySelector(`#${name}-empty`)?.classList.toggle('d-none', Boolean(container?.querySelector(selector)));
        });
    }

    [['campaign', 'campaigns'], ['link', 'links']].forEach(([row, plural]) => {
        form.querySelector(`#add-${row}-btn`)?.addEventListener('click', () => {
            const container = form.querySelector(`#${plural}-container`);
            const indices = [...container.querySelectorAll(`.${row}-row`)].map((element) => element.dataset.index);
            const element = cloneRow(`report-${row}-template`, nextRowIndex(indices));
            if (element) container.append(element);
            emptyStates();
            form.dispatchEvent(new Event('change', {bubbles: true}));
        });
    });

    form.addEventListener('click', (event) => {
        const button = event.target.closest('.remove-campaign, .remove-link');
        if (!button) return;
        button.closest('.campaign-row, .link-row')?.remove();
        emptyStates();
        form.dispatchEvent(new Event('change', {bubbles: true}));
    });

    const input = form.elements.namedItem('marketing_evidence[]');
    input?.addEventListener('change', () => {
        const saved = new Map(previousFiles.map((file, index) => {
            const values = {};
            evidenceContainer.querySelector(`[data-evidence-index="${index}"]`)?.querySelectorAll('[name]').forEach((control) => {
                values[control.name.slice(control.name.lastIndexOf('[') + 1, -1)] = control.value;
            });
            return [fileIdentity(file), values];
        }));
        const files = Array.from(input.files || []);
        evidenceContainer.replaceChildren();
        files.forEach((file, index) => {
            const element = cloneRow('report-evidence-template', index);
            element.querySelector('[data-evidence-name]').textContent = file.name;
            evidenceContainer.append(element);
            fill({ marketing_evidence_meta: { [index]: saved.get(fileIdentity(file)) || { display_order: index, kind: file.type === 'application/pdf' ? 'document' : 'screenshot' } } });
        });
        previousFiles = files;
    });

    function restore(data, files = {}) {
        restoredResults = data.property_results || {};
        [['campaign', 'campaigns'], ['link', 'links']].forEach(([row, plural]) => {
            const container = form.querySelector(`#${plural}-container`);
            container.querySelectorAll(`.${row}-row`).forEach((element) => element.remove());
            Object.keys(data.marketing?.[plural] || {}).forEach((index) => {
                const element = cloneRow(`report-${row}-template`, index);
                if (element) container.append(element);
            });
        });
        evidenceContainer.replaceChildren();
        const indices = new Set([...Object.keys(data.marketing_evidence_meta || {}), ...Object.keys(files.marketing_evidence || {})]);
        indices.forEach((index) => {
            const element = cloneRow('report-evidence-template', index);
            if (!element) return;
            element.querySelector('[data-evidence-name]').textContent = files.marketing_evidence?.[index]?.name || `مرفق المسودة ${Number(index) + 1}`;
            evidenceContainer.append(element);
        });
        fill(data);
        emptyStates();
    }

    emptyStates();
    return {
        restore,
        propertyCard(property) {
            const element = cloneRow('report-property-template', property.id);
            element.querySelector('[data-result-title]').textContent = `${property.name} — ${property.number || ''}`;
            return element;
        },
        restorePropertyResults() {
            Object.entries(restoredResults).forEach(([id, values]) => {
                if (!form.querySelector(`[data-property-result="${CSS.escape(id)}"]`)) return;
                fill({property_results: {[id]: values}});
                delete restoredResults[id];
            });
        },
    };
}
