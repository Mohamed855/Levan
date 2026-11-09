import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { mkdtempSync, readFileSync, writeFileSync } from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import test from 'node:test';
import puppeteer from 'puppeteer';

const php = process.env.PHP_BINARY || 'php';

async function render(data, type) {
    const directory = mkdtempSync(path.join(os.tmpdir(), 'auction-regulatory-'));
    const input = path.join(directory, 'data.json');
    writeFileSync(input, JSON.stringify(data));
    execFileSync(php, ['scripts/auction-regulatory-forms/render.php', `--type=${type}`, `--data=${input}`, `--output=${directory}`, '--html-only']);
    const browser = await puppeteer.launch({ headless: true, args: ['--no-sandbox'] });
    const page = await browser.newPage();
    await page.emulateMediaType('print');
    await page.setContent(readFileSync(path.join(directory, `${type}.html`), 'utf8'), { waitUntil: 'networkidle0' });
    await page.evaluate(() => document.fonts.ready);
    return { browser, page };
}

test('long closing reasons create measured continuation pages without overlapping signatures or losing records', async () => {
    const properties = Array.from({ length: 18 }, (_, i) => ({
        deed_number: `UNIQUE-${String(i + 1).padStart(3, '0')}`,
        result: 'not_awarded',
        unawarded_reason: 'لم تصل المزايدات إلى الحد الأدنى المعتمد من المالك '.repeat(16),
    }));
    const { browser, page } = await render({ properties }, 'closing');
    try {
        const result = await page.evaluate(() => ({
            ready: window.regulatoryFormReady,
            error: window.regulatoryFormError,
            pages: document.querySelectorAll('[data-form-page]').length,
            text: document.body.innerText,
            overlaps: [...document.querySelectorAll('[data-form-page]')].filter(sheet => sheet.querySelector('.statement-table').getBoundingClientRect().bottom > sheet.querySelector('.signatures').getBoundingClientRect().top).length,
        }));
        assert.equal(result.ready, true);
        assert.equal(result.error, null);
        assert.ok(result.pages > 2, 'Tall rows need more pages than the nominal 17-row capacity.');
        assert.equal(result.overlaps, 0);
        for (const property of properties) assert.equal(result.text.split(property.deed_number).length - 1, 1);
    } finally {
        await browser.close();
    }
});

test('an overlong scalar is reported instead of exported with silently clipped text', async () => {
    const { browser, page } = await render({ organization: { name: 'شركة ذات اسم طويل جدًا '.repeat(100) } }, 'establishment');
    try {
        const error = await page.evaluate(() => window.regulatoryFormError);
        assert.equal(typeof error, 'string');
        assert.match(error, /fit|overflow/i);
    } finally {
        await browser.close();
    }
});
