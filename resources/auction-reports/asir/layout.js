(async () => {
    window.asirReportReady = false;
    window.asirReportError = null;
    try {
        await document.fonts.ready;
        await Promise.all([...document.images].map(image => image.complete
            ? Promise.resolve()
            : new Promise((resolve, reject) => { image.onload = resolve; image.onerror = () => reject(new Error('An Asir report image could not load.')); })));

        for (const cover of document.querySelectorAll('[data-asir-cover]')) {
            const scale = cover.getBoundingClientRect().width / 595.276;
            const height = element => element.getBoundingClientRect().height / scale;
            const fitText = (element, maximum, minimum, availableHeight) => {
                let size = maximum;
                element.style.fontSize = `${size}pt`;
                const fits = () => height(element) <= availableHeight + .2 && element.scrollWidth <= element.clientWidth + 1;
                while (!fits() && size > minimum) {
                    size = Math.max(minimum, size - .25);
                    element.style.fontSize = `${size}pt`;
                }
                return fits();
            };
            const titles = cover.querySelector('.asir-cover-titles');
            if (titles) {
                const name = titles.querySelector('[data-report-field="name"]');
                const subtitle = titles.querySelector('[data-report-field="subtitle"]');
                const nameFits = fitText(name, 95, 36, 150);
                const subtitleFits = fitText(subtitle, 30, 18, 60);
                if (!nameFits || !subtitleFits) {
                    titles.classList.add('asir-cover-titles--expanded');
                    const fullNameFits = fitText(name, 95, 18, 250);
                    let fullSubtitleFits = fitText(subtitle, 30, 12, 370 - 12 - height(name));
                    let nameSize = parseFloat(name.style.fontSize);
                    while (!fullSubtitleFits && nameSize > 18) {
                        nameSize = Math.max(18, nameSize - .25);
                        name.style.fontSize = `${nameSize}pt`;
                        fullSubtitleFits = fitText(subtitle, 30, 12, 370 - 12 - height(name));
                    }
                    if (!fullNameFits || !fullSubtitleFits) throw new Error('Asir cover titles exceed their readable cover area.');
                }
                if (!titles.classList.contains('asir-cover-titles--expanded')) {
                    // Preserve the source SVG's baseline and horizontal font
                    // scaling for ordinary single-line titles. Wrapped titles
                    // retain the measured flow and its explicit inter-title gap.
                    for (const [text, baseline, stretch] of [[name, 281.802, 97.381 / 95], [subtitle, 356.932, 30.752 / 30]]) {
                        const style = getComputedStyle(text);
                        const lineHeight = parseFloat(style.lineHeight);
                        if (text.getBoundingClientRect().height > lineHeight + 1) continue;
                        const context = document.createElement('canvas').getContext('2d');
                        context.font = `${style.fontWeight} ${style.fontSize} ${style.fontFamily}`;
                        const metrics = context.measureText(text.textContent);
                        const top = text.getBoundingClientRect().top - cover.getBoundingClientRect().top;
                        const current = (top + (lineHeight - metrics.fontBoundingBoxAscent - metrics.fontBoundingBoxDescent) / 2 + metrics.fontBoundingBoxAscent) / scale;
                        text.style.transform = `translateY(${baseline - current}pt) scaleX(${stretch})`;
                    }
                } else {
                    // Keep the source strokes intact. Long title regions push
                    // their decorative layer down, behind the original photo.
                    const curves = cover.querySelector('[data-cover-curves]');
                    const titleBounds = titles.getBoundingClientRect();
                    let firstStroke = Infinity;
                    for (const path of curves?.querySelectorAll('path') ?? []) {
                        const length = path.getTotalLength();
                        const matrix = path.getScreenCTM();
                        for (let index = 0; index <= 128; index++) {
                            const point = path.getPointAtLength(length * index / 128).matrixTransform(matrix);
                            if (point.x >= titleBounds.left && point.x <= titleBounds.right) firstStroke = Math.min(firstStroke, point.y);
                        }
                    }
                    if (firstStroke < titleBounds.bottom + 12 * scale) curves.setAttribute('transform', `translate(0 ${(titleBounds.bottom - firstStroke) / scale + 12})`);
                }
            }
            for (const frame of cover.querySelectorAll('[data-asir-logo]')) {
                const image = frame.querySelector('img');
                await image.decode();
                const style = getComputedStyle(frame);
                const padding = parseFloat(style.paddingLeft) / scale;
                const bounds = frame.getBoundingClientRect();
                const width = bounds.width / scale, frameHeight = bounds.height / scale;
                const ratio = Math.min((width - padding * 2) / image.naturalWidth, (frameHeight - padding * 2) / image.naturalHeight);
                const fittedWidth = image.naturalWidth * ratio + padding * 2;
                const fittedHeight = image.naturalHeight * ratio + padding * 2;
                frame.style.left = `${parseFloat(style.left) * .75 + (width - fittedWidth) / 2}pt`;
                frame.style.top = `${parseFloat(style.top) * .75 + (frameHeight - fittedHeight) / 2}pt`;
                frame.style.width = `${fittedWidth}pt`;
                frame.style.height = `${fittedHeight}pt`;
                const sample = document.createElement('canvas');
                sample.width = sample.height = 64;
                const context = sample.getContext('2d', { willReadFrequently: true });
                context.drawImage(image, 0, 0, 64, 64);
                const pixels = context.getImageData(0, 0, 64, 64).data;
                let ink = 0, luminance = 0, transparent = false;
                for (let index = 0; index < pixels.length; index += 4) {
                    const alpha = pixels[index + 3] / 255;
                    if (alpha < .95) transparent = true;
                    if (alpha < .1) continue;
                    luminance += (.2126 * pixels[index] + .7152 * pixels[index + 1] + .0722 * pixels[index + 2]) / 255 * alpha;
                    ink += alpha;
                }
                const light = ink > 0 && luminance / ink > .65;
                const dark = ink > 0 && luminance / ink < .4;
                if (transparent && frame.dataset.logoBackground === 'light' && light) frame.style.backgroundColor = '#303039';
                if (transparent && frame.dataset.logoBackground === 'dark' && dark) frame.style.backgroundColor = 'rgba(255,255,255,.92)';
            }
            const contacts = cover.querySelector('.asir-cover-contacts');
            if (contacts) {
                const phoneTexts = [...contacts.querySelectorAll('.asir-cover-phones [data-report-field]')];
                for (const text of phoneTexts) fitText(text, 48.642, 28, 55);
                const social = contacts.querySelector('.asir-cover-contact--social');
                if (social) {
                    const text = social.querySelector('[data-report-field]');
                    let size = 16.529;
                    while (height(contacts) > 270 && size > 10) {
                        size = Math.max(10, size - .25);
                        text.style.fontSize = `${size}pt`;
                    }
                    if (height(contacts) > 270) social.style.marginTop = '12pt';
                }
                // Unicode numeric symbols are accepted by the existing phone
                // validation and may be much wider than ordinary digits.
                while (height(contacts) > 270 && phoneTexts.some(text => parseFloat(text.style.fontSize) > 18)) {
                    for (const text of phoneTexts) text.style.fontSize = `${Math.max(18, parseFloat(text.style.fontSize) - .25)}pt`;
                }
                if (height(contacts) > 270) contacts.style.top = `${820 - height(contacts)}pt`;
                if (height(contacts) > 320.5) throw new Error('Asir contact groups exceed the closing cover.');
            }
            const bounds = cover.getBoundingClientRect();
            for (const element of cover.querySelectorAll('[data-report-field],[data-asir-logo]')) {
                const box = element.getBoundingClientRect();
                if (box.left < bounds.left - 1 || box.right > bounds.right + 1 || box.top < bounds.top - 1 || box.bottom > bounds.bottom - 12 * scale) {
                    throw new Error('Asir cover content exceeds its printable area.');
                }
            }
        }

        // Keep each link inside its reference-sized button, including long labels.
        for (const link of document.querySelectorAll('[data-asir-reference-link]')) {
            const text = link.querySelector('span');
            const style = getComputedStyle(link);
            const availableHeight = link.clientHeight - parseFloat(style.paddingTop) - parseFloat(style.paddingBottom);
            const fits = () => text.scrollWidth <= text.clientWidth + 1 && text.getBoundingClientRect().height <= availableHeight + .5;
            let size = parseFloat(style.fontSize) * .75;
            while (!fits() && size > 6) {
                size = Math.max(6, size - .25);
                link.style.fontSize = `${size}pt`;
            }
            if (!fits()) throw new Error('An Asir link label exceeds its reference button.');
        }

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

        // Keep the original logo panel and text column. Additional lines expand
        // the text upwards within that column rather than moving the artwork.
        for (const body of document.querySelectorAll('.asir-introduction-main')) {
            const paragraph = body.querySelector('[data-asir-paragraph]');
            if (!paragraph) continue;
            const scale = body.closest('.auction-print-page').getBoundingClientRect().width / 595.276;
            const height = paragraph.getBoundingClientRect().height / scale;
            const lineHeight = parseFloat(getComputedStyle(paragraph).lineHeight) / scale;
            body.style.paddingBlockStart = `${Math.max(25, 337 - Math.max(0, height - lineHeight) / 2)}pt`;
        }

        // Methodology subsections share one content flow under their common title.
        const methodology = [...document.querySelectorAll('.asir-native-narrative')].filter(page => ['4', '5', '6'].includes(page.dataset.asirIndex));
        if (methodology.length > 1) {
            const body = methodology[0].querySelector('[data-asir-flow]');
            for (const page of methodology.slice(1)) {
                body.append(...page.querySelector('[data-asir-flow]').children);
                page.remove();
            }
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
            const printableBottom = ['4', '5', '6'].includes(page.dataset.asirIndex) ? 795 : 825;
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
                return node.getBoundingClientRect().bottom <= bounds.top + printableBottom * bounds.width / 595.276 + .3;
            };
            const fitUsingFlexibleGaps = node => {
                const frame = page.getBoundingClientRect();
                const scale = frame.width / 595.276;
                const overflow = () => (node.getBoundingClientRect().bottom - frame.top) / scale - printableBottom;
                const gaps = [...body.children].reverse().filter(section => section.hasAttribute('data-asir-gap-min')).map(section => ({
                    section,
                    current: parseFloat(getComputedStyle(section).marginBlockStart) / scale,
                    minimum: Number(section.dataset.asirGapMin),
                })).filter(gap => Number.isFinite(gap.current) && Number.isFinite(gap.minimum) && gap.current > gap.minimum);
                // Preserve the reference spacing when a new page is inevitable.
                if (gaps.reduce((sum, gap) => sum + gap.current - gap.minimum, 0) < overflow() + .5) return false;
                for (const gap of gaps) {
                    gap.section.style.setProperty('margin-block-start', `${Math.max(gap.minimum, gap.current - overflow() - .5)}pt`, 'important');
                    if (fits(node)) return true;
                }
                return fits(node);
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
                        const brokerDocument = item.matches('.asir-broker-document');
                        if (image && (item.querySelector('figcaption') || brokerDocument)) {
                            const frame = page.getBoundingClientRect();
                            const scale = frame.width / 595.276;
                            if (brokerDocument && part.hasAttribute('data-asir-gap-min')) {
                                const gap = parseFloat(getComputedStyle(part).marginBlockStart) / scale;
                                part.style.setProperty('margin-block-start', `${Math.min(gap, Number(part.dataset.asirGapMin))}pt`, 'important');
                            }
                            const overflow = part.getBoundingClientRect().bottom - frame.top - 825 * scale;
                            const height = image.getBoundingClientRect().height;
                            // Keep full captions readable. Only unusually tall
                            // figures need a smaller contained image to fit.
                            const minimum = Math.min(height, (brokerDocument ? 300 : 100) * scale);
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
                if (fits(section) || fitUsingFlexibleGaps(section)) continue;
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
