import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import puppeteer from 'puppeteer';
import { getDocument } from 'pdfjs-dist/legacy/build/pdf.mjs';
import { createCanvas } from '@napi-rs/canvas';

async function render(scenario, callback) {
    execFileSync(process.env.PHP_BINARY || 'php', ['scripts/auction-reports/render-asir-cover-proof.php', scenario], { stdio: 'pipe' });
    const browser = await puppeteer.launch({ headless: true, args: ['--no-sandbox'] });
    try {
        const page = await browser.newPage();
        await page.emulateMediaType('print');
        await page.setContent(readFileSync(`output/pdf/asir-cover-fixes/${scenario}.html`, 'utf8'), { waitUntil: 'load' });
        await page.waitForFunction('window.asirReportReady === true || window.asirReportError !== null');
        assert.equal(await page.evaluate(() => window.asirReportError), null);
        await callback(page);
        const count = await page.$$eval('.auction-print-page', pages => pages.length);
        const pdf = await getDocument({ data: new Uint8Array(await page.pdf({ preferCSSPageSize: true, printBackground: true })) }).promise;
        try { assert.equal(pdf.numPages, count, 'Covers must not add blank/overflow PDF pages.'); } finally { await pdf.destroy(); }
    } finally { await browser.close(); }
}

for (const scenario of ['normal', 'name-99', 'name-255-arabic', 'name-255-latin']) {
    test(`${scenario}: full centered cover titles fit without touching the subtitle`, async () => {
        await render(scenario, async page => {
            const result = await page.evaluate(() => {
                const cover = document.querySelector('[data-asir-cover="front"]');
                if (!cover) return null;
                const name = cover.querySelector('[data-report-field="name"]');
                const subtitle = cover.querySelector('[data-report-field="subtitle"]');
                const a = name.getBoundingClientRect(), b = subtitle.getBoundingClientRect(), c = cover.getBoundingClientRect();
                return { name: name.textContent, subtitle: subtitle.textContent, gap: (b.top - a.bottom) * .75, left: a.left - c.left, right: c.right - a.right, bottom: (b.bottom - c.top) * .75, center: Math.abs((a.left + a.right) / 2 - (c.left + c.right) / 2), font: parseFloat(getComputedStyle(name).fontSize) * .75 };
            });
            assert.ok(result, 'The front cover must have native dynamic content.');
            assert.ok(result.gap >= 8 && result.left >= 0 && result.right >= 0 && result.bottom <= 490);
            assert.ok(result.center < 2 && result.font >= 18);
            if (scenario.startsWith('name-255')) assert.equal([...result.name].length, 255);
            assert.equal(result.subtitle, `التقرير النهائي ${result.name.startsWith('مزاد') ? 'ل' : 'لمزاد '}${result.name}`);
        });
    });
}

test('long contacts preserve complete text and safe links in separate icon/text columns', async () => {
    await render('contacts-long', async page => {
        const rows = await page.$$eval('[data-asir-contact]', rows => rows.map(row => {
            const icon = row.querySelector('img').getBoundingClientRect();
            const text = row.querySelector('[data-report-field]').getBoundingClientRect();
            const frame = row.closest('.auction-print-page').getBoundingClientRect();
            return { role: row.dataset.asirContact, text: row.querySelector('[data-report-field]').textContent, href: row.querySelector('a')?.href, gap: Math.max(text.left - icon.right, icon.left - text.right, text.top - icon.bottom, icon.top - text.bottom) * .75, bottom: (text.bottom - frame.top) * .75, direction: getComputedStyle(row.querySelector('[data-report-field]')).direction };
        }));
        assert.equal(rows.length, 3);
        assert.equal(rows[0].text, '123456789012345678901234567890');
        assert.equal(rows[1].text, '+966 55 123 4567');
        assert.equal(rows[2].text.length, 255);
        assert.equal(rows[1].href, 'tel:+966551234567');
        assert.ok(rows.every(row => row.gap >= 10 && row.bottom <= 820 && row.direction === 'ltr'));
    });
});

test('missing contact values omit the icon and the whole contact group', async () => {
    await render('contacts-empty', async page => {
        assert.equal(await page.$$eval('[data-asir-contact]', nodes => nodes.length), 0);
        assert.equal(await page.$$eval('[data-asir-cover="closing"] [data-asir-contact-icon]', nodes => nodes.length), 0);
        assert.equal(await page.$$eval('[data-asir-cover="closing"]', nodes => nodes.length), 1);
    });
});

for (const scenario of ['contacts-unicode', 'social-multiline', 'phones-30']) {
    test(`${scenario}: accepted contact values fit together inside the closing cover`, async () => {
        await render(scenario, async page => {
            const result = await page.evaluate(() => {
                const contacts = document.querySelector('.asir-cover-contacts');
                const origin = contacts.closest('.auction-print-page').getBoundingClientRect();
                const box = contacts.getBoundingClientRect();
                return { top: (box.top - origin.top) * .75, bottom: (box.bottom - origin.top) * .75, values: [...contacts.querySelectorAll('[data-report-field]')].map(node => ({ text: node.textContent, font: parseFloat(getComputedStyle(node).fontSize) * .75 })) };
            });
            assert.ok(result.top >= 470 && result.bottom <= 820.5);
            assert.ok(result.values.slice(0, 2).every(value => value.font >= 18));
            if (scenario === 'contacts-unicode') {
                assert.equal(result.values[0].text, '⑩'.repeat(30));
                assert.equal(result.values[1].text, '⑩'.repeat(30));
                assert.equal(result.values[2].text.length, 255);
            }
            if (scenario === 'social-multiline') assert.equal(result.values[2].text, '\n'.repeat(200) + 'https://example.com/auctions');
        });
    });
}

