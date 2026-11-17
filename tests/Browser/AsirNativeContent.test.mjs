import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import puppeteer from 'puppeteer';
import { getDocument } from 'pdfjs-dist/legacy/build/pdf.mjs';
import { createCanvas } from '@napi-rs/canvas';

async function render(scenario, callback, beforeLayout = null) {
    execFileSync(process.env.PHP_BINARY || 'php', ['scripts/auction-reports/render-asir-native-proof.php', scenario], { stdio: 'pipe' });
    const browser = await puppeteer.launch({ headless: true, args: ['--no-sandbox'] });
    try {
        const page = await browser.newPage();
        await page.emulateMediaType('print');
        const html = readFileSync(`output/pdf/asir-native-content/${scenario}.html`, 'utf8');
        await page.setContent(beforeLayout ? html.replace(/<script data-asir-layout>[\s\S]*?<\/script>/, '') : html, { waitUntil: 'load' });
        if (beforeLayout) {
            await beforeLayout(page);
            await page.addScriptTag({ content: readFileSync('resources/auction-reports/asir/layout.js', 'utf8') });
        }
        await page.waitForFunction('window.asirReportReady === true || window.asirReportError !== null');
        assert.equal(await page.evaluate(() => window.asirReportError), null);
        await callback(page);
    } finally { await browser.close(); }
}

test('every internal Asir page uses native content and every PDF page matches the measured DOM', async () => {
    await render('populated', async page => {
        const result = await page.evaluate(() => ({
            count: document.querySelectorAll('.auction-print-page').length,
            graphics: document.querySelectorAll('.asir-native-page svg,.asir-native-page canvas,.asir-native-page object').length,
            headings: [...document.querySelectorAll('.asir-native-page')].every(page => page.querySelector('header h1')),
            attachments: [...document.querySelectorAll('.asir-native-page [data-report-attachment]')].map(image => image.dataset.reportAttachment),
            alignments: [...document.querySelectorAll('.asir-table td')].map(cell => ({ align: getComputedStyle(cell).textAlign, padding: parseFloat(getComputedStyle(cell).paddingRight) })),
        }));
        assert.equal(result.graphics, 0);
        assert.equal(result.count, 21, 'The populated reference keeps the original section/page structure.');
        assert.equal(result.headings, true);
        // X evidence intentionally appears in its campaign and the social summary.
        assert.equal(result.attachments.filter(id => id === '304').length, 1);
        assert.equal(result.attachments.filter(id => id === '404').length, 1);
        assert.ok(result.alignments.every(cell => cell.align === 'center' && cell.padding >= 4));
        const pdf = await getDocument({ data: new Uint8Array(await page.pdf({ preferCSSPageSize: true, printBackground: true })) }).promise;
        try { assert.equal(pdf.numPages, result.count); } finally { await pdf.destroy(); }
    });
});

test('maximum valid thumbnail names remain complete without breaking PDF generation or duplicating evidence', async () => {
    for (const name of ['W'.repeat(255), 'م'.repeat(255)]) {
        let attachments;
        await render('populated', async page => {
            const result = await page.evaluate(() => ({
                names: [...document.querySelectorAll('.asir-thumbnail-name [data-asir-paragraph],.asir-platform-name strong')].map(node => node.textContent),
                thumbnailFits: [...document.querySelectorAll('.asir-platform-name strong')].every(node => node.getBoundingClientRect().bottom <= node.closest('.asir-platform-hero').getBoundingClientRect().bottom + 1 && parseFloat(getComputedStyle(node).fontSize) >= 8 / .75 - .1),
                attachments: [...document.querySelectorAll('.asir-native-page [data-report-attachment]')].map(node => node.dataset.reportAttachment),
                count: document.querySelectorAll('.auction-print-page').length,
            }));
            assert.deepEqual(result.names, [name, name]);
            assert.equal(result.thumbnailFits, true);
            assert.deepEqual(result.attachments, attachments);
            const pdf = await getDocument({ data: new Uint8Array(await page.pdf({ preferCSSPageSize: true, printBackground: true })) }).promise;
            try { assert.equal(pdf.numPages, result.count); } finally { await pdf.destroy(); }
        }, async page => {
            attachments = await page.evaluate(name => {
                document.querySelectorAll('.asir-platform-name strong').forEach(node => { node.textContent = name; });
                return [...document.querySelectorAll('.asir-native-page [data-report-attachment]')].map(node => node.dataset.reportAttachment);
            }, name);
        });
    }
});

