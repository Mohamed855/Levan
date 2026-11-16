/* Measure the Haram overlays with the fonts and images used by the PDF. */
window.haramReportReady = false;
window.haramReportError = null;

(async () => {
    try {
        await document.fonts.ready;
        await Promise.all([...document.images].map(image => image.decode()));

        for (const cell of document.querySelectorAll('[data-native-fit]')) {
            const scale = cell.closest('.auction-print-page').getBoundingClientRect().width / 595.5;
            const style = getComputedStyle(cell);
            const availableWidth = Math.min(148 * scale, cell.clientWidth - parseFloat(style.paddingLeft) - parseFloat(style.paddingRight));
            const range = document.createRange();
            range.selectNodeContents(cell);
            cell.style.whiteSpace = 'nowrap';
            let size = 9.784;
            const minimum = size * .7;
            while (range.getBoundingClientRect().width > availableWidth && size > minimum) {
                size = Math.max(minimum, size - .25);
                cell.style.fontSize = `${size}pt`;
            }
            cell.style.whiteSpace = '';
        }

        const indexPages = [...document.querySelectorAll('.haram-native-index')];
        if (indexPages.length) {
            const original = indexPages[0];
            const template = original.cloneNode(true);
            const rows = indexPages.flatMap(page => [...page.querySelector('[data-native-property-table] tbody').rows]);
            for (const page of indexPages.slice(1)) page.remove();
            let current = original;
            let body = current.querySelector('[data-native-property-table] tbody');
            body.replaceChildren();
            const fits = row => {
                const frame = current.getBoundingClientRect();
                return row.getBoundingClientRect().bottom <= frame.top + 775 * frame.width / 595.5;
            };
            for (const row of rows) {
                body.append(row);
                if (body.rows.length <= 11 && fits(row)) continue;
                row.remove();
                if (!body.rows.length) throw new Error('A property row exceeds the available index page.');
                const next = template.cloneNode(true);
                next.dataset.pageType = 'haram-index-continuation';
                current.after(next);
                current = next;
                body = current.querySelector('[data-native-property-table] tbody');
                body.replaceChildren(row);
                if (!fits(row)) throw new Error('A property row exceeds the available index page.');
            }
        }

        // The server supplies conservative chunks. Reassemble results before measuring
        // actual row heights so empty slots never determine the number of PDF pages.
        const resultPages = [...document.querySelectorAll('.haram-native-results')];
        if (resultPages.length) {
            const first = resultPages[0];
            const body = first.querySelector('[data-native-flow]');
            const sections = [];
            const narrative = first.querySelector('.haram-result-narrative')?.cloneNode(true);
            if (narrative) {
                narrative.querySelector('[data-native-paragraph]').textContent = resultPages.map(page => [...page.querySelectorAll('[data-report-field="narrative"]')].map(node => node.textContent).join('')).join('');
                sections.push(narrative);
            }
            for (const type of ['sold', 'unsold']) {
                const source = resultPages.flatMap(page => [...page.querySelectorAll(`[data-result-section="${type}"]`)]);
                if (!source.length) continue;
                const section = source[0].cloneNode(true);
                section.querySelector('tbody').replaceChildren(...source.flatMap(section => [...section.querySelector('tbody').rows]));
                sections.push(section);
            }
            body.replaceChildren(...sections);
            resultPages.slice(1).forEach(page => page.remove());
        }

        for (const original of [...document.querySelectorAll('[data-native-flow]')]) {
            let page = original.closest('.auction-print-page');
            const template = page.cloneNode(true);
            template.querySelector('[data-native-flow]').replaceChildren();
            const family = page.dataset.pageType.replace(/-continuation$/, '');
            const sections = [...original.children];
            let body = original;
            body.replaceChildren();
            const fits = node => {
                const frame = page.getBoundingClientRect();
                return node.getBoundingClientRect().bottom <= frame.top + 790 * frame.width / 595.5 + .25;
            };
            const nextPage = () => {
                const next = template.cloneNode(true);
                next.dataset.pageType = family + '-continuation';
                page.after(next);
                page = next;
                body = page.querySelector('[data-native-flow]');
            };
            const flowItems = (section, selector) => {
                const container = section.querySelector(selector);
                const items = [...container.children];
                const shell = section.cloneNode(true);
                shell.querySelector(selector).replaceChildren();
                let part = shell.cloneNode(true);
                let target = part.querySelector(selector);
                body.append(part);
                for (const item of items) {
                    target.append(item);
                    if (fits(part)) continue;
                    item.remove();
                    if (!target.children.length) {
                        part.remove();
                        if (!body.children.length) throw new Error('A native report item exceeds a complete page.');
                    }
                    nextPage();
                    part = shell.cloneNode(true);
                    target = part.querySelector(selector);
                    target.replaceChildren(item);
                    body.append(part);
                    if (!fits(part)) throw new Error('A native report item exceeds a complete page.');
                }
            };
            const flowParagraph = section => {
                const text = Array.from(section.querySelector('[data-native-paragraph]').textContent);
                let offset = 0;
                while (offset < text.length) {
                    const part = section.cloneNode(true);
                    const paragraph = part.querySelector('[data-native-paragraph]');
                    paragraph.textContent = '';
                    body.append(part);
                    let low = 0;
                    let high = text.length - offset;
                    while (low < high) {
                        const middle = Math.ceil((low + high) / 2);
                        paragraph.textContent = text.slice(offset, offset + middle).join('');
                        if (fits(part)) low = middle;
                        else high = middle - 1;
                    }
                    if (!low) {
                        part.remove();
                        if (!body.children.length) throw new Error('A native report paragraph cannot fit.');
                        nextPage();
                        continue;
                    }
                    if (offset + low < text.length) {
                        const candidate = text.slice(offset, offset + low).join('');
                        const boundary = candidate.search(/\s+\S*$/u);
                        if (boundary > low * .75) low = Array.from(candidate.slice(0, boundary + 1)).length;
                    }
                    paragraph.textContent = text.slice(offset, offset + low).join('');
                    offset += low;
                    if (offset < text.length) nextPage();
                }
            };
            for (const section of sections) {
                body.append(section);
                if (fits(section)) continue;
                section.remove();
                if (section.querySelector('[data-native-result-table] tbody')) {
                    flowItems(section, '[data-native-result-table] tbody');
                } else if (section.querySelector('[data-native-grid]')) {
                    flowItems(section, '[data-native-grid]');
                } else if (section.querySelector('[data-native-paragraph]')) {
                    flowParagraph(section);
                } else {
                    if (body.children.length) nextPage();
                    body.append(section);
                    if (!fits(section)) {
                        section.remove();
                        if (section.querySelector('.haram-snapchat-details')) {
                            flowItems(section, '.haram-snapchat-details');
                        } else {
                            throw new Error('A native report section exceeds a complete page.');
                        }
                    }
                }
            }
        }

        for (const original of [...document.querySelectorAll('.auction-print-page')]) {
            const body = original.querySelector('.haram-body');
            if (!body) continue;
            const pageBounds = original.getBoundingClientRect();
            const scale = pageBounds.width / 595.5;
            const availableBottom = 775 * scale;
            const template = original.cloneNode(true);
            const sections = [...body.children];
            body.replaceChildren();
            let currentPage = original;
            let currentBody = body;
            for (const section of sections) {
                currentBody.append(section);
                if (section.getBoundingClientRect().bottom - currentPage.getBoundingClientRect().top <= availableBottom) continue;
                section.remove();
                if (!currentBody.children.length) throw new Error('A Haram section exceeds a complete page.');
                const next = template.cloneNode(true);
                next.dataset.pageType = original.dataset.pageType.replace(/-continuation$/, '') + '-continuation';
                const suffix = `_continued_${document.querySelectorAll('.auction-print-page').length}_`;
                for (const node of next.querySelectorAll('svg [id], svg [clip-path], svg [mask], svg [filter], svg use')) {
                    for (const attribute of [...node.attributes]) {
                        if (attribute.name === 'id') node.setAttribute('id', suffix + attribute.value);
                        else if (attribute.localName === 'href' && attribute.value.startsWith('#')) node.setAttributeNS(attribute.namespaceURI, attribute.name, '#' + suffix + attribute.value.slice(1));
                        else if (attribute.value.includes('url(#')) node.setAttribute(attribute.name, attribute.value.replaceAll('url(#', `url(#${suffix}`));
                    }
                }
                currentPage.after(next);
                currentPage = next;
                currentBody = next.querySelector('.haram-body');
                currentBody.replaceChildren(section);
                if (section.getBoundingClientRect().bottom - next.getBoundingClientRect().top > availableBottom) throw new Error('A Haram section exceeds a complete page.');
            }
        }

        for (const row of document.querySelectorAll('.haram-contact')) {
            const limit = row.parentElement.getBoundingClientRect().width;
            let size = 28;
            while (row.getBoundingClientRect().width > limit && size > 14) row.style.fontSize = `${--size}pt`;
            if (row.getBoundingClientRect().width > limit + 1) throw new Error('A Haram contact does not fit.');
        }

        document.querySelectorAll('.haram-index-page-number').forEach((number, index) => {
            number.textContent = String(index + 1);
        });

        for (const caption of document.querySelectorAll('.haram-index-footer-caption')) {
            const footer = caption.parentElement;
            const scale = footer.getBoundingClientRect().width / 595.5;
            const style = getComputedStyle(footer);
            const number = footer.querySelector('.haram-index-page-number');
            const maximumWidth = footer.clientWidth - parseFloat(style.paddingLeft) - parseFloat(style.paddingRight) - number.getBoundingClientRect().width - 20 * scale;
            const maximumHeight = footer.clientHeight - 8 * scale;
            let width = caption.getBoundingClientRect().width;
            while (caption.getBoundingClientRect().height > maximumHeight && width < maximumWidth) {
                width = Math.min(maximumWidth, width + 12 * scale);
                caption.style.width = `${width}px`;
            }
            const bounds = caption.getBoundingClientRect();
            const frame = footer.getBoundingClientRect();
            if (bounds.height > maximumHeight + 1 || bounds.left < frame.left || bounds.right > frame.right || caption.scrollWidth > caption.clientWidth + 2) {
                throw new Error('The index footer caption exceeds its printable area.');
            }
        }

        for (const page of document.querySelectorAll('.haram-native-index')) {
            const frame = page.getBoundingClientRect();
            for (const section of page.querySelectorAll('.haram-index-section')) {
                const bounds = section.getBoundingClientRect();
                if (bounds.left < frame.left - 1 || bounds.right > frame.right + 1 || bounds.bottom > frame.top + 775 * frame.width / 595.5) {
                    throw new Error('An index section exceeds its printable area.');
                }
            }
        }

        for (const section of document.querySelectorAll('[data-native-flow] > [data-native-section]')) {
            const frame = section.closest('.auction-print-page').getBoundingClientRect();
            const bounds = section.getBoundingClientRect();
            if (bounds.bottom > frame.top + 790 * frame.width / 595.5 + 1 || bounds.left < frame.left - 1 || bounds.right > frame.right + 1 || section.scrollWidth > section.clientWidth + 2) {
                throw new Error('Native Haram content exceeds its printable area.');
            }
        }

        for (const page of document.querySelectorAll('.auction-print-page')) {
            const bounds = page.getBoundingClientRect();
            const scale = bounds.width / 595.5;
            for (const element of page.querySelectorAll('.haram-card, .haram-contact, .haram-social, .haram-body > section')) {
                const box = element.getBoundingClientRect();
                if (box.left < bounds.left - 1 || box.right > bounds.right + 1 || box.bottom > bounds.top + 775 * scale || element.scrollWidth > element.clientWidth + 2) {
                    throw new Error('Haram content exceeds its printable area.');
                }
            }
        }
    } catch (error) {
        window.haramReportError = error.message;
    } finally {
        window.haramReportReady = true;
    }
})();
