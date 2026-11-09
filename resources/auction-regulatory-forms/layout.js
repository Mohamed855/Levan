/* Measure native table rows after the actual embedded fonts have loaded. */
window.regulatoryFormReady = false;
window.regulatoryFormError = null;

(async () => {
    try {
        await document.fonts.ready;
        await Promise.all([...document.images].map(image => image.decode()));

        const specifications = {
            properties: { height: 388.05, row: 24.253, capacity: 14 },
            agents: { height: 213.7, row: 42.75, capacity: 4 },
            closing: { height: 363.31, row: 19.642, capacity: 17 },
        };

        for (const [name, specification] of Object.entries(specifications)) {
            const tables = [...document.querySelectorAll(`table[data-table="${name}"]`)];
            if (!tables.length) continue;
            const originalPages = tables.map(table => table.closest('[data-form-page]'));
            const original = originalPages[0];
            const rows = tables.flatMap(table => [...table.tBodies[0].rows].filter(row => row.dataset.record === 'true'));
            const template = original.cloneNode(true);
            const probe = template.cloneNode(true);
            probe.style.position = 'absolute';
            probe.style.visibility = 'hidden';
            document.body.append(probe);
            const probeTable = probe.querySelector('.statement-table');
            const probeBody = probeTable.tBodies[0];
            probeBody.replaceChildren();
            const maximum = specification.height * 4 / 3;
            const groups = [];
            let group = [];
            for (const row of rows) {
                const measured = row.cloneNode(true);
                probeBody.append(measured);
                if (probeTable.getBoundingClientRect().height > maximum + 1) {
                    measured.remove();
                    if (!group.length) throw new Error(`A ${name} row cannot fit on a complete page.`);
                    groups.push(group);
                    group = [];
                    probeBody.replaceChildren(measured);
                    if (probeTable.getBoundingClientRect().height > maximum + 1) {
                        throw new Error(`A ${name} row cannot fit on a complete page.`);
                    }
                }
                group.push(row);
            }
            if (group.length || !groups.length) groups.push(group);
            probe.remove();

            for (const group of groups) {
                const sheet = template.cloneNode(true);
                const table = sheet.querySelector('.statement-table');
                const body = table.tBodies[0];
                const blank = body.rows[body.rows.length - 1].cloneNode(true);
                blank.dataset.record = 'false';
                for (const cell of blank.cells) cell.replaceChildren();
                body.replaceChildren(...group.map(row => row.cloneNode(true)));
                original.before(sheet);
                while (body.rows.length < specification.capacity && table.getBoundingClientRect().height + specification.row * 4 / 3 <= maximum + 1) {
                    body.append(blank.cloneNode(true));
                }
                if (table.getBoundingClientRect().height > maximum + 1) {
                    throw new Error(`The ${name} table exceeds its page area.`);
                }
            }
            for (const sheet of originalPages) sheet.remove();
        }

        const sheets = [...document.querySelectorAll('[data-form-page]')];
        for (const [index, sheet] of sheets.entries()) {
            const footer = sheet.querySelector('.page-number');
            if (footer) footer.textContent = `${index + 1}/${sheets.length}`;
            const bounds = sheet.getBoundingClientRect();
            for (const value of sheet.querySelectorAll('.data-value')) {
                if (!value.textContent.trim()) continue;
                if (value.scrollHeight > value.clientHeight + 2 || value.scrollWidth > value.clientWidth + 2) {
                    throw new Error(`Data overflow: a value does not fit in the ${sheet.dataset.formPage} form.`);
                }
                const range = document.createRange();
                range.selectNodeContents(value);
                const textBounds = range.getBoundingClientRect();
                if (textBounds.bottom > bounds.bottom + 1 || textBounds.top < bounds.top - 1) {
                    throw new Error('Data overflow: text exceeds the page.');
                }
            }
            for (const block of sheet.querySelectorAll('.signatures, .closing-repeat')) {
                if (block.getBoundingClientRect().bottom > bounds.bottom + 1) {
                    throw new Error('The signature area does not fit on the page.');
                }
            }
        }
    } catch (error) {
        window.regulatoryFormError = error.message;
    } finally {
        window.regulatoryFormReady = true;
    }
})();
