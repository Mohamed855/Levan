import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { createServer } from 'node:http';
import test from 'node:test';
import puppeteer from 'puppeteer';

test('broker rows add unique name/file inputs, remove a middle row, and initialize real upload zones', async () => {
    const html = execFileSync(process.env.PHP_BINARY || 'php', ['-r', `
        require 'vendor/autoload.php';
        $app = require 'bootstrap/app.php';
        $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
        Illuminate\\Support\\Facades\\DB::listen(static function (): void { throw new RuntimeException('No database access allowed.'); });
        view()->share('errors', new Illuminate\\Support\\ViewErrorBag);
        echo view('tenant.marketing-campaigns.partials.brokers', ['campaign' => null])->render();
    `], { encoding: 'utf8' });
    const modules = new Map([
        ['/campaign-brokers.js', readFileSync('resources/assets/js/tenant/auctions/campaign-brokers.js', 'utf8').replace('../../shared/file-upload.js', './file-upload.js')],
        ['/file-upload.js', readFileSync('resources/assets/js/shared/file-upload.js', 'utf8')],
    ]);
    const server = createServer((request, response) => {
        response.setHeader('Content-Type', modules.has(request.url) ? 'text/javascript' : 'text/html; charset=utf-8');
        response.end(modules.get(request.url) ?? `<form>${html}</form><script type="module" src="/campaign-brokers.js"></script>`);
    });
    await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
    const browser = await puppeteer.launch({ headless: true, args: ['--no-sandbox'] });
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(`http://127.0.0.1:${server.address().port}`, { waitUntil: 'networkidle0' });
        await page.click('[data-add-broker]');
        await page.click('[data-add-broker]');
        const before = await page.evaluate(() => [...document.querySelectorAll('[data-broker-rows] [data-broker-row]')].map(row => ({
            key: row.querySelector('input[type="hidden"]').value,
            name: row.querySelector('input[type="text"]').name,
            file: row.querySelector('input[type="file"]').name,
            initialized: row.querySelector('[data-file-upload-zone]').dataset.fileUploadInitialized,
        })));
        assert.equal(before.length, 3);
        assert.equal(new Set(before.map(row => row.key)).size, 3);
        assert.ok(before.every(row => row.name.endsWith('[name]') && row.file.endsWith(`[${row.key}]`) && row.initialized === 'true'));
        await page.click('[data-broker-rows] [data-broker-row]:nth-child(2) [data-remove-broker]');
        const after = await page.evaluate(() => [...document.querySelectorAll('[data-broker-rows] input[type="file"]')].map(input => input.name));
        assert.deepEqual(after, [before[0].file, before[2].file]);
        assert.deepEqual(errors, []);
    } finally {
        await browser.close();
        await new Promise(resolve => server.close(resolve));
    }
});