test('long document captions stay visible and fit without cropping text or duplicating evidence', async () => {
    const caption = 'W'.repeat(255);
    let attachments;
    await render('populated', async page => {
        const result = await page.evaluate(() => ({
            text: document.querySelector('.asir-broker-image figcaption').textContent,
            color: getComputedStyle(document.querySelector('.asir-broker-image figcaption')).color,
            fit: document.querySelector('.asir-broker-image [data-report-attachment]').style.objectFit,
            attachments: [...document.querySelectorAll('.asir-native-page [data-report-attachment]')].map(node => node.dataset.reportAttachment),
            count: document.querySelectorAll('.auction-print-page').length,
        }));
        assert.equal(result.text, caption);
        assert.equal(result.color, 'rgb(10, 57, 92)');
        assert.equal(result.fit, 'contain');
        assert.deepEqual(result.attachments, attachments);
        const pdf = await getDocument({ data: new Uint8Array(await page.pdf({ preferCSSPageSize: true, printBackground: true })) }).promise;
        try { assert.equal(pdf.numPages, result.count); } finally { await pdf.destroy(); }
    }, async page => {
        attachments = await page.evaluate(text => {
            const label = document.createElement('figcaption');
            label.textContent = text;
            document.querySelector('.asir-broker-image').append(label);
            return [...document.querySelectorAll('.asir-native-page [data-report-attachment]')].map(node => node.dataset.reportAttachment);
        }, caption);
    });
});

test('native evidence frames retain the measured original Asir positions and dimensions', async () => {
    await render('populated', async page => {
        const frames = await page.evaluate(() => Object.fromEntries(['asir-board-installation-1', 'asir-board-installation-2', 'asir-brochure', 'asir-video', 'asir-x', 'asir-whatsapp', 'asir-brokers'].map(type => {
            const page = document.querySelector(`[data-page-type="${type}"]`);
            const image = page.querySelector('[data-report-attachment]');
            const bounds = page.getBoundingClientRect();
            const rect = image.getBoundingClientRect();
            return [type, [(rect.x - bounds.x) * .75, (rect.y - bounds.y) * .75, rect.width * .75, rect.height * .75]];
        })));
        const dates = await page.evaluate(() => {
            const page = document.querySelector('[data-page-type="asir-index"]');
            const frame = page.getBoundingClientRect();
            const card = page.querySelector('.asir-date-card').getBoundingClientRect();
            return [(card.x - frame.x) * .75, (card.y - frame.y) * .75, card.width * .75, card.height * .75];
        });
        [319.22, 234.94, 242.21, 88.04].forEach((expected, index) => assert.ok(Math.abs(dates[index] - expected) < 1.5, `Original date card ${expected}, native ${dates[index]}`));
        for (const [type, original] of Object.entries({
            'asir-board-installation-1': [334.681, 364.393, 156.362, 106.731],
            'asir-brochure': [440.795, 371.261, 57.369, 81.146],
            'asir-video': [115.583, 366.137, 273.484, 187.305],
            'asir-x': [391.054, 370.056, 110.353, 148.046],
            'asir-whatsapp': [160.539, 558.545, 291.219, 116],
            'asir-brokers': [135.5, 325.8, 356.1, 463.6],
        })) {
            original.forEach((expected, index) => assert.ok(Math.abs(frames[type][index] - expected) < 1.5, `${type}: original ${expected}, native ${frames[type][index]}`));
        }
    });
});

