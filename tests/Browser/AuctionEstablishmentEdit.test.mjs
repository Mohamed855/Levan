import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import puppeteer from 'puppeteer';

const png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScLbtAAAAABJRU5ErkJggg==';
async function render(existing, run) {
    const html = execFileSync('php', ['tests/Fixtures/AuctionReports/render-establishment-edit.php', existing ? 'existing' : 'empty'], { encoding: 'utf8', maxBuffer: 2 * 1024 * 1024 });
    assert.ok(html.startsWith('<!doctype html>'), html.slice(0, 700));
    assert.ok(html.includes('id="modal_prop_id"'), html.slice(-1000));
    const browser = await puppeteer.launch({ headless: true, args: ['--no-sandbox'] });
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.setRequestInterception(true);
        page.on('request', request => request.url().startsWith('http') ? request.respond({ status: 200, contentType: 'image/png', body: Buffer.from(png, 'base64') }) : request.continue());
        await page.setContent(html);
        await page.addScriptTag({ path: 'node_modules/jquery/dist/jquery.min.js' });
        await page.addScriptTag({ path: 'node_modules/bootstrap/dist/js/bootstrap.bundle.min.js' });
        await page.evaluate(png => {
            window.toastr = { success() {}, warning() {}, error() {} };
            window.fetch = async () => new Response(Uint8Array.from(atob(png), c => c.charCodeAt(0)), { headers: { 'Content-Type': 'image/png' } });
        }, png);
        await page.addScriptTag({ type: 'module', content: readFileSync('resources/assets/js/shared/file-upload.js', 'utf8') });
        const cardUri = 'data:text/javascript;base64,' + Buffer.from(readFileSync('resources/assets/js/shared/file-card.js', 'utf8')).toString('base64');
        await page.addScriptTag({ type: 'module', content: readFileSync('resources/assets/js/tenant/auction-establishment-requests/edit.js', 'utf8').replace('../../shared/file-card.js', cardUri) + '\nwindow.establishmentEditorLoaded = true;' });
        await page.waitForFunction('window.establishmentEditorLoaded === true', { timeout: 3000 });
        await page.evaluate(() => document.dispatchEvent(new Event('DOMContentLoaded')));
        assert.deepEqual(errors, []);
        await run(page);
        assert.deepEqual(errors, []);
    } finally { await browser.close(); }
}

async function open(page, id = 42) {
    await page.click(`[data-property-row="${id}"] td:nth-child(4)`);
    assert.equal(await page.$eval('#modal_prop_id', node => node.value), String(id));
    await page.waitForSelector('#establishmentPropertyModal.show', { timeout: 3000 });
}

test('properties next remains disabled until required property data and attachments are complete', async () => {
    await render(false, async page => {
        assert.equal(await page.$eval('#step-establishment-properties [data-wizard-next]', node => node.disabled), true);
    });
});

test('properties next enables for complete properties and disables for invalid restored data', async () => {
    await render(true, async page => {
        assert.equal(await page.$eval('#step-establishment-properties [data-wizard-next]', node => node.disabled), false);
        for (const [field, value] of [['age', '-1'], ['ownership_share', '101'], ['opening_value', '-1'], ['deed_number', 'abc'], ['agency_number', 'abc']]) {
            await page.evaluate(([field, value]) => {
                const input = document.querySelector(`[data-prop-hidden="42"] [data-field="${field}"]`);
                input.dataset.original = input.value;
                input.value = value;
                document.querySelector('form').dispatchEvent(new CustomEvent('wizardDraftRestored'));
            }, [field, value]);
            assert.equal(await page.$eval('#step-establishment-properties [data-wizard-next]', node => node.disabled), true, field);
            await page.evaluate(field => {
                const input = document.querySelector(`[data-prop-hidden="42"] [data-field="${field}"]`);
                input.value = input.dataset.original;
                document.querySelector('form').dispatchEvent(new CustomEvent('wizardDraftRestored'));
            }, field);
        }
    });
});

