import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import puppeteer from 'puppeteer';

const moduleUri = path => 'data:text/javascript;base64,' + Buffer.from(readFileSync(path, 'utf8')).toString('base64');

test('auction draft restores modal values and each property upload into the submitted form', async () => {
    const html = execFileSync('php', ['tests/Fixtures/AuctionReports/render-auction-property-draft.php'], { encoding: 'utf8', maxBuffer: 2 * 1024 * 1024 });
    const browser = await puppeteer.launch({ headless: true, args: ['--no-sandbox'] });
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.setContent(html);
        await page.addScriptTag({ path: 'node_modules/jquery/dist/jquery.min.js' });
        await page.addScriptTag({ path: 'node_modules/bootstrap/dist/js/bootstrap.bundle.min.js' });
        await page.evaluate(() => {
            window.toastr = { success() {}, error() {}, warning() {} };
            window.fetch = async () => new Response(new Blob(['%PDF-1.4\n%%EOF'], { type: 'application/pdf' }));
            $.ajax = options => Promise.resolve().then(() => options.success({
                success: true, assignment: { id: 1, name: 'إسناد' },
                properties: [42, 43].map(id => ({ id, name: `عقار ${id}`, property_number: `P-${id}`, area: 100, estimated_value: 2000, is_selectable: true })),
            }));
        });
        const uploadUri = moduleUri('resources/assets/js/shared/file-upload.js');
        const validation = readFileSync('resources/assets/js/shared/form-validation.js', 'utf8').replace('./file-upload.js', uploadUri);
        await page.addScriptTag({ type: 'module', content: validation + '\nwindow.restoreAuctionDraftFiles = restoreDraftFiles;' });
        const source = readFileSync('resources/assets/js/tenant/auctions/create.js', 'utf8')
            .replace('./report-inputs.js', moduleUri('resources/assets/js/tenant/auctions/report-inputs.js'))
            .replace('../../shared/file-card.js', moduleUri('resources/assets/js/shared/file-card.js'));
        await page.addScriptTag({ type: 'module', content: source + '\nwindow.auctionDraftModuleLoaded = true;' });
        await page.waitForFunction(() => window.auctionDraftModuleLoaded && window.restoreAuctionDraftFiles);
        await page.evaluate(() => document.dispatchEvent(new Event('DOMContentLoaded')));
        assert.deepEqual(errors, []);
        const result = await page.evaluate(async () => {
            const form = document.querySelector('form');
            const data = { assignment_id: '1', property_ids: ['42', '43'], properties_data: {} };
            const files = { properties_data: {} };
            for (const id of ['42', '43']) {
                data.properties_data[id] = { opening_value: `${id}00`, entry_amount: '0', ownership_share: '50', disclosure: `إفصاح ${id}`, board_count: '2', board_dimensions: '3x6' };
                files.properties_data[id] = {
                    valuation: { name: `valuation-${id}.pdf`, mime: 'application/pdf', url: `/valuation-${id}` },
                    board_before: [{ name: `board-${id}.pdf`, mime: 'application/pdf', url: `/board-${id}` }],
                };
            }
            const tasks = [];
            form.dispatchEvent(new CustomEvent('wizardDraftRestored', { detail: { data, files, waitUntil(task) { tasks.push(Promise.resolve(task)); } } }));
            await Promise.all(tasks);
            await window.restoreAuctionDraftFiles(form, files);
            const before = [...new FormData(form)].filter(([key]) => key.startsWith('properties_data')).map(([key, value]) => [key, value instanceof File ? value.name : value]);
            document.querySelector('[data-property-card="42"]').click();
            const opening = document.getElementById('modal_auction_opening_value').value;
            const disclosure = document.getElementById('modal_auction_disclosure').value;
            const modalFiles = [...document.getElementById('modal_file_valuation').files].map(file => file.name);
            return { before, opening, disclosure, modalFiles };
        });
        assert.equal(result.opening.replaceAll(',', ''), '4200');
        assert.equal(result.disclosure, 'إفصاح 42');
        assert.deepEqual(result.modalFiles, ['valuation-42.pdf']);
        for (const id of ['42', '43']) {
            assert.ok(result.before.some(([key, value]) => key === `properties_data[${id}][entry_amount]` && value === '0'));
            assert.ok(result.before.some(([key, value]) => key === `properties_data[${id}][valuation]` && value === `valuation-${id}.pdf`));
            assert.ok(result.before.some(([key, value]) => key === `properties_data[${id}][board_before][]` && value === `board-${id}.pdf`));
        }
        assert.deepEqual(errors, []);
    } finally {
        await browser.close();
    }
});
