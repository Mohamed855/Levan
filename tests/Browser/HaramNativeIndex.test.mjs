import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import puppeteer from 'puppeteer';

async function render(scenario, callback) {
    execFileSync(process.env.PHP_BINARY || 'php', ['scripts/auction-reports/render-native-index-proof.php', scenario], { stdio: 'pipe' });
    const browser = await puppeteer.launch({ headless: true, args: ['--no-sandbox'] });
    try {
        const page = await browser.newPage();
        await page.emulateMediaType('print');
        await page.setContent(readFileSync(`output/pdf/haram-native-index/${scenario}.html`, 'utf8'), { waitUntil: 'load' });
        await page.waitForFunction('window.haramReportReady === true');
        assert.equal(await page.evaluate(() => window.haramReportError), null);
        await callback(page);
    } finally {
        await browser.close();
    }
}

test('reference index matches measured original geometry while using real HTML cells', async () => {
    await render('reference', async page => {
        const result = await page.evaluate(() => {
            const page = document.querySelector('.haram-native-index');
            const frame = page.getBoundingClientRect();
            const point = node => (node.getBoundingClientRect().top - frame.top) * .75;
            const project = page.querySelector('.haram-index-project-frame').getBoundingClientRect();
            const dateHeading = page.querySelector('.haram-index-dates h2').getBoundingClientRect();
            return {
                graphics: page.querySelectorAll('svg,canvas,object').length,
                headings: [...page.querySelectorAll('h2')].map(point),
                project: point(page.querySelector('.haram-index-project-frame')),
                dates: point(page.querySelector('.haram-index-date-grid')),
                properties: point(page.querySelector('.haram-index-property-frame')),
                gap: (dateHeading.top - project.bottom) * .75,
                cells: [...page.querySelectorAll('th,td')].map(node => ({ align: getComputedStyle(node).textAlign, padding: parseFloat(getComputedStyle(node).paddingRight) * .75 })),
            };
        });
        // Measured from the original PDF, in points, rather than inferred from CSS.
        for (const [actual, expected] of [...result.headings.map((value, index) => [value, [82.5, 193, 294][index]]), [result.project, 111.5], [result.dates, 222], [result.properties, 320.5]]) {
            assert.ok(Math.abs(actual - expected) < .5, `${actual} differs from original position ${expected}`);
        }
        assert.equal(result.graphics, 0);
        assert.ok(result.gap > 17);
        for (const cell of result.cells) {
            assert.equal(cell.align, 'center');
            assert.ok(cell.padding >= 7.9);
        }
    });
});

test('real long rows continue under the same title without losing records', async () => {
    await render('many', async page => {
        const original = await page.evaluate(() => ({
            pages: document.querySelectorAll('.haram-native-index').length,
            records: [...document.querySelectorAll('[data-report-cell$=":1"]')].map(node => node.textContent.trim()),
        }));
        assert.equal(original.pages, 3);
        assert.equal(original.records.length, 29);
        await page.evaluate(async () => {
            for (const badge of document.querySelectorAll('[data-haram-property-badge]')) badge.textContent = 'أرض سكنية استثمارية '.repeat(18);
            const script = document.createElement('script');
            script.textContent = document.querySelector('[data-haram-layout]').textContent;
            document.body.append(script);
            while (!window.haramReportReady) await new Promise(resolve => setTimeout(resolve, 10));
        });
        const result = await page.evaluate(() => ({
            error: window.haramReportError,
            pages: [...document.querySelectorAll('.haram-native-index')].map(page => ({
                title: page.querySelector('h1').textContent,
                lastRowBottom: (page.querySelector('[data-native-property-table] tbody tr:last-child').getBoundingClientRect().bottom - page.getBoundingClientRect().top) * .75,
            })),
            records: [...document.querySelectorAll('[data-report-cell$=":1"]')].map(node => node.textContent.trim()),
        }));
        assert.equal(result.error, null);
        assert.ok(result.pages.length > 3);
        assert.deepEqual(result.records, original.records);
        for (const page of result.pages) {
            assert.equal(page.title, 'مخرجات المزاد');
            assert.ok(page.lastRowBottom <= 775.1);
        }
    });
});

test('long project metadata expands without touching the following heading', async () => {
    await render('long', async page => {
        const result = await page.evaluate(() => {
            const project = document.querySelector('.haram-index-project-frame').getBoundingClientRect();
            const heading = document.querySelector('.haram-index-dates h2').getBoundingClientRect();
            return { height: project.height * .75, gap: (heading.top - project.bottom) * .75, text: document.querySelector('.haram-index-project-table td').textContent };
        });
        assert.ok(result.height > 63.8);
        assert.ok(result.gap > 17);
        assert.equal(result.text, 'مشروع عقارات الرياض '.repeat(10));
    });
});

test('a 255-character auction caption stays inside the footer and produces no blank PDF page', async () => {
    await render('reference', async page => {
        const caption = 'مخرجات المزاد - ' + ('مزاد ريف الرياض الزراعي والسكني ').repeat(8).slice(0, 255);
        await page.evaluate(async caption => {
            document.querySelector('.haram-index-footer-caption').textContent = caption;
            const script = document.createElement('script');
            script.textContent = document.querySelector('[data-haram-layout]').textContent;
            document.body.append(script);
            while (!window.haramReportReady) await new Promise(resolve => setTimeout(resolve, 10));
        }, caption);
        const result = await page.evaluate(() => {
            const caption = document.querySelector('.haram-index-footer-caption');
            const bounds = caption.getBoundingClientRect();
            const footer = caption.parentElement.getBoundingClientRect();
            return { error: window.haramReportError, text: caption.textContent, fits: bounds.bottom <= footer.bottom && bounds.top >= footer.top };
        });
        assert.equal(result.error, null);
        assert.equal(result.text, caption);
        assert.equal(result.fits, true);
        await page.evaluate(() => document.querySelectorAll('.auction-print-page:not(.haram-native-index)').forEach(node => node.remove()));
        const { getDocument } = await import('pdfjs-dist/legacy/build/pdf.mjs');
        const document = await getDocument({ data: new Uint8Array(await page.pdf({ preferCSSPageSize: true, printBackground: true })) }).promise;
        try {
            assert.equal(document.numPages, 1);
        } finally {
            await document.destroy();
        }
    });
});
