import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFile } from 'node:fs/promises';
import { after, before, test } from 'node:test';
import puppeteer from 'puppeteer';

let browser;
const root = new URL('../../', import.meta.url);
const source = (await readFile(new URL('resources/assets/js/shared/confirm-delete.js', root), 'utf8'))
    .replace(/^import .*;\r?$/gm, '');

before(async () => {
    browser = await puppeteer.launch({ headless: true, pipe: true, timeout: 10000, args: ['--no-sandbox', '--disable-gpu'] });
});
after(async () => { await browser?.close(); });

function render(module) {
    const model = module === 'banks'
        ? 'App\\Models\\Admin\\Bank\\Bank'
        : (module === 'faqs' ? 'App\\Models\\Admin\\Faq' : 'App\\Models\\Admin\\ControlDomain\\ControlDomain');
    const viewName = module === 'faqs' ? 'admin.faqs.action' : `admin.settings.${module}.action`;
    const routeName = module === 'faqs' ? 'admin.faqs' : `admin.settings.${module}`;
    return JSON.parse(execFileSync('php', ['-r', `
        require 'vendor/autoload.php';
        $app = require 'bootstrap/app.php';
        $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
        Illuminate\\Support\\Facades\\Auth::shouldUse('admin');
        Illuminate\\Support\\Facades\\Auth::guard('admin')->setUser((new App\\Models\\Admin\\AdminMangment\\Users\\AdminUser)->forceFill(['id' => 10]));
        Illuminate\\Support\\Facades\\Gate::before(fn () => true);
        $row = (new ${model})->forceFill(['id' => 7]);
        echo json_encode([
            'actions' => view('${viewName}', ['row' => $row, 'route' => '${routeName}'])->render(),
            'modal' => Illuminate\\Support\\Facades\\Blade::render('<x-bootstrap.confirm-modal />'),
        ]);
    `], { cwd: root, encoding: 'utf8' }));
}

for (const module of ['banks', 'domains', 'faqs']) {
    test(`${module}: the rendered delete button opens the shared modal under the application's strict CSP`, async () => {
        const page = await browser.newPage();
        const fixture = render(module);
        const failures = [];
        page.on('console', (message) => { if (message.type() === 'error') failures.push(message.text()); });
        await page.setRequestInterception(true);
        page.on('request', (request) => request.respond({
            contentType: 'text/html; charset=utf-8',
            headers: { 'Content-Security-Policy': "script-src 'nonce-regression' 'strict-dynamic'; object-src 'none'" },
            body: `<html><head><meta charset="utf-8"><meta name="csrf-token" content="test"></head><body>${fixture.actions}${fixture.modal}</body></html>`,
        }));
        try {
            const pagePath = module === 'faqs' ? 'superadmin/faqs' : `superadmin/settings/${module}`;
            await page.goto(`http://localhost:8000/${pagePath}`);
            for (const path of ['node_modules/jquery/dist/jquery.js', 'node_modules/bootstrap/dist/js/bootstrap.bundle.js']) {
                const text = await readFile(new URL(path, root), 'utf8');
                await page.evaluate((text) => {
                    const script = document.createElement('script');
                    script.nonce = 'regression';
                    script.textContent = text;
                    document.head.appendChild(script);
                }, text);
            }
            await page.evaluate((source) => {
                window.toastr = { error() {}, success() {} };
                window.deleteRequests = [];
                window.jQuery.ajax = (options) => window.deleteRequests.push(options);
                const script = document.createElement('script');
                script.nonce = 'regression';
                script.textContent = source;
                document.head.appendChild(script);
            }, source);
            await page.click('button.js-delete-btn');
            await page.waitForFunction(() => document.getElementById('globalConfirmationModal').classList.contains('show'), { timeout: 1000 });
            assert.equal(await page.evaluate(() => window.deleteRequests.length), 0);
            await page.waitForFunction(() => !bootstrap.Modal.getInstance(document.getElementById('globalConfirmationModal'))._isTransitioning);
            await page.click('#globalConfirmationModalSubmitBtn');
            assert.equal(await page.evaluate(() => window.deleteRequests.length), 1);
            assert.equal(await page.evaluate(() => window.deleteRequests[0].method), 'DELETE');
            assert.equal(await page.evaluate(() => window.deleteRequests[0].url), `http://localhost:8000/${pagePath}/7`);
            assert.deepEqual(failures.filter((message) => message.includes('inline event handler')), []);
        } finally { await page.close(); }
    });
}
