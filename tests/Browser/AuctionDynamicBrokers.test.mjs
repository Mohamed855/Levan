import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import puppeteer from 'puppeteer';

const moduleUri = path => 'data:text/javascript;base64,' + Buffer.from(readFileSync(path, 'utf8')).toString('base64');

for (const [mode, initialCount] of [['brokers', 0], ['brokers-existing', 2]]) {
test(`auction brokers add and remove rows with unique names and working contract uploads: ${mode}`, async () => {
    const html = execFileSync('php', ['tests/Fixtures/AuctionReports/render-auction-property-draft.php', mode], { encoding: 'utf8', maxBuffer: 2 * 1024 * 1024 });
    const browser = await puppeteer.launch({ headless: true, args: ['--no-sandbox'] });
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.setContent(html);
        await page.addScriptTag({ path: 'node_modules/jquery/dist/jquery.min.js' });
        await page.addScriptTag({ path: 'node_modules/bootstrap/dist/js/bootstrap.bundle.min.js' });
        await page.addScriptTag({ type: 'module', content: readFileSync('resources/assets/js/shared/file-upload.js', 'utf8') });
        const source = readFileSync('resources/assets/js/tenant/auctions/create.js', 'utf8')
            .replace('./report-inputs.js', moduleUri('resources/assets/js/tenant/auctions/report-inputs.js'))
            .replace('../../shared/file-card.js', moduleUri('resources/assets/js/shared/file-card.js'));
        await page.addScriptTag({ type: 'module', content: source + '\nwindow.auctionBrokersLoaded = true;' });
        await page.waitForFunction(() => window.auctionBrokersLoaded && window.SharedFileUpload);
        await page.evaluate(() => document.dispatchEvent(new Event('DOMContentLoaded')));
        assert.deepEqual(errors, []);
        for (let index = 0; index < 3; index++) await page.click('#btn-add-auction-broker');
        const rows = () => page.evaluate(() => [...document.querySelectorAll('#auction-brokers-list [data-auction-broker-row]')].map(row => ({
            key: row.dataset.brokerKey,
            name: row.querySelector('input[type="text"]').name,
            file: row.querySelector('input[type="file"]').name,
            initialized: row.querySelector('[data-file-upload-zone]').dataset.fileUploadInitialized,
            title: row.querySelector('.broker-number').textContent,
        })));
        const before = await rows();
        assert.equal(before.length, initialCount + 3);
        assert.equal(new Set(before.map(row => row.key)).size, before.length);
        assert.ok(before.every(row => row.file === `broker_evidence[${row.key}]` && row.initialized === 'true'));
        await page.click('#auction-brokers-list [data-auction-broker-row]:nth-child(2) .btn-remove-auction-broker');
        await page.click('#btn-add-auction-broker');
        const after = await rows();
        const expectedIndices = [...Array(initialCount + 4).keys()].filter(index => index !== 1);
        assert.deepEqual(after.map(row => row.name), expectedIndices.map(index => `marketing[brokers][${index}][name]`));
        assert.equal(after[0].key, before[0].key);
        assert.equal(after[1].key, before[2].key);
        assert.equal(new Set(after.map(row => row.key)).size, after.length);
        const files = await page.evaluate(() => {
            const rows = [...document.querySelectorAll('#auction-brokers-list [data-auction-broker-row]')];
            rows.forEach((row, index) => {
                row.querySelector('input[type="text"]').value = `Broker ${index + 1}`;
                window.SharedFileUpload.getController(row.querySelector('input[type="file"]')).setFiles([new File(['%PDF-1.4'], `contract-${index + 1}.pdf`, { type: 'application/pdf' })]);
            });
            return [...new FormData(document.querySelector('form'))].filter(([name, value]) => name.startsWith('broker_evidence[') && value instanceof File && value.name).map(([name, value]) => [name, value.name]);
        });
        assert.deepEqual(files, after.map((row, index) => [row.file, `contract-${index + 1}.pdf`]));
        await page.evaluate(() => document.querySelectorAll('#auction-brokers-list .btn-remove-auction-broker').forEach(button => button.click()));
        assert.equal(await page.$eval('#auction-brokers-empty', element => element.textContent.trim().length > 0), true);
        await page.click('#btn-add-auction-broker');
        assert.equal((await rows()).length, 1);
        assert.deepEqual(errors, []);
    } finally {
        await browser.close();
    }
});
}