test('evidence appendices retain original frames, complete captions and campaign metadata', async () => {
    await render('appendix', async page => {
        const result = await page.evaluate(() => {
            const gallery = [...document.querySelectorAll('.asir-native-gallery')].find(node => node.querySelector('[data-report-attachment="901"]'));
            const frame = gallery.getBoundingClientRect();
            return {
                frames: [...gallery.querySelectorAll('[data-report-attachment]')].map(node => {
                    const rect = node.getBoundingClientRect();
                    return [(rect.x - frame.x) * .75, (rect.y - frame.y) * .75, rect.width * .75, rect.height * .75];
                }),
                caption: gallery.querySelector('figcaption').textContent,
                campaign: document.querySelector('.asir-gallery-main--campaign').textContent,
                url: document.querySelector('.asir-gallery-main--campaign a').href,
            };
        });
        [[95, 350, 190, 100], [310, 350, 190, 100], [95, 485, 190, 100]].forEach((original, slot) => original.forEach((expected, index) => assert.ok(Math.abs(result.frames[slot][index] - expected) < 1.5, `Appendix frame ${slot}: original ${expected}, native ${result.frames[slot][index]}`)));
        assert.equal(result.caption, 'تيك توك - مرفق 901 - 2');
        assert.ok(result.campaign.includes('حملة إضافية'));
        assert.ok(result.campaign.includes('42'));
        assert.equal(result.url, 'https://example.com/extra');
    });
});

test('social badges and unsold table corners preserve their original CSS geometry', async () => {
    await render('populated', async page => {
        const styles = await page.evaluate(() => {
            const badge = document.querySelector('.asir-social-label');
            const table = document.querySelector('.asir-unsold-table');
            return {
                badgeRadius: getComputedStyle(badge).borderTopLeftRadius,
                badgeColor: getComputedStyle(badge, '::before').backgroundColor,
                headerClip: getComputedStyle(table.querySelector('th:last-child')).clipPath,
                lastClip: getComputedStyle(table.querySelector('tbody tr:last-child td:first-child')).clipPath,
            };
        });
        assert.ok(Math.abs(parseFloat(styles.badgeRadius) * .75 - 27.76) < .1);
        assert.equal(styles.badgeColor, 'rgb(244, 238, 30)');
        assert.notEqual(styles.headerClip, 'none');
        assert.notEqual(styles.lastClip, 'none');
    });
});

test('the application PDF preserves logo transparency and a solid complete delivery chart', async () => {
    await render('populated', async page => {
        const points = await page.evaluate(() => {
            const frame = document.querySelector('[data-page-type="asir-whatsapp"]');
            const bounds = frame.getBoundingClientRect();
            const logo = frame.querySelector('.asir-whatsapp-link a').getBoundingClientRect();
            const chart = frame.querySelector('[data-report-chart="whatsapp"]').getBoundingClientRect();
            return {
                page: [...document.querySelectorAll('.auction-print-page')].indexOf(frame) + 1,
                clear: [(logo.x - bounds.x) * .75 + 8, (logo.y - bounds.y) * .75 + 8],
                chart: [-1, 0, 1].map(offset => [(chart.x - bounds.x + chart.width / 2) * .75 + offset, (chart.y - bounds.y) * .75 + 15]),
            };
        });
        execFileSync(process.env.PHP_BINARY || 'php', ['scripts/auction-reports/render-asir-native-proof.php', 'populated', '--pdf'], { stdio: 'pipe' });
        const pdf = await getDocument({ data: new Uint8Array(readFileSync('output/pdf/asir-native-content/populated-engine.pdf')) }).promise;
        try {
            const sheet = await pdf.getPage(points.page);
            const viewport = sheet.getViewport({ scale: 2 });
            const canvas = createCanvas(Math.ceil(viewport.width), Math.ceil(viewport.height));
            const context = canvas.getContext('2d');
            await sheet.render({ canvasContext: context, viewport }).promise;
            const rgb = point => [...context.getImageData(Math.floor(point[0] * 2), Math.floor(point[1] * 2), 1, 1).data].slice(0, 3);
            assert.deepEqual(rgb(points.clear), [255, 255, 255], 'The transparent corner of the WhatsApp icon must reveal the white card.');
            for (const point of points.chart) assert.deepEqual(rgb(point), [0, 85, 137], 'A complete delivery chart must be uniformly blue, without a zero-value seam.');
        } finally { await pdf.destroy(); }
    });
});

