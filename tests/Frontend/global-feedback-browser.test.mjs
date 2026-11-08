import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { after, before, test } from 'node:test';
import puppeteer from 'puppeteer';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');
const withoutImports = (source) => source.replace(/^import .*;\r?$/gm, '');
let browser;
before(async () => { browser = await puppeteer.launch({ headless: true, pipe: true, timeout: 10000, args: ['--no-sandbox', '--disable-gpu'] }); });
after(async () => { await browser?.close(); });

async function fixture() {
    const page = await browser.newPage();
    const modal = (await read('resources/views/components/shared/bootstrap/confirm-modal.blade.php'))
        .replace(/^@props\([\s\S]*?\]\)\s*/, '')
        .replaceAll('{{ $id }}', 'globalConfirmationModal');
    await page.setRequestInterception(true);
    page.on('request', (request) => request.respond({
        contentType: 'text/html; charset=utf-8',
        body: `<html><head><meta charset="utf-8"><meta name="csrf-token" content="browser-token"></head><body>${modal}
            <button class="js-delete-btn" data-id="1" data-delete-url="/records/1" data-delete-title="السجل الأول">Delete</button></body></html>`,
    }));
    await page.goto('https://tenant.test/records');
    for (const path of ['node_modules/jquery/dist/jquery.js', 'node_modules/bootstrap/dist/js/bootstrap.bundle.js', 'node_modules/toastr/build/toastr.min.js']) {
        await page.addScriptTag({ content: await read(path) });
    }
    await page.addScriptTag({ content: withoutImports(await read('resources/assets/js/shared/toastr.js')) });
    await page.evaluate(() => {
        window.requests = [];
        window.notices = [];
        $.ajax = (options) => window.requests.push(options);
        for (const type of ['success', 'error', 'warning']) {
            const original = window.toastr[type];
            window.toastr[type] = (...args) => { window.notices.push({ type, message: args[0] }); return original(...args); };
        }
    });
    await page.addScriptTag({ content: withoutImports(await read('resources/assets/js/shared/confirm-delete.js')) });
    return page;
}

test('real Bootstrap modal holds one request, blocks dismissal, and closes after success', async () => {
    const page = await fixture();
    try {
        await page.evaluate(() => document.querySelector('.js-delete-btn').click());
        assert.equal(await page.$eval('#globalConfirmationModalHeading', (el) => el.textContent), 'السجل الأول');
        await page.waitForFunction(() => bootstrap.Modal.getInstance(document.getElementById('globalConfirmationModal'))._isTransitioning === false);
        await page.evaluate(() => {
            const submit = document.getElementById('globalConfirmationModalSubmitBtn');
            submit.click();
            submit.click();
            document.getElementById('globalConfirmationModal').dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
        });
        assert.equal(await page.evaluate(() => window.requests.length), 1);
        assert.equal(await page.$eval('#globalConfirmationModalSubmitBtn', (el) => el.disabled), true);
        assert.equal(await page.$eval('#globalConfirmationModal', (el) => el.classList.contains('show')), true);
        await page.evaluate(() => {
            // Detail/dropdown callers own their DOM update and do not reload the page.
            window.confirmRecordDelete({ url: '/other', refresh: false }); // ignored during the pending request
            window.requests[0].error({ status: 422, responseJSON: { message: 'ممنوع الحذف' } });
        });
        await page.waitForFunction(() => !document.getElementById('globalConfirmationModalSubmitBtn').disabled);
        assert.equal(await page.$eval('#globalConfirmationModal', (el) => el.classList.contains('show')), true);
        await page.evaluate(() => {
            window.confirmRecordDelete({ url: '/records/2', refresh: false, title: 'السجل الثاني' });
            document.getElementById('globalConfirmationModalSubmitBtn').click();
            window.requests[1].success({ success: true, message: 'تم الحذف' });
        });
        await page.waitForFunction(() => !document.getElementById('globalConfirmationModal').classList.contains('show'));
        assert.deepEqual(await page.evaluate(() => window.requests.map((request) => request.url)), ['https://tenant.test/records/1', 'https://tenant.test/records/2']);
        assert.deepEqual(await page.evaluate(() => window.notices.map((notice) => notice.type)), ['error', 'success']);
    } finally { await page.close(); }
});

test('session and carried deletion feedback appear once, with escaped message content', async () => {
    const page = await fixture();
    try {
        await page.evaluate(() => {
            const message = '<img src=x onerror="window.injected=true"> تم الحذف';
            document.body.setAttribute('data-flash-success', message);
            sessionStorage.setItem('delete-feedback:/records', message);
        });
        const flash = withoutImports(await read('resources/assets/js/shared/toastr-flash.js'));
        await page.addScriptTag({ content: flash });
        await page.addScriptTag({ content: flash });
        assert.equal(await page.evaluate(() => window.notices.length), 1);
        assert.equal(await page.$$eval('#toast-container .toast', (toasts) => toasts.length), 1);
        assert.equal(await page.$$eval('#toast-container img', (images) => images.length), 0);
        assert.equal(await page.evaluate(() => sessionStorage.getItem('delete-feedback:/records')), null);
        assert.equal(await page.evaluate(() => window.injected), undefined);
    } finally { await page.close(); }
});

test('separate actions can show the same success message again', async () => {
    const page = await fixture();
    try {
        assert.equal(await page.evaluate(() => {
            window.toastr.success('تم الحفظ');
            return Boolean(window.toastr.success('تم الحفظ'));
        }), true);
    } finally { await page.close(); }
});
