import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import puppeteer from 'puppeteer';
import { getDocument } from 'pdfjs-dist/legacy/build/pdf.mjs';

async function render(scenario, callback) {
    execFileSync(process.env.PHP_BINARY || 'php', ['scripts/auction-reports/render-native-content-proof.php', scenario], { stdio: 'pipe' });
    const browser = await puppeteer.launch({ headless: true, args: ['--no-sandbox'] });
    try {
        const page = await browser.newPage();
        await page.emulateMediaType('print');
        await page.setContent(readFileSync(`output/pdf/haram-native-content/${scenario}.html`, 'utf8'), { waitUntil: 'load' });
        await page.waitForFunction('window.haramReportReady === true');
        assert.equal(await page.evaluate(() => window.haramReportError), null);
        await callback(page);
    } finally {
        await browser.close();
    }
}

test('native results and marketing preserve original frames, colors, padding and live links', async () => {
    await render('reference', async page => {
        const result = await page.evaluate(() => {
            const results = document.querySelector('.haram-native-results');
            const marketing = document.querySelector('.haram-native-marketing');
            const point = (node, frame) => {
                const rect = node.getBoundingClientRect();
                const bounds = frame.getBoundingClientRect();
                return { x: (rect.x - bounds.x) * .75, y: (rect.y - bounds.y) * .75, width: rect.width * .75, height: rect.height * .75 };
            };
            return {
                graphicText: document.querySelectorAll('.haram-native-page svg,.haram-native-page canvas').length,
                narrative: point(results.querySelector('.haram-narrative-card'), results),
                xImage: point(marketing.querySelector('[data-report-attachment="1"]'), marketing),
                cells: [...results.querySelectorAll('th,td')].map(cell => ({ align: getComputedStyle(cell).textAlign, padding: parseFloat(getComputedStyle(cell).paddingRight) * .75, border: getComputedStyle(cell).borderWidth })),
                links: [...document.querySelectorAll('.haram-native-marketing a')].map(link => link.href),
            };
        });
        assert.equal(result.graphicText, 0);
        for (const [actual, expected] of [[result.narrative.y, 130], [result.xImage.width, 140.15], [result.xImage.height, 142.33]]) {
            assert.ok(Math.abs(actual - expected) < 1.5, `Original frame ${expected}, native frame ${actual}`);
        }
        for (const cell of result.cells) {
            assert.equal(cell.align, 'center');
            assert.ok(cell.padding >= 7.9);
            assert.equal(cell.border, '0px');
        }
        assert.deepEqual(result.links, ['https://example.com/x', 'https://example.com/tiktok', 'https://example.com/evidence', 'https://example.com/boards']);
    });
});

for (const scenario of ['many', 'long']) {
    test(`${scenario} data continues within its own section with every row, image and narrative preserved`, async () => {
        await render(scenario, async page => {
            const result = await page.evaluate(() => ({
                pages: [...document.querySelectorAll('.auction-print-page')].map(node => ({ type: node.dataset.pageType, title: node.querySelector('h1')?.textContent })),
                rows: Object.fromEntries(['sold', 'unsold'].map(type => [type, [...document.querySelectorAll(`[data-native-result-table="${type}"] tbody tr`)].map(row => row.cells[0].textContent.trim())])),
                narrative: [...document.querySelectorAll('[data-native-paragraph][data-report-field="narrative"]')].map(node => node.textContent).join(''),
                images: [...document.querySelectorAll('.haram-native-page [data-report-attachment]')].map(node => node.dataset.reportAttachment),
                bounds: [...document.querySelectorAll('[data-native-flow] > [data-native-section]')].map(node => (node.getBoundingClientRect().bottom - node.closest('.auction-print-page').getBoundingClientRect().top) * .75),
            }));
            assert.deepEqual(result.rows.sold, Array.from({ length: 19 }, (_, i) => String(i + 1)));
            assert.deepEqual(result.rows.unsold, Array.from({ length: 28 }, (_, i) => String(i + 20)));
            for (let index = 1; index <= 17; index++) for (const base of [100, 200, 300]) assert.ok(result.images.includes(String(base + index)));
            if (scenario === 'long') assert.equal(result.narrative, 'نتائج المزاد والبيانات المسجلة عن عملية البيع. '.repeat(220));
            assert.ok(result.bounds.every(bottom => bottom <= 790.1));
            for (const family of ['results', 'marketing', 'board-before', 'brokers']) {
                const indices = result.pages.flatMap((page, index) => page.type.startsWith(`haram-${family}`) ? [index] : []);
                assert.ok(indices.length > 0);
                assert.equal(indices.at(-1) - indices[0] + 1, indices.length, `${family} pages must stay consecutive`);
                assert.ok(indices.every(index => result.pages[index].title === result.pages[indices[0]].title));
            }
            const document = await getDocument({ data: new Uint8Array(await page.pdf({ preferCSSPageSize: true, printBackground: true })) }).promise;
            try { assert.equal(document.numPages, result.pages.length); } finally { await document.destroy(); }
        });
    });
}

