(async () => {
    window.asirReportReady = false;
    window.asirReportError = null;
    try {
        await document.fonts.ready;
        await Promise.all([...document.images].map(image => image.complete
            ? Promise.resolve()
            : new Promise((resolve, reject) => { image.onload = resolve; image.onerror = () => reject(new Error('An Asir report image could not load.')); })));

        // Keep thumbnail names inside the original blue frame at readable sizes.
        // A valid name can still be too long at 8pt; let that full name flow as
        // ordinary content rather than stretching or cropping the thumbnail.
        for (const name of document.querySelectorAll('.asir-platform-name strong')) {
            const frame = name.closest('.asir-native-page');
            const scale = frame.getBoundingClientRect().width / 595.276;
            let size = parseFloat(getComputedStyle(name).fontSize) * .75;
            const fitsName = () => name.getBoundingClientRect().height <= 72 * scale + .5
                && name.scrollWidth <= name.clientWidth + 1;
            while (!fitsName() && size > 8) {
                size = Math.max(8, size - .25);
                name.style.fontSize = `${size}pt`;
            }
            if (fitsName()) continue;
            const section = document.createElement('section');
            section.className = 'asir-thumbnail-name';
            section.setAttribute('data-asir-section', '');
            const heading = document.createElement('h3');
            heading.textContent = 'اسم المزاد';
            const paragraph = document.createElement('p');
            paragraph.setAttribute('data-asir-paragraph', '');
            paragraph.dataset.reportField = 'name';
            paragraph.textContent = name.textContent;
            section.append(heading, paragraph);
            name.closest('[data-asir-section]').before(section);
            name.remove();
        }

        // Reassemble only property pages. Campaign/evidence pages keep their own
        // payload context, even when consecutive pages use the same template.
        for (const family of ['asir-index', 'asir-sold-assets', 'asir-unsold-assets']) {
            const pages = [...document.querySelectorAll('.asir-native-page')].filter(page => page.dataset.pageType.replace(/-continuation$/, '') === family);
            if (pages.length < 2) continue;
            const first = pages[0];
            if (family === 'asir-sold-assets') {
                const body = first.querySelector('[data-asir-flow]');
                for (const page of pages.slice(1)) body.append(...page.querySelectorAll('[data-asir-property]'));
            } else {
                const selector = family === 'asir-index' ? '[data-asir-table="properties"] tbody' : '[data-asir-table="unsold"] tbody';
                const body = first.querySelector(selector);
                for (const page of pages.slice(1)) body.append(...page.querySelector(selector).rows);
            }
            pages.slice(1).forEach(page => page.remove());
        }

        for (const original of [...document.querySelectorAll('[data-asir-flow]')]) {
            let page = original.closest('.asir-native-page');
            const family = page.dataset.pageType.replace(/-continuation$/, '');
            const template = page.cloneNode(true);
            template.querySelector('[data-asir-flow]').replaceChildren();
            const sections = [...original.children];
            const repeated = sections.filter(node => node.hasAttribute('data-asir-repeat'));
            // Long index metadata is preserved on its first page. Repeating a
            // near-page-height description would leave no space for any rows.
            if (family === 'asir-index') {
                while (repeated.length > 1 && repeated.reduce((height, node) => height + node.getBoundingClientRect().height * .75, 0) > 300) {
                    repeated.shift().removeAttribute('data-asir-repeat');
                }
            }
            let body = original;
            body.replaceChildren();
            const fits = node => {
                const bounds = page.getBoundingClientRect();
                return node.getBoundingClientRect().bottom <= bounds.top + 825 * bounds.width / 595.276 + .3;
            };
            const nextPage = () => {
                const next = template.cloneNode(true);
                next.dataset.pageType = `${family}-continuation`;
                page.after(next);
                page = next;
                body = page.querySelector('[data-asir-flow]');
                repeated.forEach(section => body.append(section.cloneNode(true)));
            };
            const hasContent = () => [...body.children].some(node => !node.hasAttribute('data-asir-repeat'));

            const flowParagraph = section => {
                const characters = Array.from(section.querySelector('[data-asir-paragraph]').textContent);
                let offset = 0;
                while (offset < characters.length) {
                    const part = section.cloneNode(true);
                    const paragraph = part.querySelector('[data-asir-paragraph]');
                    body.append(part);
                    let low = 0;
                    let high = characters.length - offset;
                    while (low < high) {
                        const middle = Math.ceil((low + high) / 2);
                        paragraph.textContent = characters.slice(offset, offset + middle).join('');
                        if (fits(part)) low = middle;
                        else high = middle - 1;
                    }
                    if (!low) {
                        part.remove();
                        if (!hasContent()) throw new Error('An Asir paragraph has no printable space.');
                        nextPage();
                        continue;
                    }
                    if (offset + low < characters.length) {
                        const text = characters.slice(offset, offset + low).join('');
                        const boundary = text.search(/\s+\S*$/u);
                        if (boundary > text.length * .75) low = Array.from(text.slice(0, boundary + 1)).length;
                    }
                    paragraph.textContent = characters.slice(offset, offset + low).join('');
                    offset += low;
                    if (offset < characters.length) nextPage();
                }
            };

            const flowItems = (section, selector) => {
                const source = section.querySelector(selector);
                const items = [...source.children];
                const shell = section.cloneNode(true);
                shell.querySelector(selector).replaceChildren();
                let part = shell.cloneNode(true);
                let target = part.querySelector(selector);
                body.append(part);
                const nextPart = () => {
                    nextPage();
                    part = shell.cloneNode(true);
                    target = part.querySelector(selector);
                    body.append(part);
                };
                for (const item of items) {
                    target.append(item);
                    if (fits(part)) continue;
                    item.remove();
                    if (!target.children.length) part.remove();
                    if (hasContent()) nextPart();
                    else {
                        part = shell.cloneNode(true);
                        target = part.querySelector(selector);
                        body.append(part);
                    }
                    target.append(item);
                    if (fits(part)) continue;
                    if (item.tagName !== 'TR') {
                        const image = item.querySelector('[data-report-attachment]');
                        if (image && item.querySelector('figcaption')) {
                            const frame = page.getBoundingClientRect();
                            const scale = frame.width / 595.276;
                            const overflow = part.getBoundingClientRect().bottom - frame.top - 825 * scale;
                            const height = image.getBoundingClientRect().height;
                            // Keep full captions readable. Only unusually tall
                            // figures need a smaller contained image to fit.
                            const minimum = Math.min(height, 100 * scale);
                            image.style.height = `${Math.max(minimum, height - overflow - 1) / scale}pt`;
                            if (fits(part)) continue;
                        }
                        throw new Error('An Asir evidence item exceeds its printable page.');
                    }
                    // An unusually long table cell continues as row fragments. Its
                    // identifiers remain repeated, while its text is consumed once.
                    const cells = [...item.cells];
                    const longest = cells.reduce((best, cell) => cell.textContent.length > best.textContent.length ? cell : best);
                    const cellIndex = cells.indexOf(longest);
                    const text = Array.from(longest.textContent);
                    let offset = 0;
                    item.remove();
                    while (offset < text.length) {
                        const row = item.cloneNode(true);
                        target.append(row);
                        let low = 0;
                        let high = text.length - offset;
                        while (low < high) {
                            const middle = Math.ceil((low + high) / 2);
                            row.cells[cellIndex].textContent = text.slice(offset, offset + middle).join('');
                            if (fits(part)) low = middle;
                            else high = middle - 1;
                        }
                        if (!low) throw new Error('An Asir table row cannot fit its identifiers.');
                        row.cells[cellIndex].textContent = text.slice(offset, offset + low).join('');
                        row.dataset.asirFragment = String(offset);
                        offset += low;
                        if (offset < text.length) nextPart();
                    }
                }
            };

            for (const section of sections) {
                body.append(section);
                if (fits(section)) continue;
                section.remove();
                if (section.querySelector('[data-asir-table] tbody')) {
                    flowItems(section, '[data-asir-table] tbody');
                } else if (section.querySelector('[data-asir-grid]')) {
                    flowItems(section, '[data-asir-grid]');
                } else if (section.querySelector('[data-asir-paragraph]')) {
                    flowParagraph(section);
                } else {
                    if (hasContent()) nextPage();
                    body.append(section);
                    if (!fits(section)) throw new Error('An Asir report section exceeds its printable page.');
                }
            }
        }

        for (const page of document.querySelectorAll('.asir-native-page')) {
            const frame = page.getBoundingClientRect();
            for (const section of page.querySelectorAll('[data-asir-flow] > [data-asir-section]')) {
                const box = section.getBoundingClientRect();
                const table = section.querySelector('[data-asir-table]');
                const horizontalBox = table ? table.getBoundingClientRect() : box;
                // The original platform pill and video badge protrude from their
                // cards. They must remain inside the page, not inside the card.
                const protrudes = section.matches('.asir-platform-card,.asir-video-hero');
                const overflowsWidth = !table && !protrudes && section.scrollWidth > section.clientWidth + 2;
                if (protrudes && [...section.querySelectorAll('*')].some(node => {
                    const bounds = node.getBoundingClientRect();
                    return bounds.left < frame.left - 1 || bounds.right > frame.right + 1;
                })) throw new Error(`Asir content overflows ${page.dataset.pageType}.`);
                if (box.bottom > frame.top + 825 * frame.width / 595.276 + 1 || horizontalBox.left < frame.left - 1 || horizontalBox.right > frame.right + 1 || overflowsWidth) {
                    throw new Error(`Asir content overflows ${page.dataset.pageType}.`);
                }
            }
        }
        window.asirReportReady = true;
    } catch (error) {
        window.asirReportError = error.message;
        throw error;
    }
})();
