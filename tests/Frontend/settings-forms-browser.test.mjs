import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFile } from 'node:fs/promises';
import { after, before, test } from 'node:test';
import puppeteer from 'puppeteer';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');
let browser;
before(async () => { browser = await puppeteer.launch({ headless: true, pipe: true, args: ['--no-sandbox', '--disable-gpu'] }); });
after(async () => { await browser?.close(); });

function renderForm(resource, editing) {
    const php = `require 'vendor/autoload.php';
        $app = require 'bootstrap/app.php';
        $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
        view()->share('errors', new Illuminate\\Support\\ViewErrorBag);
        $bank = (new App\\Models\\Admin\\Bank\\Bank)->forceFill(['id'=>7, 'bank_name'=>'Bank', 'account_name'=>'Account', 'iban'=>'SA0380000000608010167519', 'account_number'=>'608010167519']);
        echo view('admin.settings.${resource}.partials._form', ['is_edit'=>${editing ? 'true' : 'false'}, 'bank'=>$bank])->render();`;
    return execFileSync('php', ['-r', php], { encoding: 'utf8' });
}

async function fixture(resource, editing) {
    const page = await browser.newPage();
    const html = renderForm(resource, editing);
    await page.setRequestInterception(true);
    page.on('request', (request) => request.respond({ contentType: 'text/html; charset=utf-8', body: `<html><head><meta charset="utf-8"><meta name="csrf-token" content="test-token"><style>.d-none{display:none!important}.content{display:block}</style></head><body>${html}</body></html>` }));
    await page.goto(`https://admin.test/superadmin/settings/${resource}/${editing ? '7/edit' : 'create'}`);
    await page.evaluate(() => {
        window.validationRequests = [];
        window.toastr = { success() {}, warning() {}, error() {} };
        window.fetch = async (url, options) => {
            if (String(url).includes('validate-step')) {
                return new Promise((resolve) => window.validationRequests.push({ resolve, fields: options.body.getAll('fields[]'), method: options.body.get('target_method'), path: options.body.get('target_path') }));
            }
            return { ok: true, json: async () => ({ draft_id: null }) };
        };
    });
    const upload = (await read('resources/assets/js/shared/file-upload.js')).replace(/^export /gm, '');
    await page.addScriptTag({ content: upload });
    const validation = (await read('resources/assets/js/shared/form-validation.js')).replace(/import\s*\{[\s\S]*?\}\s*from "\.\/file-upload\.js";\s*/, '');
    await page.addScriptTag({ content: validation });
    await page.waitForFunction(() => document.querySelector('form').dataset.formValidationInitialized === 'true' && !document.querySelector('form').inert);
    return page;
}

for (const resource of ['banks']) {
    for (const editing of [false, true]) {
        test(`${resource} ${editing ? 'edit' : 'create'} renders required controls and immediate validation`, async () => {
            const page = await fixture(resource, editing);
            try {
                const names = ['bank_name', 'account_name', 'iban', 'account_number'];
                for (const name of names) {
                    assert.equal(await page.$eval(`[name="${name}"]`, (input) => input.getAttribute('aria-required')), 'true', `${name} required contract`);
                }
                const field = 'bank_name';
                await page.$eval(`[name="${field}"]`, (input) => { input.value = ''; input.focus(); input.blur(); });
                assert.equal(await page.$eval(`[name="${field}"]`, (input) => input.getAttribute('aria-invalid')), 'true');
                assert.equal(await page.$eval(`[data-field-name="${field}"] [data-form-error]`, (error) => error.hidden), false);
                const request = await page.evaluate(() => window.validationRequests.at(-1) && ({ method: window.validationRequests.at(-1).method, path: window.validationRequests.at(-1).path }));
                assert.equal(request.method, editing ? 'PUT' : 'POST');
                assert.equal(request.path, `/superadmin/settings/${resource}${editing ? '/7' : ''}`);
                await page.$eval('[name="iban"]', (input) => { input.value = 'bad-iban'; input.focus(); input.blur(); });
                assert.equal(await page.$eval('[name="iban"]', (input) => input.getAttribute('aria-invalid')), 'true');
            } finally { await page.close(); }
        });
    }
}