test('modal retains conditional errors for other status, other usage and representative authority', async () => {
    await render(true, async page => {
        await open(page);
        await page.evaluate(() => {
            document.querySelector('#modal_prop_usage').value = 'other';
            document.querySelector('#modal_prop_status').value = 'other';
            document.querySelector('#modal_prop_agent_name').value = 'وكيل العقار';
        });
        await page.click('#btn-save-establishment-prop-modal');
        for (const id of ['modal_prop_usage_other', 'modal_prop_status_other', 'modal_prop_agency_number', 'modal_file_representative_authority']) {
            assert.equal(await page.$eval(`#${id}`, node => node.classList.contains('is-invalid')), true, id);
        }
        assert.equal(await page.$eval('#establishmentPropertyModal', node => node.classList.contains('show')), true);
    });
});

test('modal files stay with their property and cancelled selections do not enter the form', async () => {
    await render(true, async page => {
        await open(page);
        await page.evaluate(() => window.SharedFileUpload.getController(document.querySelector('#modal_file_deed')).setFiles([new File(['replacement'], 'replacement.png', { type: 'image/png' })]));
        await page.click('#btn-save-establishment-prop-modal');
        await page.waitForSelector('#establishmentPropertyModal:not(.show)');
        await open(page, 43);
        assert.equal(await page.$eval('#modal_file_deed', node => node.files.length), 0);
        await page.evaluate(() => window.SharedFileUpload.getController(document.querySelector('#modal_file_deed')).setFiles([new File(['cancelled'], 'cancelled.png', { type: 'image/png' })]));
        await page.click('#establishmentPropertyModal [data-bs-dismiss="modal"]');
        await page.waitForSelector('#establishmentPropertyModal:not(.show)');
        assert.deepEqual(await page.$$eval('form input[type=file]', inputs => inputs.flatMap(input => [...input.files].map(file => file.name))), ['replacement.png']);
        await open(page);
        assert.equal(await page.$eval('#modal_file_deed', node => node.files[0]?.name), 'replacement.png');
    });
});

test('the modal does not save or close without valuation when the entry amount is positive', async () => {
    await render(true, async page => {
        await page.evaluate(() => {
            const table = document.querySelector('#establishment-properties-table');
            const attachments = JSON.parse(table.dataset.existingAttachments);
            delete attachments[42].valuation;
            table.dataset.existingAttachments = JSON.stringify(attachments);
        });
        await open(page);
        await page.click('#btn-save-establishment-prop-modal');
        assert.equal(await page.$eval('#establishmentPropertyModal', node => node.classList.contains('show')), true);
        assert.equal(await page.$eval('#modal_file_valuation', node => node.classList.contains('is-invalid')), true);
    });
});

test('saved image metadata renders image previews both outside and inside the property modal', async () => {
    await render(true, async page => {
        assert.equal(await page.$$eval('#establishment-request-form [data-existing-file-card] img', nodes => nodes.length), 2);
        await open(page);
        assert.equal(await page.$$eval('#establishmentPropertyModal [data-existing-file-card] img', nodes => nodes.length), 2);
    });
});

test('removing a saved deed requires a replacement and uses the existing component replacement controls', async () => {
    await render(true, async page => {
        await open(page);
        await page.click('#modal_file_deed-preview-list [data-existing-file-card] .btn-remove-file');
        await page.click('#btn-save-establishment-prop-modal');
        assert.equal(await page.$eval('#establishmentPropertyModal', node => node.classList.contains('show')), true);
        assert.equal(await page.$eval('#modal_file_deed', node => node.classList.contains('is-invalid')), true);
        await page.evaluate(() => window.SharedFileUpload.getController(document.querySelector('#modal_file_deed')).setFiles([new File(['new'], 'new.png', { type: 'image/png' })]));
        await page.click('#btn-save-establishment-prop-modal');
        await page.waitForSelector('#establishmentPropertyModal:not(.show)');
        assert.equal(await page.$eval('form [name="attachments[deed][42]"]', node => node.files[0]?.name), 'new.png');
    });
});