for (const scenario of ['many', 'long']) {
    test(`${scenario} content continues under the same heading with all rows, attachments, and text`, async () => {
        await render(scenario, async page => {
            const result = await page.evaluate(() => ({
                pages: [...document.querySelectorAll('.auction-print-page')].map(page => ({ type: page.dataset.pageType, title: page.querySelector('h1')?.textContent })),
                rows: [...document.querySelectorAll('[data-asir-table="properties"] tbody tr')].map(row => row.cells[3].textContent.trim()),
                sold: [...document.querySelectorAll('[data-asir-property]')].map(section => section.dataset.asirProperty),
                unsold: [...document.querySelectorAll('[data-asir-table="unsold"] tbody tr')].map(row => ({ id: row.dataset.asirRow, text: row.cells[4].textContent })),
                introduction: [...document.querySelectorAll('[data-report-field="introduction"]')].map(node => node.textContent).join(''),
                planning: [...document.querySelectorAll('[data-report-field="planning_methodology"]')].map(node => node.textContent).join(''),
                images: [...document.querySelectorAll('[data-report-attachment]')].map(node => node.dataset.reportAttachment),
            }));
            assert.deepEqual(result.rows, Array.from({ length: 47 }, (_, index) => `PROP-${index + 1}`));
            assert.equal(result.sold.length, 19);
            assert.equal(new Set(result.unsold.map(row => row.id)).size, 28);
            for (let index = 20; index <= 47; index++) {
                const id = String(index).replace(/\d/g, value => '٠١٢٣٤٥٦٧٨٩'[value]);
                assert.equal(result.unsold.filter(row => row.id === id).map(row => row.text).join(''), `سبب عدم البيع المسجل للعقار ${index}. `.repeat(scenario === 'long' ? 100 : 1));
            }
            for (const base of [1000, 4000, 8000]) for (let index = 1; index <= 17; index++) assert.ok(result.images.includes(String(base + index)));
            if (scenario === 'long') {
                assert.equal(result.introduction, 'التقرير التفصيلي لنتائج المزاد والعقارات المعروضة. '.repeat(300));
                assert.equal(result.planning, 'دراسة الفئات المستهدفة ووسائل الوصول إليها وواقع السوق العقاري. '.repeat(200));
            }
            for (const family of ['asir-index', 'asir-introduction', 'asir-planning', 'asir-sold-assets', 'asir-unsold-assets']) {
                const indices = result.pages.flatMap((page, index) => page.type.replace(/-continuation$/, '') === family ? [index] : []);
                assert.equal(indices.at(-1) - indices[0] + 1, indices.length);
                assert.ok(indices.every(index => result.pages[index].title === result.pages[indices[0]].title));
            }
        });
    });
}

test('sparse reports keep recorded zero without adding absent evidence or result sections', async () => {
    await render('sparse', async page => {
        const result = await page.evaluate(() => ({
            types: [...document.querySelectorAll('.auction-print-page')].map(page => page.dataset.pageType),
            values: [...document.querySelectorAll('[data-report-field="x_views"]')].map(node => node.textContent),
            images: document.querySelectorAll('.asir-native-page [data-report-attachment]').length,
        }));
        assert.deepEqual(result.types, ['asir-cover', 'asir-index', 'asir-x', 'asir-social', 'asir-closing']);
        assert.deepEqual(result.values, ['0', '0']);
        assert.equal(result.images, 0);
    });
});