test('ordinary single-line titles retain the original source text geometry', async () => {
    await render('legacy', async page => {
        const fields = await page.evaluate(() => {
            const cover = document.querySelector('[data-asir-cover="front"]');
            const origin = cover.getBoundingClientRect();
            return ['name', 'subtitle'].map(role => {
                const range = document.createRange();
                range.selectNodeContents(cover.querySelector(`[data-report-field="${role}"]`));
                const bounds = range.getBoundingClientRect();
                return [bounds.x - origin.x, bounds.y - origin.y, bounds.width, bounds.height].map(value => value * .75);
            });
        });
        const original = [[40.09, 194.4, 514.29, 126.65], [79.19, 329.53, 436.27, 40]];
        for (let index = 0; index < original.length; index++) for (let axis = 0; axis < 4; axis++) assert.ok(Math.abs(fields[index][axis] - original[index][axis]) < 1.5, `Source title geometry changed: ${JSON.stringify(fields)}`);
    });
});

for (const scenario of ['name-255-arabic', 'name-255-latin']) {
    test(`${scenario}: expanded titles stay clear of the original decorative strokes`, async () => {
        await render(scenario, async page => {
            const result = await page.evaluate(() => {
                const cover = document.querySelector('[data-asir-cover="front"]');
                const title = cover.querySelector('.asir-cover-titles').getBoundingClientRect();
                const paths = [...cover.querySelectorAll('[data-cover-decoration] path[stroke="#332832"]')];
                let overlaps = 0;
                for (const path of paths) for (let index = 0; index <= 80; index++) {
                    const point = path.getPointAtLength(path.getTotalLength() * index / 80).matrixTransform(path.getScreenCTM());
                    if (point.x >= title.left && point.x <= title.right && point.y >= title.top && point.y <= title.bottom + 8) overlaps++;
                }
                return { count: paths.length, overlaps };
            });
            assert.equal(result.count, 6, 'Preserve the original six source curves.');
            assert.equal(result.overlaps, 0, 'Decorative strokes must not pass through long cover text.');
        });
    });
}

for (const scenario of ['white-logo', 'dark-logo', 'tall-logo', 'logos-empty']) {
    test(`${scenario}: cover logos have suitable contrast and preserve the decorative background`, async () => {
        await render(scenario, async page => {
            assert.equal(await page.$$eval('[data-asir-cover]', nodes => nodes.length), 2);
            const logos = await page.$$eval('[data-asir-logo]', nodes => nodes.map(node => ({ role: node.dataset.asirLogo, color: getComputedStyle(node).backgroundColor, fit: getComputedStyle(node.querySelector('img')).objectFit })));
            if (scenario === 'logos-empty') assert.equal(logos.length, 0);
            else {
                assert.equal(logos.length, 2);
                assert.ok(logos.every(logo => logo.fit === 'contain'));
                if (scenario === 'white-logo') assert.notEqual(logos.find(logo => logo.role === 'issuing').color, 'rgba(0, 0, 0, 0)');
                if (scenario === 'dark-logo') assert.notEqual(logos.find(logo => logo.role === 'company').color, 'rgba(0, 0, 0, 0)');
            }
            assert.equal(await page.$$eval('[data-asir-cover] [data-cover-decoration]', nodes => nodes.length), 2);
        });
    });
}

for (const scenario of ['white-logo', 'dark-logo']) {
    test(`${scenario}: the application PDF prints contrast backings without covering the photograph`, async () => {
        await render(scenario, async page => {
            const point = await page.evaluate(scenario => {
                const logo = document.querySelector(`[data-asir-logo="${scenario === 'white-logo' ? 'issuing' : 'company'}"]`);
                const cover = logo.closest('.auction-print-page');
                const a = logo.getBoundingClientRect(), b = cover.getBoundingClientRect();
                return { page: [...document.querySelectorAll('.auction-print-page')].indexOf(cover) + 1, x: (a.x - b.x) * .75 + 1, y: (a.y - b.y + a.height / 2) * .75 };
            }, scenario);
            execFileSync(process.env.PHP_BINARY || 'php', ['scripts/auction-reports/render-asir-cover-proof.php', scenario, '--pdf'], { stdio: 'pipe' });
            const pdf = await getDocument({ data: new Uint8Array(readFileSync(`output/pdf/asir-cover-fixes/${scenario}.pdf`)) }).promise;
            try {
                const sheet = await pdf.getPage(point.page);
                const viewport = sheet.getViewport({ scale: 2 });
                const canvas = createCanvas(Math.ceil(viewport.width), Math.ceil(viewport.height));
                const context = canvas.getContext('2d');
                await sheet.render({ canvasContext: context, viewport }).promise;
                const rgb = (x, y) => [...context.getImageData(Math.floor(x * 2), Math.floor(y * 2), 1, 1).data].slice(0, 3);
                const backing = rgb(point.x, point.y);
                if (scenario === 'white-logo') assert.ok(backing.every(value => value < 80), `White logo backing: ${backing}`);
                else {
                    assert.ok(backing.every(value => value > 200), `Dark logo backing: ${backing}`);
                    const pixels = [[60, 300], [100, 250], [120, 350]].map(([x, y]) => rgb(x, y).join(','));
                    assert.ok(new Set(pixels).size > 1, 'The photograph must retain texture instead of a solid branding mask.');
                    assert.ok(pixels.some(pixel => pixel !== '26,26,26'));
                }
            } finally { await pdf.destroy(); }
        });
    });
}