test('optional saved property file removal survives reopen while cancelling restores it', async () => {
    await render(true, async page => {
        await open(page);
        await page.click('#modal_file_valuation-preview-list .btn-remove-file');
        await page.click('#establishmentPropertyModal [data-bs-dismiss="modal"]');
        await page.waitForSelector('#establishmentPropertyModal:not(.show)');
        await open(page);
        assert.equal(await page.$$eval('#modal_file_valuation-preview-list [data-existing-file-card]', nodes => nodes.length), 1);
        await page.$eval('#modal_prop_entry_amount', node => { node.value = '0'; });
        await page.click('#modal_file_valuation-preview-list .btn-remove-file');
        await page.click('#btn-save-establishment-prop-modal');
        await page.waitForSelector('#establishmentPropertyModal:not(.show)');
        assert.equal(await page.$eval('form [name="remove_attachments[valuation][42]"]', node => node.value), '1');
        await open(page);
        assert.equal(await page.$$eval('#modal_file_valuation-preview-list [data-existing-file-card]', nodes => nodes.length), 0);
    });
});

test('removing a saved certificate requires replacement and restored removal flags hide the old card', async () => {
    await render(true, async page => {
        const input = '[name="attachments[manager_certificate]"]';
        await page.$eval(input, node => node.closest('[data-file-upload-wrapper]').querySelector('.btn-remove-file').click());
        assert.equal(await page.$eval(input, node => node.required), true);
        assert.equal(await page.$eval('[name="remove_attachments_manager_certificate"]', node => node.value), '1');
    });
    await render(true, async page => {
        await page.evaluate(() => {
            document.querySelector('[name="remove_attachments_manager_certificate"]').value = '1';
            document.querySelector('form').dispatchEvent(new CustomEvent('wizardDraftRestored'));
        });
        assert.equal(await page.$eval('[name="attachments[manager_certificate]"]', node => node.closest('[data-file-upload-wrapper]').querySelectorAll('[data-existing-file-card]').length), 0);
    });
});

test('draft restores canonical property files and outside certificates and retains them after modal reopen', async () => {
    await render(false, async page => {
        const source = readFileSync('resources/assets/js/shared/form-validation.js', 'utf8').replace(/import\s*\{[\s\S]*?\}\s*from "\.\/file-upload\.js";\s*/, '').split('if (document.readyState === "loading")')[0];
        await page.evaluate(() => {
            window.getFileUploadController = window.SharedFileUpload.getController;
            window.initAllFileUploadZones = window.SharedFileUpload.initAll;
            window.setFilesOnUploadZone = window.SharedFileUpload.setFiles;
        });
        await page.addScriptTag({ content: source + '\nwindow.restoreEstablishmentDraftFiles = restoreDraftFiles;' });
        await page.evaluate(async () => {
            const files = { attachments: { manager_certificate: [{ name: 'manager.png', mime: 'image/png', url: '/draft-file', key: 'attachments.manager_certificate.0' }], deed: { 42: { name: 'deed-42.png', mime: 'image/png', url: '/draft-file', key: 'attachments.deed.42' } }, valuation: { 42: { name: 'valuation-42.png', mime: 'image/png', url: '/draft-file', key: 'attachments.valuation.42' } } } };
            await window.restoreEstablishmentDraftFiles(document.querySelector('form'), files);
            document.querySelector('form').dispatchEvent(new CustomEvent('wizardDraftRestored', { detail: { files } }));
        });
        const names = await page.$$eval('form input[type=file]', inputs => inputs.flatMap(input => [...input.files].map(file => file.name)).sort());
        assert.deepEqual(names, ['deed-42.png', 'manager.png', 'valuation-42.png']);
        await open(page);
        assert.equal(await page.$eval('#modal_file_deed', node => node.files[0]?.name), 'deed-42.png');
        assert.equal(await page.$eval('#modal_file_valuation', node => node.files[0]?.name), 'valuation-42.png');
        await page.click('#btn-save-establishment-prop-modal');
        await page.waitForSelector('#establishmentPropertyModal:not(.show)');
        await open(page);
        assert.equal(await page.$eval('#modal_file_deed', node => node.files[0]?.name), 'deed-42.png');
    });
});