test('marketing flows all campaign images once without repeating TikTok metrics or shared links', async () => {
    await render('marketing', async page => {
        const result = await page.evaluate(() => ({
            images: [...document.querySelectorAll('.haram-native-marketing [data-report-attachment]')].map(node => Number(node.dataset.reportAttachment)),
            tiktok: document.querySelectorAll('[data-report-field="tiktok_views"]').length,
            evidenceLinks: document.querySelectorAll('.haram-native-marketing [data-link-role="marketing_evidence"]').length,
            secondX: document.querySelectorAll('.haram-native-marketing a[href="https://example.com/x-second"]').length,
            channels: [...document.querySelectorAll('.haram-native-marketing [data-haram-channel]')].map(node => node.dataset.haramChannel),
            sections: [...document.querySelectorAll('.haram-native-marketing [data-native-flow] > section')].map(node => ({ bottom: (node.getBoundingClientRect().bottom - node.closest('.auction-print-page').getBoundingClientRect().top) * .75, items: node.querySelector('[data-native-marketing-items]').children.length })),
        }));
        assert.deepEqual(result.images.sort((a, b) => a - b), [...Array.from({ length: 8 }, (_, i) => 401 + i), ...Array.from({ length: 8 }, (_, i) => 501 + i), 600]);
        assert.equal(result.tiktok, 1);
        assert.equal(result.evidenceLinks, 1);
        assert.equal(result.secondX, 1);
        assert.deepEqual([...new Set(result.channels)], ['x', 'tiktok', 'snapchat']);
        assert.deepEqual(result.channels, [...result.channels].sort((a, b) => ['x', 'tiktok', 'snapchat'].indexOf(a) - ['x', 'tiktok', 'snapchat'].indexOf(b)));
        assert.ok(result.sections.every(section => section.items > 0 && section.bottom <= 790.1));
    });
});

test('short platforms follow each other on the same page', async () => {
    await render('partial', async page => {
        await page.evaluate(async () => {
            const flow = document.querySelector('.haram-native-marketing [data-native-flow]');
            const first = flow.firstElementChild;
            flow.append(first.cloneNode(true), first.cloneNode(true));
            const script = document.createElement('script');
            script.textContent = document.querySelector('[data-haram-layout]').textContent;
            document.body.append(script);
            while (!window.haramReportReady) await new Promise(resolve => setTimeout(resolve, 10));
        });
        assert.equal(await page.evaluate(() => document.querySelectorAll('.haram-native-marketing').length), 1);
        const gaps = await page.evaluate(() => [...document.querySelector('.haram-native-marketing [data-native-flow]').children].slice(1).map(node => (node.getBoundingClientRect().top - node.previousElementSibling.getBoundingClientRect().bottom) * .75));
        assert.ok(gaps.every(gap => gap >= 0 && gap < 16));
    });
});

test('partial data keeps zero, omits absent sections, and does not produce empty internal pages', async () => {
    await render('partial', async page => {
        const result = await page.evaluate(() => ({
            types: [...document.querySelectorAll('.auction-print-page')].map(page => page.dataset.pageType),
            count: document.querySelectorAll('.haram-summary-card').length,
            value: document.querySelector('[data-report-field="x_views"]').textContent,
        }));
        assert.deepEqual(result.types, ['haram-cover', 'haram-index', 'haram-marketing', 'haram-closing']);
        assert.equal(result.count, 1);
        assert.equal(result.value, '0');
    });
});
